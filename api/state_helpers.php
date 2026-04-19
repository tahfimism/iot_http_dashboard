<?php

function normalize_type_label($type) {
    $candidate = strtolower(trim((string)$type));
    if ($candidate === 'binary' || $candidate === 'number' || $candidate === 'text') {
        return $candidate;
    }
    return '';
}

function infer_type_from_value($value) {
    if (is_bool($value)) {
        return 'binary';
    }
    if (is_int($value) || is_float($value)) {
        return 'number';
    }
    return 'text';
}

function normalize_typed_state($decodedState) {
    $typedState = [];
    if (!is_array($decodedState)) {
        return $typedState;
    }

    foreach ($decodedState as $key => $entry) {
        if (is_array($entry) && array_key_exists('value', $entry)) {
            $entryType = normalize_type_label($entry['type'] ?? '');
            if ($entryType === '') {
                $entryType = infer_type_from_value($entry['value']);
            }
            $typedState[$key] = [
                'value' => $entry['value'],
                'type' => $entryType,
                'source' => $entry['source'] ?? ''
            ];
        } else {
            $typedState[$key] = [
                'value' => $entry,
                'type' => infer_type_from_value($entry),
                'source' => ''
            ];
        }
    }

    return $typedState;
}

function flatten_typed_state($typedState) {
    $flat = [];
    if (!is_array($typedState)) {
        return $flat;
    }

    foreach ($typedState as $key => $entry) {
        if (is_array($entry) && array_key_exists('value', $entry)) {
            $flat[$key] = $entry['value'];
        }
    }

    return $flat;
}

function cast_value_by_type($rawValue, $type, &$isValid) {
    $isValid = true;

    if ($type === 'binary') {
        $normalized = strtolower(trim((string)$rawValue));
        if ($normalized === '1' || $normalized === 'true' || $normalized === 'on') {
            return 1;
        }
        if ($normalized === '0' || $normalized === 'false' || $normalized === 'off') {
            return 0;
        }
        $isValid = false;
        return null;
    }

    if ($type === 'number') {
        if (is_numeric($rawValue)) {
            return $rawValue + 0;
        }
        $isValid = false;
        return null;
    }

    return (string)$rawValue;
}

?>