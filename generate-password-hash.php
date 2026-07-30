<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=UTF-8');

    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    if ($password === '') {
        http_response_code(422);
        exit;
    }

    echo password_hash($password, PASSWORD_DEFAULT);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Generate Password Hash</title>
</head>
<body>
    <p><strong>Delete this file immediately after copying your generated hash.</strong></p>
    <form method="post" autocomplete="off">
        <label>
            Password
            <input type="password" name="password" required autofocus>
        </label>
        <button type="submit">Generate hash</button>
    </form>
</body>
</html>
