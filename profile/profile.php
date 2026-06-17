<?php
declare(strict_types=1);

require_once __DIR__ . '/../sign-up-in/authApi.php';

startAuthSession();

if (empty($_SESSION['user']) && empty($_SESSION['access_token'])) {
    redirectTo('../sign-up-in/signin.php');
}

$meResult = callApiWithAuth('GET', '/me');

if ($meResult['status'] === 401 || $meResult['status'] === 403) {
    $_SESSION = [];
    redirectTo('../sign-up-in/signin.php');
}

$profileData = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];

if ($meResult['ok']) {
    $apiData = $meResult['data'];

    if (isset($apiData['user']) && is_array($apiData['user'])) {
        $profileData = $apiData['user'];
    } elseif (isset($apiData['data']['user']) && is_array($apiData['data']['user'])) {
        $profileData = $apiData['data']['user'];
    } elseif (isset($apiData['data']) && is_array($apiData['data'])) {
        $profileData = $apiData['data'];
    } elseif (!empty($apiData)) {
        $profileData = $apiData;
    }

    $_SESSION['user'] = $profileData;
}

function profileValue(array $profileData, array $keys, string $fallback = ''): string
{
    foreach ($keys as $key) {
        if (isset($profileData[$key]) && trim((string) $profileData[$key]) !== '') {
            return trim((string) $profileData[$key]);
        }
    }

    return $fallback;
}

function profileInitials(string $displayName, string $email): string
{
    $source = trim($displayName) !== '' ? $displayName : $email;
    $parts = preg_split('/\s+/', trim($source));

    if (!$parts || $parts[0] === '') {
        return 'U';
    }

    $initials = strtoupper(substr($parts[0], 0, 1));

    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }

    return $initials;
}

$firstName = profileValue($profileData, ['firstName', 'first_name']);
$lastName = profileValue($profileData, ['lastName', 'last_name']);
$fullName = trim($firstName . ' ' . $lastName);
$displayName = profileValue($profileData, ['name', 'fullName', 'full_name', 'username'], $fullName);
$email = profileValue($profileData, ['email']);
$university = profileValue($profileData, ['university', 'school', 'campus'], 'University not set');
$avatarInitials = profileInitials($displayName, $email);

function callChronoApi(string $method, string $endpoint, ?array $payload = null, bool $payloadAsQuery = false): array
{
    if ($payloadAsQuery && $payload !== null) {
        $queryPayload = [];

        foreach ($payload as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $queryPayload[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        $queryString = http_build_query($queryPayload);

        if ($queryString !== '') {
            $endpoint .= (str_contains($endpoint, '?') ? '&' : '?') . $queryString;
        }
    }

    $url = chronoApiBaseUrl() . $endpoint;
    $ch = curl_init($url);
    $method = strtoupper($method);
    $headers = authHeaders();

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($payload !== null && !$payloadAsQuery) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => [],
            'error' => 'Could not connect to preferences server at ' . $url . ': ' . $curlError,
        ];
    }

    $data = json_decode($responseBody, true);

    return [
        'ok' => $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'data' => is_array($data) ? $data : [],
        'error' => is_array($data) ? ($data['message'] ?? $data['error'] ?? ('HTTP ' . $statusCode . ' from ' . $url)) : ('HTTP ' . $statusCode . ' from ' . $url),
    ];
}

function nullableIntFromPost(string $key): ?int
{
    $rawValue = trim((string) ($_POST[$key] ?? ''));

    if ($rawValue === '') {
        return null;
    }

    $value = filter_var($rawValue, FILTER_VALIDATE_INT);

    return $value === false ? null : $value;
}

function nullableStringFromPost(string $key): ?string
{
    $value = trim((string) ($_POST[$key] ?? ''));

    return $value === '' ? null : $value;
}

function spiderLabels(): array
{
    return [
        'kamernet' => 'Kamernet',
        'funda' => 'Funda',
        'housinganywhere' => 'HousingAnywhere',
        'huurwoningen' => 'Huurwoningen',
        'irentalize' => 'iRentalize',
    ];
}

function nullableStringListFromPost(string $key, array $defaultValues = []): ?string
{
    $rawValue = $_POST[$key] ?? null;

    if ($rawValue === null) {
        return $defaultValues === [] ? null : implode(',', $defaultValues);
    }

    $values = is_array($rawValue) ? $rawValue : explode(',', (string) $rawValue);
    $normalizedValues = [];

    foreach ($values as $value) {
        $value = trim((string) $value);

        if ($value !== '' && !in_array($value, $normalizedValues, true)) {
            $normalizedValues[] = $value;
        }
    }

    if ($normalizedValues === []) {
        return $defaultValues === [] ? null : implode(',', $defaultValues);
    }

    return implode(',', $normalizedValues);
}

function nullableBoolFromPost(string $key): ?bool
{
    $value = nullableStringFromPost($key);

    if ($value === null) {
        return null;
    }

    return $value === 'true';
}

function preferenceValue(array $preferences, string $key, mixed $fallback): mixed
{
    return array_key_exists($key, $preferences) && $preferences[$key] !== null ? $preferences[$key] : $fallback;
}

function normalizePreferences(array $data): array
{
    $keyMap = [
        'spider' => 'spider',
        'city' => 'city',
        'time_between_scrap' => 'time_between_scrap',
        'timeBetweenScrap' => 'time_between_scrap',
        'scrape_interval' => 'time_between_scrap',
        'scrapeInterval' => 'time_between_scrap',
        'university_campus' => 'university_campus',
        'universityCampus' => 'university_campus',
        'campus' => 'university_campus',
        'min_budget' => 'min_budget',
        'minBudget' => 'min_budget',
        'max_budget' => 'max_budget',
        'maxBudget' => 'max_budget',
        'move_in_date' => 'move_in_date',
        'moveInDate' => 'move_in_date',
        'min_lease_length' => 'min_lease_length',
        'minLeaseLength' => 'min_lease_length',
        'max_distance_from_campus' => 'max_distance_from_campus',
        'maxDistanceFromCampus' => 'max_distance_from_campus',
        'room_type' => 'room_type',
        'roomType' => 'room_type',
        'furnishing' => 'furnishing',
        'pet_friendly' => 'pet_friendly',
        'petFriendly' => 'pet_friendly',
    ];

    $preferences = [];

    foreach ($keyMap as $sourceKey => $targetKey) {
        if (array_key_exists($sourceKey, $data)) {
            $preferences[$targetKey] = $data[$sourceKey];
        }
    }

    return $preferences;
}

function extractComparableTime(array $data): int
{
    foreach (['updated_at', 'updatedAt', 'created_at', 'createdAt', 'created', 'date'] as $key) {
        if (!empty($data[$key])) {
            $timestamp = strtotime((string) $data[$key]);

            if ($timestamp !== false) {
                return $timestamp;
            }
        }
    }

    foreach (['id', '_id'] as $key) {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return (int) $data[$key];
        }
    }

    return 0;
}

function collectPreferenceCandidates(array $data, array &$candidates): void
{
    if (array_is_list($data)) {
        foreach ($data as $item) {
            if (is_array($item)) {
                collectPreferenceCandidates($item, $candidates);
            }
        }

        return;
    }

    $preferences = normalizePreferences($data);

    if ($preferences !== []) {
        $candidates[] = [
            'preferences' => $preferences,
            'time' => extractComparableTime($data),
            'index' => count($candidates),
        ];
    }

    foreach ($data as $value) {
        if (is_array($value)) {
            collectPreferenceCandidates($value, $candidates);
        }
    }
}

function extractPreferences(array $taskData): array
{
    $candidates = [];

    collectPreferenceCandidates($taskData, $candidates);

    if ($candidates === []) {
        return [];
    }

    usort(
        $candidates,
        static fn (array $a, array $b): int => ($a['time'] <=> $b['time']) ?: ($a['index'] <=> $b['index'])
    );

    return $candidates[count($candidates) - 1]['preferences'];
}

function boolPreferenceValue(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string) $value));

    return in_array($normalized, ['1', 'true', 'yes', 'required'], true);
}

function selectedAttr(mixed $actual, mixed $expected): string
{
    return (string) $actual === (string) $expected ? ' selected' : '';
}

function preferenceListValue(array $preferences, string $key): array
{
    $value = preferenceValue($preferences, $key, []);
    $values = is_array($value) ? $value : explode(',', (string) $value);
    $normalizedValues = [];

    foreach ($values as $item) {
        $item = trim((string) $item);

        if ($item !== '' && !in_array($item, $normalizedValues, true)) {
            $normalizedValues[] = $item;
        }
    }

    return $normalizedValues;
}

function checkedAttr(array $actual, string $expected): string
{
    return in_array($expected, $actual, true) ? ' checked' : '';
}

$preferenceNotice = null;
$preferenceError = null;
$preferences = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'preferences') {
    if ($email === '') {
        $preferenceError = 'Could not save preferences because your profile email is missing.';
    } else {
        $preferencesPayload = [
            'user' => $email,
            'spider' => nullableStringListFromPost('spider', array_keys(spiderLabels())),
            'city' => nullableStringFromPost('city'),
            'university_campus' => nullableStringFromPost('university_campus'),
            'min_budget' => nullableIntFromPost('min_budget'),
            'max_budget' => nullableIntFromPost('max_budget'),
            'move_in_date' => nullableStringFromPost('move_in_date'),
            'min_lease_length' => nullableIntFromPost('min_lease_length'),
            'max_distance_from_campus' => nullableIntFromPost('max_distance_from_campus'),
            'room_type' => nullableStringFromPost('room_type'),
            'furnishing' => nullableStringFromPost('furnishing'),
            'pet_friendly' => nullableBoolFromPost('pet_friendly'),
        ];

        $saveResult = callChronoApi('POST', '/chrono/tasks', $preferencesPayload);

        if ($saveResult['ok']) {
            $preferences = $preferencesPayload;
            $_SESSION['preferences'] = $preferencesPayload;
            $preferenceNotice = 'Preferences saved.';
        } else {
            $preferences = $preferencesPayload;
            $preferenceError = $saveResult['error'] ?? 'Could not save preferences.';
        }
    }
}

if ($preferences === [] && $email !== '') {
    $taskResult = callChronoApi('GET', '/chrono/tasks/user/' . rawurlencode($email));

    if ($taskResult['ok']) {
        $taskData = $taskResult['data'];

        $preferences = extractPreferences($taskData);
        if ($preferences !== []) {
            $_SESSION['preferences'] = $preferences;
        }
    } elseif ($taskResult['status'] !== 404) {
        $preferenceError = $taskResult['error'] ?? 'Could not load preferences.';
    }
} elseif ($email === '') {
    $preferenceError = 'Could not load preferences because /me did not return an email.';
}

if ($preferences === [] && is_array($_SESSION['preferences'] ?? null)) {
    $preferences = $_SESSION['preferences'];
}

$hasPreferences = $preferences !== [];
$selectedSpiders = preferenceListValue($preferences, 'spider');
$spiderLabels = spiderLabels();
$selectedSpiderLabel = 'Any source';

if ($selectedSpiders !== []) {
    $selectedSpiderLabels = [];

    foreach ($selectedSpiders as $spider) {
        if (isset($spiderLabels[$spider])) {
            $selectedSpiderLabels[] = $spiderLabels[$spider];
        }
    }

    if ($selectedSpiderLabels !== []) {
        $selectedSpiderLabel = implode(', ', $selectedSpiderLabels);
    }
}
$selectedCity = preferenceValue($preferences, 'city', '');
$selectedCampus = preferenceValue($preferences, 'university_campus', '');

// The university is not stored on the user account.
// It is saved in the search preferences instead.
// So if the profile did not give us a university, fall back to the campus the user picked in their preferences.
if ($university === 'University not set' && trim((string) $selectedCampus) !== '') {
    $university = (string) $selectedCampus;
}
$selectedMinBudget = preferenceValue($preferences, 'min_budget', '');
$selectedMaxBudget = preferenceValue($preferences, 'max_budget', '');
$selectedMoveInDate = preferenceValue($preferences, 'move_in_date', '');
$selectedLeaseLength = preferenceValue($preferences, 'min_lease_length', '');

// The "Min lease length" field uses the same combobox UI as University / Campus.
// These are the choices it shows, and we work out which label matches the saved value.
$leaseLengthOptions = [
    ['value' => '12', 'label' => '12 months'],
    ['value' => '6', 'label' => '6 months'],
    ['value' => '3', 'label' => '3 months'],
    ['value' => '0', 'label' => 'Any'],
];

$selectedLeaseLabel = '';
foreach ($leaseLengthOptions as $leaseOption) {
    if ((string) $leaseOption['value'] === (string) $selectedLeaseLength) {
        $selectedLeaseLabel = $leaseOption['label'];
    }
}

$selectedDistance = preferenceValue($preferences, 'max_distance_from_campus', '');
$selectedRoomType = preferenceValue($preferences, 'room_type', '');
$selectedFurnishing = preferenceValue($preferences, 'furnishing', '');
$selectedPetFriendlyRaw = preferenceValue($preferences, 'pet_friendly', '');
$selectedPetFriendly = $selectedPetFriendlyRaw === '' ? '' : (boolPreferenceValue($selectedPetFriendlyRaw) ? 'true' : 'false');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetMeRent - Profile & Alerts</title>
  <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2">
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="profile.css">
</head>

<body>
  <?php
  // Top navbar. <alerts> is the current page. The avatar shows the users initials.
  $navBase = '../';
  $navActive = 'alerts';
  $navProfileHref = 'profile.php';
  $navAvatar = $avatarInitials;
  include __DIR__ . '/../includes/nav.php';
  ?>

  <!-- Sidebar -->
  <div class="profile-wrap">
    <div>
      <div class="profile-card">
        <div class="profile-avatar">
          <p><?php echo htmlspecialchars($avatarInitials); ?></p>
        </div>

        <div class="profile-name">
          <p><?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Profile'); ?></p>
        </div>

        <div class="profile-uni">
          <p><?php echo htmlspecialchars($university); ?></p>
        </div>

        <?php if ($email !== ''): ?>
          <div class="profile-email">
            <p><?php echo htmlspecialchars($email); ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="side-menu">
        <a class="side-menu-item active" href="profile.php">
          <span class="side-menu-icon">👤</span>
          <p>My Profile</p>
        </a>

        <a class="side-menu-item" href="../index.php">
          <span class="side-menu-icon">🏘️</span>
          <p>Browse listings</p>
        </a>

        <a class="side-menu-item" href="../sign-up-in/logout.php">
          <span class="side-menu-icon">🚪</span>
          <p>Sign out</p>
        </a>
      </div>
    </div>

    <div>
      <form class="form-card" method="post" action="profile.php">
        <input type="hidden" name="form" value="preferences">
        <div class="form-head">
          <div class="form-head-title">
            <p>Search Preferences</p>
          </div>

          <div class="form-head-status">
            <p>● Profile active</p>
          </div>
        </div>

        <?php if ($preferenceNotice !== null): ?>
          <div class="preference-message preference-message--success">
            <p><?php echo htmlspecialchars($preferenceNotice); ?></p>
          </div>
        <?php endif; ?>

        <?php if ($preferenceError !== null): ?>
          <div class="preference-message preference-message--error">
            <p><?php echo htmlspecialchars($preferenceError); ?></p>
          </div>
        <?php endif; ?>

        <!-- City Selection -->
        <div class="form-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">City</label>
              <div class="city-combobox">
                <input
                  class="form-input"
                  type="text"
                  id="city-search"
                  value="<?php echo htmlspecialchars((string) $selectedCity); ?>"
                  autocomplete="off"
                  placeholder="Type a city"
                  role="combobox"
                  aria-autocomplete="list"
                  aria-expanded="false"
                  aria-controls="city-options"
                  aria-busy="true"
                >
                <input type="hidden" name="city" id="city-select" value="<?php echo htmlspecialchars((string) $selectedCity); ?>">
                <div class="city-options" id="city-options" role="listbox"></div>
              </div>
            </div>

            <!-- Uni Selection -->
            <div class="form-group">
              <label class="form-label">University / Campus</label>
              <div class="city-combobox">
                <input
                  class="form-input"
                  type="text"
                  id="campus-search"
                  value="<?php echo htmlspecialchars((string) $selectedCampus); ?>"
                  autocomplete="off"
                  placeholder="Select a university"
                  role="combobox"
                  aria-autocomplete="list"
                  aria-expanded="false"
                  aria-controls="campus-options"
                  aria-busy="true"
                >
                <input type="hidden" name="university_campus" id="campus-select" value="<?php echo htmlspecialchars((string) $selectedCampus); ?>">
                <div class="city-options" id="campus-options" role="listbox"></div>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Source</label>
              <details class="source-dropdown">
                <summary>
                  <span class="source-dropdown-label" data-empty-label="Any source">
                    <?php echo htmlspecialchars($selectedSpiderLabel); ?>
                  </span>
                </summary>
                <div class="source-options" role="group" aria-label="Source">
                  <?php foreach ($spiderLabels as $spiderValue => $spiderLabel): ?>
                    <label class="source-option">
                      <input type="checkbox" name="spider[]" value="<?php echo htmlspecialchars($spiderValue); ?>"<?php echo checkedAttr($selectedSpiders, $spiderValue); ?>>
                      <span><?php echo htmlspecialchars($spiderLabel); ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </details>
            </div>

            <div class="form-group">
              <label class="form-label">Pet-friendly</label>
              <select class="form-input form-select" name="pet_friendly">
                <option value=""<?php echo selectedAttr($selectedPetFriendly, ''); ?>></option>
                <option value="true"<?php echo selectedAttr($selectedPetFriendly, 'true'); ?>>Required</option>
                <option value="false"<?php echo selectedAttr($selectedPetFriendly, 'false'); ?>>Not needed</option>
              </select>
            </div>
          </div>

          <!-- Budget Selection -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Min budget (€/mo)</label>
              <div class="budget-field">
                <div class="budget-input-row">
                  <span class="budget-currency">&euro;</span>
                  <input
                    class="budget-number-input"
                    id="min-budget-input"
                    type="number"
                    min="0"
                    max="5000"
                    step="1"
                    value="<?php echo $selectedMinBudget === '' ? '0' : htmlspecialchars((string) $selectedMinBudget); ?>"
                  >
                  <span class="budget-period">/ mo</span>
                </div>
                <input
                  class="budget-slider"
                  id="min-budget-slider"
                  type="range"
                  min="0"
                  max="5000"
                  step="50"
                  value="<?php echo $selectedMinBudget === '' ? '0' : htmlspecialchars((string) $selectedMinBudget); ?>"
                  aria-label="Min budget slider"
                >
                <input type="hidden" name="min_budget" id="min-budget-hidden" value="<?php echo htmlspecialchars((string) $selectedMinBudget); ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Max budget (€/mo)</label>
              <div class="budget-field">
                <div class="budget-input-row">
                  <span class="budget-currency">&euro;</span>
                  <input
                    class="budget-number-input"
                    id="max-budget-input"
                    type="number"
                    name="max_budget"
                    min="0"
                    max="5000"
                    step="1"
                    placeholder="5000+"
                    value="<?php echo htmlspecialchars((string) $selectedMaxBudget); ?>"
                  >
                  <span class="budget-period">/ mo</span>
                </div>
                <input
                  class="budget-slider"
                  id="max-budget-slider"
                  type="range"
                  min="0"
                  max="5000"
                  step="50"
                  value="<?php echo $selectedMaxBudget === '' ? '5000' : htmlspecialchars((string) $selectedMaxBudget); ?>"
                  aria-label="Max budget slider"
                >
              </div>
            </div>
          </div>

          <!-- Move-in Date Selection -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Move-in date</label>
              <div class="move-in-field">
                <input
                  class="move-in-input"
                  id="move-in-input"
                  type="date"
                  name="move_in_date"
                  value="<?php echo htmlspecialchars((string) $selectedMoveInDate); ?>"
                >
                <button class="move-in-clear" id="move-in-clear" type="button">Any date</button>
              </div>
            </div>

            <!-- Lease Length Selection -->
            <div class="form-group">
              <label class="form-label">Min lease length</label>
              <!-- Same combobox UI as University / Campus: a box that opens a list of choices. -->
              <div class="city-combobox">
                <input
                  class="form-input"
                  type="text"
                  id="lease-search"
                  value="<?php echo htmlspecialchars($selectedLeaseLabel); ?>"
                  autocomplete="off"
                  placeholder="Select lease length"
                  readonly
                  role="combobox"
                  aria-autocomplete="list"
                  aria-expanded="false"
                  aria-controls="lease-options"
                >
                <input type="hidden" name="min_lease_length" id="lease-select" value="<?php echo htmlspecialchars((string) $selectedLeaseLength); ?>">
                <div class="city-options" id="lease-options" role="listbox">
                  <?php foreach ($leaseLengthOptions as $leaseOption): ?>
                    <button
                      type="button"
                      class="city-option"
                      role="option"
                      data-value="<?php echo htmlspecialchars($leaseOption['value']); ?>"
                    ><?php echo htmlspecialchars($leaseOption['label']); ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Distance Slider -->
          <div class="slider-group">
            <div class="slider-top">
              <div class="slider-label">Max distance from campus</div>
              <div class="slider-value" id="distance-value"><?php echo $selectedDistance === '' ? '20+ km' : htmlspecialchars((string) $selectedDistance) . ' km'; ?></div>
            </div>

            <input
              class="budget-slider"
              id="distance-slider"
              type="range"
              min="0"
              max="20"
              step="1"
              value="<?php echo $selectedDistance === '' ? '20' : htmlspecialchars((string) $selectedDistance); ?>"
              aria-label="Max distance from campus slider"
            >
            <input type="hidden" name="max_distance_from_campus" id="distance-input" value="<?php echo htmlspecialchars((string) $selectedDistance); ?>">

            <div class="slider-ticks">
              <span>0</span>
              <span>5 km</span>
              <span>10 km</span>
              <span>15 km</span>
              <span>20 km</span>
            </div>
          </div>


          <!-- Room Type Selection -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Room type</label>
              <select class="form-input form-select" name="room_type">
                <option value=""<?php echo selectedAttr($selectedRoomType, ''); ?>></option>
                <option value="studio_or_room"<?php echo selectedAttr($selectedRoomType, 'studio_or_room'); ?>>Studio or Room</option>
                <option value="studio"<?php echo selectedAttr($selectedRoomType, 'studio'); ?>>Studio only</option>
                <option value="room"<?php echo selectedAttr($selectedRoomType, 'room'); ?>>Room (shared)</option>
                <option value="apartment"<?php echo selectedAttr($selectedRoomType, 'apartment'); ?>>1-bed apartment</option>
                <option value="any"<?php echo selectedAttr($selectedRoomType, 'any'); ?>>Any</option>
              </select>
            </div>

            <!-- Furnishing Selection -->
            <div class="form-group">
              <label class="form-label">Furnishing</label>
              <select class="form-input form-select" name="furnishing">
                <option value=""<?php echo selectedAttr($selectedFurnishing, ''); ?>></option>
                <option value="furnished"<?php echo selectedAttr($selectedFurnishing, 'furnished'); ?>>Furnished required</option>
                <option value="furnished_preferred"<?php echo selectedAttr($selectedFurnishing, 'furnished_preferred'); ?>>Furnished preferred</option>
                <option value="unfurnished"<?php echo selectedAttr($selectedFurnishing, 'unfurnished'); ?>>Unfurnished only</option>
                <option value="any"<?php echo selectedAttr($selectedFurnishing, 'any'); ?>>Any</option>
              </select>
            </div>
          </div>

          <!-- Instant Alerts -->
          <div class="toggle-row">
            <div>
              <div class="toggle-title">
                <p>Instant alerts — new matches</p>
              </div>

              <div class="toggle-sub">
                <p>Email the moment a listing matching your profile goes live</p>
              </div>
            </div>

            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="form-footer">
          <button class="btn-ghost" type="button" id="reset-preferences">Reset</button>
          <button class="btn-primary" type="submit">Save preferences</button>
        </div>
      </form>
    </div>
  </div>
  <script src="../components/indexPage/cityGeoData.js"></script>
  <script src="profile.js"></script>
</body>
</html>
