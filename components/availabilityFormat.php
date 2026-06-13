<?php

// Month names (and the common short forms) mapped to their number.
// This matches MONTHS in the scraper's city_utils.py so the frontend reads
// dates the same way the scraper does.
function availabilityMonths() {
    return array(
        'jan' => 1, 'january' => 1,
        'feb' => 2, 'february' => 2,
        'mar' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'may' => 5,
        'jun' => 6, 'june' => 6,
        'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'oct' => 10, 'october' => 10,
        'nov' => 11, 'november' => 11,
        'dec' => 12, 'december' => 12,
    );
}

// Some listings give a date with no year, like "8 June". Pick the next time
// that day happens: this year if it is still coming up, otherwise next year.
function availabilityGuessYear($month, $day) {
    $todayYear = (int) date('Y');
    $candidate = mktime(0, 0, 0, $month, $day, $todayYear);
    $today = mktime(0, 0, 0, (int) date('n'), (int) date('j'), $todayYear);
    if ($candidate < $today) {
        return $todayYear + 1;
    }
    return $todayYear;
}

// Turn year/month/day numbers into friendly text like "From Aug 1, 2026".
// Returns "" when the numbers are not a real calendar date.
function availabilityDateText($year, $month, $day) {
    if (!checkdate($month, $day, $year)) {
        return '';
    }
    $timestamp = mktime(0, 0, 0, $month, $day, $year);
    return 'From ' . date('M j, Y', $timestamp);
}

// Turn the availability value into friendly text for the page.
//
// The scraper normally stores a clean value ("Immediately", "2026-08-01", a
// short phrase, or ""). But the database can still hold older raw values that
// were saved before the scraper normalised them, for example:
//   "Available on 7/1/2026", "From 01-09-2026", "8 Jun 2026",
//   "Available Immediately", "Available on Immediately".
// So we normalise here as well, using the same rules as the scraper's
// normalize_availability(), and every listing ends up reading the same way:
//   - words meaning "right away" -> "Available now"
//   - any recognisable date      -> "From Aug 1, 2026"
//   - a phrase with no date       -> shown as it is
//   - nothing usable              -> "" (hidden)
function formatAvailability($value) {
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $lowered = strtolower($text);

    // "Available now", "Per direct", "Immediately" and "ASAP" all mean the same.
    // We check this first so "Available on Immediately" also becomes "now".
    if (strpos($lowered, 'immediat') !== false
        || strpos($lowered, 'direct') !== false
        || strpos($lowered, 'asap') !== false
        || strpos($lowered, 'now') !== false) {
        return 'Available now';
    }

    $months = availabilityMonths();

    // Look for a 4-digit year anywhere in the text (it may be missing).
    $year = 0;
    if (preg_match('/(\d{4})/', $text, $yearMatch) === 1) {
        $year = (int) $yearMatch[1];
    }

    // 0) The clean normalised shape "YYYY-MM-DD", like "2026-08-01".
    //    This is what the scraper stores now, so it is the common case.
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $m) === 1) {
        $result = availabilityDateText((int) $m[1], (int) $m[2], (int) $m[3]);
        if ($result !== '') {
            return $result;
        }
    }

    // 1) A text month written as "<day> <month>", like "8 Jun 2026".
    if (preg_match('/(\d{1,2})\s+([A-Za-z]+)/', $text, $m) === 1) {
        $monthWord = strtolower($m[2]);
        if (isset($months[$monthWord])) {
            $day = (int) $m[1];
            $month = $months[$monthWord];
            if ($year === 0) {
                $year = availabilityGuessYear($month, $day);
            }
            $result = availabilityDateText($year, $month, $day);
            if ($result !== '') {
                return $result;
            }
        }
    }

    // 2) A text month written as "<month> <day>", like "June 8, 2026".
    if (preg_match('/([A-Za-z]+)\s+(\d{1,2})/', $text, $m) === 1) {
        $monthWord = strtolower($m[1]);
        if (isset($months[$monthWord])) {
            $month = $months[$monthWord];
            $day = (int) $m[2];
            if ($year === 0) {
                $year = availabilityGuessYear($month, $day);
            }
            $result = availabilityDateText($year, $month, $day);
            if ($result !== '') {
                return $result;
            }
        }
    }

    // 3) A numeric date with slashes. Funda uses American month/day/year,
    //    like "7/1/2026".
    if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $text, $m) === 1) {
        $month = (int) $m[1];
        $day = (int) $m[2];
        $dateYear = (int) $m[3];
        $result = availabilityDateText($dateYear, $month, $day);
        if ($result !== '') {
            return $result;
        }
    }

    // 4) A numeric date with dashes or dots. Huurwoningen uses Dutch
    //    day-month-year, like "01-09-2026".
    if (preg_match('/(\d{1,2})[-.](\d{1,2})[-.](\d{4})/', $text, $m) === 1) {
        $day = (int) $m[1];
        $month = (int) $m[2];
        $dateYear = (int) $m[3];
        $result = availabilityDateText($dateYear, $month, $day);
        if ($result !== '') {
            return $result;
        }
    }

    // Some listings only say "Available from" (or just "Available") with no
    // date after it. That means the place is free right now, so show it the
    // same as "Available now". We keep only the letters first, so trailing
    // spaces or punctuation like "Available from:" still match.
    $onlyLetters = preg_replace('/[^a-z]/', '', $lowered);
    if ($onlyLetters === 'available' || $onlyLetters === 'availablefrom') {
        return 'Available now';
    }

    // Nothing matched a date, so show the text as it is.
    return $text;
}
