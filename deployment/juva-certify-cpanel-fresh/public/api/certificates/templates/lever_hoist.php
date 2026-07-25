<?php
require_once __DIR__ . '/../mappers/lever_hoist_mapper.php';
require_once __DIR__ . '/endless_round_webbing_sling.php';

function lever_hoist_render_certificate_pdf(string $path, array $payload): void
{
    endless_round_webbing_sling_render_certificate_pdf($path, $payload);
}
