<?php

require_once __DIR__ . '/../mappers/general_lifting_accessory_mapper.php';
require_once __DIR__ . '/eye_bolt.php';

function general_lifting_accessory_render_certificate_pdf(string $path, array $payload): void
{
    eye_bolt_render_certificate_pdf($path, $payload);
}
