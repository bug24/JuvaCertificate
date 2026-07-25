param(
    [string]$MySqlUser = 'root',
    [string]$MySqlPassword = '',
    [string]$DatabaseName = 'juva_certify',
    [string]$LegacySqlPath = 'C:\AppServ\www\JUVA OIL\localhost.sql',
    [int]$ApiPort = 8088,
    [int]$FrontendPort = 4175,
    [string]$AdminName = 'JUVA Super Admin',
    [string]$AdminEmail = 'juvaoil@gmail.com',
    [string]$AdminUsername = 'superadmin',
    [string]$AdminPassword = '',
    [switch]$ImportLegacy,
    [switch]$StartServers
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSCommandPath
$phpExe = 'C:\AppServ\php7\php.exe'
$mysqlExe = 'C:\AppServ\MySQL\bin\mysql.exe'
$frontendUrl = "http://127.0.0.1:$FrontendPort"
$apiUrl = "http://127.0.0.1:$ApiPort"
$privateStoragePath = Join-Path $projectRoot 'storage-private'
$configPath = Join-Path $projectRoot 'api\config.local.php'
$envPath = Join-Path $projectRoot '.env.local'
$schemaPath = Join-Path $projectRoot 'database\schema.sql'
$migrationPath = Join-Path $projectRoot 'database\migrations\phase8_legacy_migration.sql'
$migrationDataOnlyPath = Join-Path $projectRoot 'database\migrations\phase8_legacy_migration.data-only.sql'
$legacyPreparedPath = Join-Path $projectRoot 'database\migrations\localhost.prepared.sql'

function Require-File([string]$Path, [string]$Label) {
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "$Label not found: $Path"
    }
}

function New-RandomHex([int]$Length = 64) {
    $value = ''
    while ($value.Length -lt $Length) {
        $value += [Guid]::NewGuid().ToString('N')
    }
    return $value.Substring(0, $Length)
}

function Escape-Php([string]$Value) {
    return $Value.Replace('\\', '\\\\').Replace("'", "\\'")
}

function Invoke-MySqlFile([string]$Database, [string]$FilePath, [switch]$Force) {
    $sourcePath = ($FilePath -replace '\\', '/')
    $args = @("--user=$MySqlUser", "--password=$MySqlPassword", "--database=$Database")
    if ($Force) {
        $args += '--force'
    }
    $args += "--execute=SOURCE $sourcePath"
    & $mysqlExe @args
    if ($LASTEXITCODE -ne 0 -and -not $Force) {
        throw "MySQL import failed for $FilePath"
    }
}

Require-File $phpExe 'PHP executable'
Require-File $mysqlExe 'MySQL executable'
Require-File $schemaPath 'Schema file'

if ([string]::IsNullOrWhiteSpace($MySqlPassword)) {
    $secure = Read-Host 'Enter local MySQL password' -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $MySqlPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    } finally {
        if ($bstr -ne [IntPtr]::Zero) {
            [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
        }
    }
}

if ([string]::IsNullOrWhiteSpace($MySqlPassword)) {
    throw 'A MySQL password is required.'
}

$securitySalt = New-RandomHex 64
$cronKey = New-RandomHex 48
$setupKey = New-RandomHex 32

New-Item -ItemType Directory -Force -Path $privateStoragePath | Out-Null

$configContents = @"
<?php

return [
    'app_env' => 'local',
    'app_url' => '$(Escape-Php $frontendUrl)',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => '$(Escape-Php $DatabaseName)',
    'db_user' => '$(Escape-Php $MySqlUser)',
    'db_password' => '$(Escape-Php $MySqlPassword)',
    'session_name' => 'juva_certify_session',
    'session_hours' => 8,
    'remember_days' => 30,
    'allowed_origin' => '$(Escape-Php $frontendUrl)',
    'security_salt' => '$(Escape-Php $securitySalt)',
    'cron_key' => '$(Escape-Php $cronKey)',
    'reminder_days' => 30,
    'admin_notice_email' => '$(Escape-Php $AdminEmail)',
    'setup_key' => '$(Escape-Php $setupKey)',
    'mail_transport' => 'log',
    'mail_from' => 'no-reply@cert.juvaoil.com',
    'mail_from_name' => 'JUVA Certify Manager',
    'private_storage_path' => '$(Escape-Php $privateStoragePath)',
];
"@
$configContents | Set-Content -LiteralPath $configPath -Encoding UTF8

"VITE_API_BASE_URL=$apiUrl/api" | Set-Content -LiteralPath $envPath -Encoding UTF8

& $mysqlExe "--user=$MySqlUser" "--password=$MySqlPassword" "--execute=DROP DATABASE IF EXISTS ``$DatabaseName``; CREATE DATABASE ``$DatabaseName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to recreate the local JUVA database.'
}

Invoke-MySqlFile -Database $DatabaseName -FilePath $schemaPath

if ($ImportLegacy) {
    Require-File $LegacySqlPath 'Legacy SQL dump'
    Require-File $migrationPath 'Legacy migration file'

    $preparedLines = Get-Content -LiteralPath $LegacySqlPath | Where-Object {
        $_ -notmatch '^CREATE DATABASE IF NOT EXISTS ' -and $_ -notmatch '^USE `juvaoil_juvaoilltd`;'
    }
    $preparedLines | Set-Content -LiteralPath $legacyPreparedPath -Encoding UTF8
    Invoke-MySqlFile -Database $DatabaseName -FilePath $legacyPreparedPath -Force

    $migrationLines = Get-Content -LiteralPath $migrationPath
    $startIndex = -1
    for ($i = 0; $i -lt $migrationLines.Count; $i++) {
        if ($migrationLines[$i] -match '^START TRANSACTION;') {
            $startIndex = $i
            break
        }
    }
    if ($startIndex -lt 0) {
        throw 'Unable to find START TRANSACTION in phase8 migration file.'
    }
    $migrationLines[$startIndex..($migrationLines.Count - 1)] | Set-Content -LiteralPath $migrationDataOnlyPath -Encoding UTF8
    Invoke-MySqlFile -Database $DatabaseName -FilePath $migrationDataOnlyPath
}

$apiProcess = $null
$viteProcess = $null

if ($StartServers) {
    $apiProcess = Start-Process -FilePath $phpExe -ArgumentList @('-S', "127.0.0.1:$ApiPort", '-t', $projectRoot) -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 2

    if (Test-Path -LiteralPath (Join-Path $projectRoot 'node_modules')) {
        $viteProcess = Start-Process -FilePath 'pnpm.cmd' -ArgumentList @('run', 'dev', '--', '--port', $FrontendPort) -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru
        Start-Sleep -Seconds 3
    } else {
        Write-Warning 'node_modules is missing. Run pnpm install before starting the frontend.'
    }
}

if ($StartServers -and -not [string]::IsNullOrWhiteSpace($AdminPassword)) {
    $bootstrapBody = @{
        setup_key = $setupKey
        name = $AdminName
        email = $AdminEmail
        username = $AdminUsername
        password = $AdminPassword
    } | ConvertTo-Json

    try {
        Invoke-RestMethod -Uri "$apiUrl/api/setup/bootstrap-admin.php" -Method Post -ContentType 'application/json' -Body $bootstrapBody | Out-Null
    } catch {
        Write-Warning "Bootstrap admin creation failed: $($_.Exception.Message)"
    }
}

$summary = [ordered]@{
    frontend_url = $frontendUrl
    api_url = "$apiUrl/api"
    database = $DatabaseName
    mysql_user = $MySqlUser
    setup_key = $setupKey
    config_file = $configPath
    env_file = $envPath
    private_storage = $privateStoragePath
}

if ($apiProcess) {
    $summary.api_pid = $apiProcess.Id
}
if ($viteProcess) {
    $summary.frontend_pid = $viteProcess.Id
}

$summary | ConvertTo-Json
