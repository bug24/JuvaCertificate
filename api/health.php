<?php

require __DIR__ . '/_bootstrap.php';

try {
    db()->query('SELECT 1');
    respond([
        'status' => 'ok',
    ]);
} catch (Throwable $error) {
    respond([
        'status' => 'degraded',
    ], 503);
}
