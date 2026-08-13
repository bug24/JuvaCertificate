<?php

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!is_file($autoload)) throw new RuntimeException('PDF merge dependencies are not installed.');
require_once $autoload;

use setasign\Fpdi\Fpdi;

function certificate_batch_merge_pdfs(array $sourcePaths, string $outputPath): void
{
    if (!$sourcePaths) throw new InvalidArgumentException('No certificate PDFs were supplied.');
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false);
    foreach ($sourcePaths as $sourcePath) {
        if (!is_file($sourcePath) || filesize($sourcePath) < 5) throw new RuntimeException('A certificate PDF is unavailable.');
        $handle = fopen($sourcePath, 'rb');
        $signature = $handle ? fread($handle, 5) : '';
        if ($handle) fclose($handle);
        if ($signature !== '%PDF-') throw new RuntimeException('A certificate file is not a valid PDF.');
        $pageCount = $pdf->setSourceFile($sourcePath);
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $template = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($template);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);
        }
    }
    $pdf->Output('F', $outputPath);
    if (!is_file($outputPath) || filesize($outputPath) < 5) throw new RuntimeException('The combined PDF could not be created.');
}

function certificate_batch_cleanup(string $workspace, array $extraFiles = []): void
{
    foreach ($extraFiles as $file) if (is_string($file) && is_file($file)) @unlink($file);
    $root = realpath(private_storage_root());
    $resolved = realpath($workspace);
    if (!$root || !$resolved || strncmp($resolved, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0) return;
    foreach (glob($resolved . DIRECTORY_SEPARATOR . '*') ?: [] as $file) if (is_file($file)) @unlink($file);
    @rmdir($resolved);
}