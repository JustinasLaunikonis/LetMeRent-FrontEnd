<?php
declare(strict_types=1);

require_once __DIR__ . '/../sign-up-in/auth_api.php';

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
$username = profileValue($profileData, ['username']);
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
    if ($username === '') {
        $preferenceError = 'Could not save preferences because your profile username is missing.';
    } else {
        $preferencesPayload = [
            'user' => $username,
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

if ($preferences === [] && $username !== '') {
    $taskResult = callChronoApi('GET', '/chrono/tasks/user/' . rawurlencode($username));

    if ($taskResult['ok']) {
        $taskData = $taskResult['data'];

        $preferences = extractPreferences($taskData);
        if ($preferences !== []) {
            $_SESSION['preferences'] = $preferences;
        }
    } elseif ($taskResult['status'] !== 404) {
        $preferenceError = $taskResult['error'] ?? 'Could not load preferences.';
    }
} elseif ($username === '') {
    $preferenceError = 'Could not load preferences because /me did not return a username.';
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
$selectedMinBudget = preferenceValue($preferences, 'min_budget', '');
$selectedMaxBudget = preferenceValue($preferences, 'max_budget', '');
$selectedMoveInDate = preferenceValue($preferences, 'move_in_date', '');
$selectedLeaseLength = preferenceValue($preferences, 'min_lease_length', '');
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
  <title>LetMeRent — Profile & Alerts</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="profile.css">
</head>

<body>
  <!-- Nav Bar -->
  <nav class="nav">
        <a class="nav-logo" href="../index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <!-- Let-Me-Rent Logo -->
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>

    <ul class="nav-links">
      <li><a href="../index.php">Browse</a></li>
      <li><a href="../map/map.php">Map View</a></li>
      <li><a href="profile.php" class="active">My Alerts</a></li>
    </ul>

    <div class="nav-right">
      <div class="nav-bell">🔔</div>
      <a href="profile.php" class="nav-avatar"><?php echo htmlspecialchars($avatarInitials); ?></a>
    </div>
  </nav>

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

        <div class="profile-stats">
          <div class="profile-stat">
            <div class="val">47</div>
            <div class="lbl">Saved</div>
          </div>

          <div class="profile-stat">
            <div class="val">8</div>
            <div class="lbl">Applied</div>
          </div>

          <div class="profile-stat">
            <div class="val">3</div>
            <div class="lbl">Alerts</div>
          </div>

          <div class="profile-stat">
            <div class="val">94%</div>
            <div class="lbl">Top match</div>
          </div>
        </div>
      </div>

      <div class="side-menu">
        <a class="side-menu-item active" href="profile.php">
          <span class="side-menu-icon">👤</span>
          <p>My Profile</p>
        </a>

        <a class="side-menu-item" href="profile.php">
          <span class="side-menu-icon">🔔</span>
          <p>Alert Settings</p>
        </a>

        <a class="side-menu-item" href="profile.php">
          <span class="side-menu-icon">🏠</span>
          <p>Preferences</p>
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
              <select
                class="form-input form-select"
                name="university_campus"
                id="campus-select"
                data-selected-campus="<?php echo htmlspecialchars((string) $selectedCampus); ?>"
                aria-busy="true"
              >
                <option value=""<?php echo selectedAttr($selectedCampus, ''); ?>></option>
                <?php if ($selectedCampus !== ''): ?>
                  <option value="<?php echo htmlspecialchars((string) $selectedCampus); ?>" selected>
                    <?php echo htmlspecialchars((string) $selectedCampus); ?>
                  </option>
                <?php endif; ?>
              </select>
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
              <input class="form-input" type="number" name="min_budget" min="0" step="50" value="<?php echo htmlspecialchars((string) $selectedMinBudget); ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Max budget (€/mo)</label>
              <input class="form-input" type="number" name="max_budget" min="0" step="50" value="<?php echo htmlspecialchars((string) $selectedMaxBudget); ?>">
            </div>
          </div>

          <!-- Move-in Date Selection -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Move-in date</label>
              <input class="form-input" type="date" name="move_in_date" value="<?php echo htmlspecialchars((string) $selectedMoveInDate); ?>">
            </div>

            <!-- Lease Length Selection -->
            <div class="form-group">
              <label class="form-label">Min lease length</label>
              <select class="form-input form-select" name="min_lease_length">
                <option value=""<?php echo selectedAttr($selectedLeaseLength, ''); ?>></option>
                <option value="12"<?php echo selectedAttr($selectedLeaseLength, 12); ?>>12 months</option>
                <option value="6"<?php echo selectedAttr($selectedLeaseLength, 6); ?>>6 months</option>
                <option value="3"<?php echo selectedAttr($selectedLeaseLength, 3); ?>>3 months</option>
                <option value="0"<?php echo selectedAttr($selectedLeaseLength, 0); ?>>Any</option>
              </select>
            </div>
          </div>

          <!-- Distance Slider -->
          <div class="slider-group">
            <div class="slider-top">
              <div class="slider-label">Max distance from campus</div>
              <div class="slider-value"><?php echo $selectedDistance === '' ? '' : htmlspecialchars((string) $selectedDistance) . ' km'; ?></div>
            </div>
            <input type="hidden" name="max_distance_from_campus" class="slider-input" value="<?php echo htmlspecialchars((string) $selectedDistance); ?>">

            <div class="slider-track">
              <div class="slider-fill"></div>
              <div class="slider-thumb"></div>
            </div>

            <div class="slider-ticks">
              <span>0</span>
              <span>5 km</span>
              <span>10 km</span>
              <span>15 km</span>
              <span>20 km</span>
            </div>
          </div>

          <script>
            const sliderGroup = document.querySelector('.slider-group');
            const sliderTrack = sliderGroup.querySelector('.slider-track');
            const sliderFill = sliderGroup.querySelector('.slider-fill');
            const sliderThumb = sliderGroup.querySelector('.slider-thumb');
            const sliderValue = sliderGroup.querySelector('.slider-value');
            const sliderInput = sliderGroup.querySelector('.slider-input');

            let isDragging = false;

            const min = 0;
            const max = 20;
            const step = 1; // optional, for discrete steps

            // Initialize slider
            function setSliderPosition(percent) {
              // Clamp percent between 0 and 1
              percent = Math.max(0, Math.min(1, percent));
              // Calculate value based on percent
              const value = Math.round(min + percent * (max - min));
              // Update fill width
              sliderFill.style.width = `${percent * 100}%`;
              // Update thumb position
              sliderThumb.style.left = `${percent * 100}%`;
              // Update displayed value
              sliderValue.textContent = `${value} km`;
              sliderInput.value = value;
            }

            function clearSliderPosition() {
              sliderFill.style.width = '0%';
              sliderThumb.style.left = '0%';
              sliderValue.textContent = '';
              sliderInput.value = '';
            }

            // Convert mouse/touch position to percentage
            function getPercentFromEvent(e) {
              const rect = sliderTrack.getBoundingClientRect();
              const x = e.clientX !== undefined ? e.clientX : e.touches[0].clientX;
              const percent = (x - rect.left) / rect.width;
              return percent;
            }

            // Mouse down / touch start
            sliderThumb.addEventListener('mousedown', () => {
              isDragging = true;
            });
            document.addEventListener('mouseup', () => {
              isDragging = false;
            });
            document.addEventListener('mousemove', (e) => {
              if (isDragging) {
                const percent = getPercentFromEvent(e);
                setSliderPosition(percent);
              }
            });
            sliderTrack.addEventListener('click', (e) => {
              const percent = getPercentFromEvent(e);
              setSliderPosition(percent);
            });
            sliderThumb.addEventListener('touchstart', () => {
              isDragging = true;
            });
            document.addEventListener('touchend', () => {
              isDragging = false;
            });
            document.addEventListener('touchmove', (e) => {
              if (isDragging) {
                const percent = getPercentFromEvent(e);
                setSliderPosition(percent);
              }
            });

            // Initialize position
            <?php if ($selectedDistance === ''): ?>
              clearSliderPosition();
            <?php else: ?>
              setSliderPosition(<?php echo htmlspecialchars((string) max(0, min(1, ((float) $selectedDistance) / 20))); ?>);
            <?php endif; ?>

          </script>
          

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

        </div>

        <div class="form-footer">
          <button class="btn-ghost" type="reset">Reset</button>
          <button class="btn-primary" type="submit">Save preferences</button>
        </div>
      </form>

      <!-- Alert Settings -->
      <div class="form-card">
        <div class="form-head">
          <div class="form-head-title">
            <p>Email Alert Settings</p>
          </div>
        </div>

        <div class="form-body">

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

          <!-- Daily Digest -->
          <div class="toggle-row">
            <div>
              <div class="toggle-title">
                <p>Daily digest</p>
              </div>

              <div class="toggle-sub">
                <p>Summary of new listings every morning at 8:00</p>
              </div>
            </div>

            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-slider"></span>
            </label>
          </div>

          <!-- Price Drop Alerts -->
          <div class="toggle-row">
            <div>
              <div class="toggle-title">
                <p>Price drop alerts</p>
              </div>

              <div class="toggle-sub">
                <p>Notify when a saved listing drops in price</p>
              </div>
            </div>

            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <!-- Fraud Warnings -->
          <div class="toggle-row">
            <div>
              <div class="toggle-title">
                <p>Fraud warnings</p>
              </div>

              <div class="toggle-sub">
                <p>Alert when a suspicious listing appears in your area</p>
              </div>
            </div>

            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-slider"></span>
            </label>
          </div>

        </div>

        <div class="form-footer">
          <button class="btn-primary">Save alert settings</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    (() => {
      const dropdown = document.querySelector('.source-dropdown');

      if (!dropdown) {
        return;
      }

      const label = dropdown.querySelector('.source-dropdown-label');
      const inputs = [...dropdown.querySelectorAll('input[type="checkbox"]')];
      const emptyLabel = label?.dataset.emptyLabel || 'Any source';

      const updateLabel = () => {
        const selectedLabels = inputs
          .filter((input) => input.checked)
          .map((input) => input.closest('label')?.textContent.trim())
          .filter(Boolean);

        label.textContent = selectedLabels.length > 0 ? selectedLabels.join(', ') : emptyLabel;
      };

      inputs.forEach((input) => {
        input.addEventListener('change', updateLabel);
      });

      document.addEventListener('click', (event) => {
        if (!dropdown.contains(event.target)) {
          dropdown.open = false;
        }
      });

      updateLabel();
    })();

    (() => {
      const campusSelect = document.getElementById('campus-select');
      const citySelect = document.getElementById('city-select');
      const citySearch = document.getElementById('city-search');

      if (!campusSelect || !citySelect) {
        return;
      }

      const selectedCampus = campusSelect.dataset.selectedCampus || '';
      const fallbackCampuses = [
        { name: 'Amsterdam University of Applied Sciences', city: 'Amsterdam' },
        { name: 'ArtEZ University of the Arts', city: 'Arnhem' },
        { name: 'Avans University of Applied Sciences', city: 'Breda' },
        { name: 'Breda University of Applied Sciences', city: 'Breda' },
        { name: 'Codarts Rotterdam', city: 'Rotterdam' },
        { name: 'Delft University of Technology', city: 'Delft' },
        { name: 'Design Academy Eindhoven', city: 'Eindhoven' },
        { name: 'Eindhoven University of Technology', city: 'Eindhoven' },
        { name: 'Erasmus University Rotterdam', city: 'Rotterdam' },
        { name: 'Fontys University of Applied Sciences', city: 'Eindhoven' },
        { name: 'Gerrit Rietveld Academie', city: 'Amsterdam' },
        { name: 'Hanze University of Applied Sciences', city: 'Groningen' },
        { name: 'Hotelschool The Hague', city: 'Den Haag' },
        { name: 'Inholland University of Applied Sciences', city: 'Amsterdam' },
        { name: 'Leiden University', city: 'Leiden' },
        { name: 'Maastricht University', city: 'Maastricht' },
        { name: 'NHL Stenden University of Applied Sciences - Emmen', city: 'Emmen' },
        { name: 'NHL Stenden University of Applied Sciences', city: 'Leeuwarden' },
        { name: 'Radboud University', city: 'Nijmegen' },
        { name: 'Rotterdam University of Applied Sciences', city: 'Rotterdam' },
        { name: 'Saxion University of Applied Sciences', city: 'Enschede' },
        { name: 'The Hague University of Applied Sciences', city: 'Den Haag' },
        { name: 'Tilburg University', city: 'Tilburg' },
        { name: 'University of Amsterdam', city: 'Amsterdam' },
        { name: 'University of Groningen', city: 'Groningen' },
        { name: 'University of Twente', city: 'Enschede' },
        { name: 'Utrecht University', city: 'Utrecht' },
        { name: 'Vrije Universiteit Amsterdam', city: 'Amsterdam' },
        { name: 'Wageningen University & Research', city: 'Wageningen' },
        { name: 'Windesheim University of Applied Sciences', city: 'Zwolle' },
        { name: 'Zuyd University of Applied Sciences', city: 'Maastricht' }
      ];
      const cityAliases = new Map([
        ['the hague', 'den haag'],
        ['den haag', 'den haag'],
        ["'s-gravenhage", 'den haag'],
        ['s-gravenhage', 'den haag'],
        ['s gravenhage', 'den haag']
      ]);
      const campusEndpoint = 'https://api.openalex.org/institutions?filter=country_code:NL,type:education&per-page=200&select=display_name,geo';

      let campuses = fallbackCampuses;

      const normalizeCity = (city) => {
        const normalized = city
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/\s+/g, ' ')
          .trim();

        return cityAliases.get(normalized) || normalized;
      };

      const selectedCityValue = () => citySelect.value || '';

      const uniqueCampuses = (items) => {
        const campusMap = new Map();

        items.forEach((item) => {
          const name = (item.name || '').trim();
          const city = (item.city || '').trim();

          if (name === '') {
            return;
          }

          campusMap.set(`${name.toLowerCase()}|${city.toLowerCase()}`, { name, city });
        });

        return [...campusMap.values()].sort((left, right) => {
          const citySort = left.city.localeCompare(right.city, 'nl', { sensitivity: 'base' });

          return citySort !== 0 ? citySort : left.name.localeCompare(right.name, 'nl', { sensitivity: 'base' });
        });
      };

      const setCampusOptions = () => {
        const selectedCity = selectedCityValue();
        const selectedCityKey = normalizeCity(selectedCity);
        const filteredCampuses = uniqueCampuses(campuses).filter((campus) => {
          return selectedCity === '' || normalizeCity(campus.city) === selectedCityKey;
        });
        let activeCampus = campusSelect.value || selectedCampus;
        const hasActiveCampus = filteredCampuses.some((campus) => campus.name === activeCampus);

        if (activeCampus !== '' && !hasActiveCampus) {
          if (selectedCity === '') {
            filteredCampuses.unshift({ name: activeCampus, city: '' });
          } else {
            activeCampus = '';
          }
        }

        campusSelect.replaceChildren(new Option('', '', activeCampus === '', activeCampus === ''));

        filteredCampuses.forEach((campus) => {
          const label = selectedCity === '' && campus.city !== '' ? `${campus.name} - ${campus.city}` : campus.name;

          campusSelect.add(new Option(label, campus.name, false, campus.name === activeCampus));
        });

        campusSelect.setAttribute('aria-busy', 'false');
      };

      const loadCampuses = async () => {
        const response = await fetch(campusEndpoint);

        if (!response.ok) {
          throw new Error(`OpenAlex campus request failed with ${response.status}`);
        }

        const data = await response.json();
        const apiCampuses = (data.results || []).map((institution) => ({
          name: institution.display_name || '',
          city: institution.geo?.city || ''
        }));

        campuses = uniqueCampuses([...fallbackCampuses, ...apiCampuses]);
        setCampusOptions();
      };

      citySelect.addEventListener('change', setCampusOptions);
      setCampusOptions();
      loadCampuses().catch(setCampusOptions);
    })();

    (() => {
      const citySelect = document.getElementById('city-select');
      const citySearch = document.getElementById('city-search');
      const cityOptions = document.getElementById('city-options');

      if (!citySelect || !citySearch || !cityOptions) {
        return;
      }

      const selectedCity = citySelect.value || '';
      const fallbackCities = [
        'Amsterdam',
        'Rotterdam',
        'Den Haag',
        'Utrecht',
        'Eindhoven',
        'Emmen',
        'Groningen',
        'Tilburg',
        'Almere',
        'Breda',
        'Nijmegen',
        'Enschede',
        'Haarlem',
        'Arnhem',
        'Amersfoort',
        'Apeldoorn',
        'Leiden',
        'Dordrecht',
        'Zoetermeer',
        'Zwolle',
        'Maastricht'
      ];

      const endpoint = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
      const maxVisibleOptions = 12;

      const naturalSort = (left, right) => left.localeCompare(right, 'nl', { sensitivity: 'base' });
      let cities = fallbackCities;
      let activeOptionIndex = -1;

      const closeOptions = () => {
        cityOptions.classList.remove('show');
        citySearch.setAttribute('aria-expanded', 'false');
        activeOptionIndex = -1;
      };

      const selectCity = (city) => {
        citySearch.value = city;
        citySelect.value = city;
        closeOptions();
        citySelect.dispatchEvent(new Event('change'));
      };

      const renderCityOptions = () => {
        const query = citySearch.value.trim().toLowerCase();
        const selectedValue = citySelect.value;
        const matchingCities = cities
          .filter((city) => query === '' || city.toLowerCase().includes(query))
          .slice(0, maxVisibleOptions);

        cityOptions.replaceChildren();

        if (matchingCities.length === 0) {
          closeOptions();
          return;
        }

        matchingCities.forEach((city, index) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = `city-option${city === selectedValue ? ' active' : ''}`;
          option.id = `city-option-${index}`;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', city === selectedValue ? 'true' : 'false');
          option.textContent = city;
          option.addEventListener('mousedown', (event) => {
            event.preventDefault();
            selectCity(city);
          });

          cityOptions.append(option);
        });

        cityOptions.classList.add('show');
        citySearch.setAttribute('aria-expanded', 'true');
      };

      const clearCityIfNotExactMatch = () => {
        const typedCity = citySearch.value.trim();
        const exactCity = cities.find((city) => city.toLowerCase() === typedCity.toLowerCase());

        citySelect.value = exactCity || '';

        if (exactCity !== undefined && citySearch.value !== exactCity) {
          citySearch.value = exactCity;
        }

        citySelect.dispatchEvent(new Event('change'));
      };

      const setCityOptions = (loadedCities) => {
        const uniqueCities = [...new Set(loadedCities.filter(Boolean))].sort(naturalSort);

        if (selectedCity !== '' && !uniqueCities.includes(selectedCity)) {
          uniqueCities.unshift(selectedCity);
        }

        cities = uniqueCities;
        citySearch.setAttribute('aria-busy', 'false');
        clearCityIfNotExactMatch();
        citySelect.dispatchEvent(new Event('change'));
      };

      const fetchCityPage = async (start) => {
        const params = new URLSearchParams({
          q: '*',
          fq: 'type:woonplaats',
          rows: '100',
          start: String(start),
          fl: 'woonplaatsnaam'
        });
        const response = await fetch(`${endpoint}?${params.toString()}`);

        if (!response.ok) {
          throw new Error(`PDOK city request failed with ${response.status}`);
        }

        return response.json();
      };

      const loadCities = async () => {
        const firstPage = await fetchCityPage(0);
        const total = Number(firstPage?.response?.numFound || 0);
        const starts = [];

        for (let start = 100; start < total; start += 100) {
          starts.push(start);
        }

        const pages = await Promise.all(starts.map(fetchCityPage));
        const docs = [firstPage, ...pages].flatMap((page) => page?.response?.docs || []);

        setCityOptions(docs.map((doc) => doc.woonplaatsnaam));
      };

      loadCities().catch(() => {
        setCityOptions(fallbackCities);
      });

      citySearch.addEventListener('input', () => {
        citySelect.value = '';
        citySelect.dispatchEvent(new Event('change'));
        renderCityOptions();
      });

      citySearch.addEventListener('focus', renderCityOptions);

      citySearch.addEventListener('blur', () => {
        clearCityIfNotExactMatch();
        window.setTimeout(closeOptions, 120);
      });

      citySearch.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          renderCityOptions();
          const options = [...cityOptions.querySelectorAll('.city-option')];
          activeOptionIndex = Math.min(activeOptionIndex + 1, options.length - 1);
          options.forEach((option, index) => {
            option.classList.toggle('active', index === activeOptionIndex);
          });
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          const options = [...cityOptions.querySelectorAll('.city-option')];
          activeOptionIndex = Math.max(activeOptionIndex - 1, 0);
          options.forEach((option, index) => {
            option.classList.toggle('active', index === activeOptionIndex);
          });
        } else if (event.key === 'Enter') {
          const options = [...cityOptions.querySelectorAll('.city-option')];

          if (activeOptionIndex >= 0 && options[activeOptionIndex]) {
            event.preventDefault();
            selectCity(options[activeOptionIndex].textContent);
          }

          return;
        } else if (event.key === 'Escape') {
          closeOptions();
          return;
        } else {
          return;
        }
      });
    })();
  </script>
</body>
</html>
