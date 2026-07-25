<?php

declare(strict_types=1);

function normalize_field_options(string $fieldType, $options, array &$errors): ?string
{
    if ($options === null || $options === '' || $options === []) {
        return null;
    }
    if (!in_array($fieldType, ['select', 'checkbox'], true)) {
        $errors['options'] = 'Options are only allowed for select and checkbox fields.';
        return null;
    }
    if (!is_array($options)) {
        $errors['options'] = 'Options must be a list of plain-text values.';
        return null;
    }
    if (count($options) > 50) {
        $errors['options'] = 'Use 50 options or fewer.';
        return null;
    }
    $clean = [];
    foreach ($options as $option) {
        $value = normalize_text_input((string) $option);
        if ($value === '' || contains_markup($value) || text_length($value) > 120) {
            $errors['options'] = 'Each option must be plain text up to 120 characters.';
            return null;
        }
        $clean[] = $value;
    }
    return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_SLASHES);
}

function normalize_field_validation($validation, array &$errors): ?string
{
    if ($validation === null || $validation === '' || $validation === []) {
        return null;
    }
    if (!is_array($validation)) {
        $errors['validation'] = 'Validation rules must be a JSON object.';
        return null;
    }
    $allowed = ['min', 'max', 'min_length', 'max_length', 'pattern'];
    $clean = [];
    foreach ($validation as $key => $value) {
        if (!in_array((string) $key, $allowed, true)) {
            $errors['validation'] = 'Validation contains an unsupported rule.';
            return null;
        }
        if (is_array($value) || is_object($value)) {
            $errors['validation'] = 'Validation rules must be scalar values only.';
            return null;
        }
        if ($key === 'pattern') {
            $pattern = normalize_text_input((string) $value);
            if ($pattern === '' || text_length($pattern) > 120 || contains_markup($pattern)) {
                $errors['validation'] = 'Pattern validation must be plain text up to 120 characters.';
                return null;
            }
            $clean[$key] = $pattern;
            continue;
        }
        if (!is_numeric($value)) {
            $errors['validation'] = 'Numeric validation rules must use numbers.';
            return null;
        }
        $clean[$key] = (float) $value;
    }
    return json_encode($clean, JSON_UNESCAPED_SLASHES);
}
