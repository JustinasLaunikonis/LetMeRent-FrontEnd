<?php

function esc($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatSourceLabel($source)
{
    $normalized = strtolower(trim($source));

    if ($normalized === 'irentalize') {
        return 'iRentalize';
    }

    if ($normalized === 'housinganywhere') {
        return 'HousingAnywhere';
    }

    if ($source !== '') {
        return ucfirst($source);
    }

    return 'Unknown';
}

function formatMoney($value)
{
    if ($value === null || $value === '') {
        return '&mdash;';
    }

    if (is_numeric($value)) {
        return '&euro;' . number_format((float) $value, 0, ',', '.');
    }

    $text = trim((string) $value);
    if ($text === '') {
        return '&mdash;';
    }

    if (str_starts_with($text, '€')) {
        return esc($text);
    }

    return '&euro;' . esc($text);
}

function formatDateValue($value)
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return $text;
    }

    return date('M j, Y', $timestamp);
}

function truncateText($value, $limit)
{
    if (strlen($value) <= $limit) {
        return $value;
    }

    $shortLimit = $limit - 1;
    if ($shortLimit < 0) {
        $shortLimit = 0;
    }

    return substr($value, 0, $shortLimit) . '...';
}

function joinNatural($items)
{
    $filtered = [];

    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $filtered[] = $item;
        }
    }

    if ($filtered === []) {
        return '';
    }

    if (count($filtered) === 1) {
        return $filtered[0];
    }

    $last = array_pop($filtered);

    return implode(', ', $filtered) . ' and ' . $last;
}

function summarizeList($values, $limit = 3)
{
    $items = [];

    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value !== '' && !in_array($value, $items, true)) {
            $items[] = $value;
        }
    }

    if ($items === []) {
        return '';
    }

    return joinNatural(array_slice($items, 0, $limit));
}
