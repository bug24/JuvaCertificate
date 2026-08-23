<?php

function partial_date_precision(?string $value): ?string
{
    $value = trim((string) $value);
    if (preg_match('/^\d{4}$/', $value) === 1) return 'year';
    if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches) === 1 && (int) $matches[2] >= 1 && (int) $matches[2] <= 12) return 'month';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? 'day' : null;
    }
    return null;
}

function valid_partial_date(?string $value): bool { return partial_date_precision($value) !== null; }

function format_juva_partial_date(?string $value): string
{
    $value = trim((string) $value);
    $precision = partial_date_precision($value);
    if ($precision === 'year') return $value;
    if ($precision === 'month') return substr($value, 5, 2) . '/' . substr($value, 0, 4);
    if ($precision === 'day') return substr($value, 8, 2) . '/' . substr($value, 5, 2) . '/' . substr($value, 0, 4);
    return $value;
}

function supports_partial_historical_date_key(string $key): bool
{
    return in_array(strtolower(trim($key)), [
        'date_of_manufacture', 'manufacture_date', 'manufacture_date_value',
        'date_of_previous_examination', 'previous_examination_date',
        'date_of_last_examination', 'last_thorough_examination_date', 'date_last_examined',
    ], true);
}
