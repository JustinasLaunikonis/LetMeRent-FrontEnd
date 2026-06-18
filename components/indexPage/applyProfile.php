<?php
// Builds the "Apply profile settings" button shown in the results bar.

// Clicking the button reloads the page with those filters applied.

// This include sets two variables for resultsBar.php to use:
//   $hasProfileFilters  - true when we have at least one filter to apply
//   $applyProfileHref   - the index.php link with the profile filters in it

require_once __DIR__ . '/../../sign-up-in/authApi.php';

// Ask the Chrono service for the saved tasks (preferences) of one user.
function applyProfileCallChrono($email)
{
    $endpoint = '/chrono/tasks/user/' . rawurlencode($email);
    $url = chronoApiBaseUrl() . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, authHeaders());
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // On any connection problem just return an empty list, so the page still works.
    if ($responseBody === false) {
        return array();
    }

    $data = json_decode($responseBody, true);

    if ($statusCode < 200 || $statusCode >= 300) {
        return array();
    }

    if (is_array($data)) {
        return $data;
    }

    return array();
}

// The Chrono task can store the same setting under different key names (for example "max_budget" or "maxBudget").
function applyProfileNormalize($data)
{
    $keyMap = array(
        'spider' => 'spider',
        'city' => 'city',
        'min_budget' => 'min_budget',
        'minBudget' => 'min_budget',
        'max_budget' => 'max_budget',
        'maxBudget' => 'max_budget',
        'move_in_date' => 'move_in_date',
        'moveInDate' => 'move_in_date',
    );

    $preferences = array();

    foreach ($keyMap as $sourceKey => $targetKey) {
        if (array_key_exists($sourceKey, $data)) {
            $preferences[$targetKey] = $data[$sourceKey];
        }
    }

    return $preferences;
}

// Work out a number we can use to tell which task is the newest.
function applyProfileTime($data)
{
    $dateKeys = array('updated_at', 'updatedAt', 'created_at', 'createdAt', 'created', 'date');

    foreach ($dateKeys as $key) {
        if (!empty($data[$key])) {
            $timestamp = strtotime((string) $data[$key]);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }
    }

    return 0;
}

// Walk through the response (which may be a list, or have tasks nested inside)
// and collect every place that looks like a set of preferences.
function applyProfileCollect($data, &$candidates)
{
    // A plain list: look inside each item.
    if (array_is_list($data)) {
        foreach ($data as $item) {
            if (is_array($item)) {
                applyProfileCollect($item, $candidates);
            }
        }
        return;
    }

    // A normal array: it might be a preferences set itself.
    $preferences = applyProfileNormalize($data);
    if ($preferences !== array()) {
        $candidates[] = array(
            'preferences' => $preferences,
            'time' => applyProfileTime($data),
            'index' => count($candidates),
        );
    }

    // It might also hold more tasks nested deeper, so keep looking.
    foreach ($data as $value) {
        if (is_array($value)) {
            applyProfileCollect($value, $candidates);
        }
    }
}

// Pick the newest preferences out of everything we found.
function applyProfileExtract($taskData)
{
    $candidates = array();
    applyProfileCollect($taskData, $candidates);

    if ($candidates === array()) {
        return array();
    }

    // Keep the candidate with the latest time. If two share a time, keep the one that was found last.
    $best = $candidates[0];
    for ($i = 1; $i < count($candidates); $i++) {
        $current = $candidates[$i];
        if ($current['time'] > $best['time']) {
            $best = $current;
        } else if ($current['time'] === $best['time'] && $current['index'] > $best['index']) {
            $best = $current;
        }
    }

    return $best['preferences'];
}

// Read one preference value, or a fallback when it is missing.
function applyProfileValue($preferences, $key, $fallback)
{
    if (array_key_exists($key, $preferences) && $preferences[$key] !== null) {
        return $preferences[$key];
    }

    return $fallback;
}

// Read the "spider" (source) preference as a clean list of names.
function applyProfileSources($preferences)
{
    $value = applyProfileValue($preferences, 'spider', array());

    if (is_array($value)) {
        $parts = $value;
    } else {
        $parts = explode(',', (string) $value);
    }

    $sources = array();
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part !== '' && !in_array($part, $sources, true)) {
            $sources[] = $part;
        }
    }

    return $sources;
}

// Turn the saved preferences into the search filters the index page understands.
// We only include the filters that map cleanly onto the search bar.
function applyProfileBuildQuery($preferences)
{
    $query = array();

    $city = applyProfileValue($preferences, 'city', '');
    if (trim((string) $city) !== '') {
        $query['city'] = trim((string) $city);
    }

    $minBudget = applyProfileValue($preferences, 'min_budget', '');
    if ($minBudget !== '' && is_numeric($minBudget) && (int) $minBudget > 0) {
        $query['min_price'] = (int) $minBudget;
    }

    $maxBudget = applyProfileValue($preferences, 'max_budget', '');
    if ($maxBudget !== '' && is_numeric($maxBudget)) {
        $query['max_price'] = (int) $maxBudget;
    }

    $moveIn = applyProfileValue($preferences, 'move_in_date', '');
    if (trim((string) $moveIn) !== '') {
        $query['available_by'] = trim((string) $moveIn);
    }

    $sources = applyProfileSources($preferences);
    if (count($sources) > 0) {
        $query['source'] = implode(',', $sources);
    }

    return $query;
}

// -------------------------------------------------------------------------
// Work out the button for this page load.
// -------------------------------------------------------------------------
$hasProfileFilters = false;
$applyProfileHref = '';

// Find the logged-in users email from the session.
$profileEmail = '';
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['email'])) {
    $profileEmail = trim((string) $_SESSION['user']['email']);
}

// Only build the button when someone is logged in.
if ($profileEmail !== '') {
    $taskData = applyProfileCallChrono($profileEmail);
    $preferences = applyProfileExtract($taskData);
    $profileQuery = applyProfileBuildQuery($preferences);

    if (count($profileQuery) > 0) {
        $applyProfileHref = 'index.php?' . http_build_query($profileQuery);
        $hasProfileFilters = true;
    }
}
