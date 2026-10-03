<?php
declare(strict_types=1);

const PRIVATE_DIR = __DIR__ . '/../private';
const DATA_DIR = PRIVATE_DIR . '/data';
const UPLOAD_DIR = PRIVATE_DIR . '/uploads';
const MAX_IMAGE_BYTES = 5_000_000;
const MAX_AVATAR_BYTES = 2_000_000;
const MAX_IMAGE_WIDTH = 7000;
const MAX_IMAGE_HEIGHT = 7000;
const SESSION_IDLE_SECONDS = 7200;
const SESSION_ABSOLUTE_SECONDS = 604800;
const SITE_CONTACT_EMAIL = 'andrew.sbstian@gmail.com';
const POLICY_UPDATED = 'October 3, 2026';
const POLICY_VERSION = '2.0';
const MIN_PASSWORD_LENGTH = 12;
const MAX_USERNAME_LENGTH = 24;
const MAX_EMAIL_LENGTH = 254;

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0700, true);
}
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0700, true);
}
if (!is_dir(DATA_DIR . '/rate')) {
    @mkdir(DATA_DIR . '/rate', 0700, true);
}

// Harden the session before session_start(). Secure becomes true automatically
// when the app is actually served over HTTPS, while local HTTP development still works.
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $https ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string)SESSION_ABSOLUTE_SECONDS);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name($https ? '__Host-webmore_session' : 'webmore_session');
session_start();

function securityHeaders(): void {
    global $cspNonce;
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self'; style-src 'self' 'nonce-$cspNonce'; img-src 'self' data:; connect-src 'self'; font-src 'self'; media-src 'self';");
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-XSS-Protection: 0');
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

$cspNonce = base64_encode(random_bytes(18));

securityHeaders();

function enforceSessionLifetime(): void {
    $now = time();
    $created = (int)($_SESSION['created_at'] ?? $now);
    $last = (int)($_SESSION['last_activity'] ?? $now);
    if ($created <= 0) $created = $now;
    if ($last <= 0) $last = $now;

    if (($now - $last) > SESSION_IDLE_SECONDS || ($now - $created) > SESSION_ABSOLUTE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['created_at'] = $now;
        $_SESSION['last_activity'] = $now;
        if (!empty($_SESSION['username'])) {
            unset($_SESSION['username']);
        }
    } else {
        $_SESSION['created_at'] = $created;
        $_SESSION['last_activity'] = $now;
    }
}

function touchSession(): void {
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }
}

enforceSessionLifetime();
touchSession();

function dbFile(string $name): string {
    if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
        throw new InvalidArgumentException('Invalid data store name.');
    }
    return DATA_DIR . '/' . $name . '.json';
}

function decodeJsonResource($handle): array {
    rewind($handle);
    $json = stream_get_contents($handle);
    if ($json === false || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function readJson(string $name, array $fallback = []): array {
    $file = dbFile($name);
    if (!is_file($file)) {
        return $fallback;
    }
    $handle = @fopen($file, 'rb');
    if ($handle === false) return $fallback;
    try {
        if (!@flock($handle, LOCK_SH)) return $fallback;
        $data = decodeJsonResource($handle);
        @flock($handle, LOCK_UN);
        return $data;
    } finally {
        fclose($handle);
    }
}

function writeJson(string $name, array $data): void {
    $file = dbFile($name);
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Unable to open data store.');
    }
    try {
        if (!@flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to lock data store.');
        }
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        ftruncate($handle, 0);
        rewind($handle);
        if (fwrite($handle, $encoded) === false) {
            throw new RuntimeException('Unable to write data store.');
        }
        fflush($handle);
        @chmod($file, 0600);
        @flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

function updateJson(string $name, callable $callback): mixed {
    $file = dbFile($name);
    $handle = @fopen($file, 'c+');
    if ($handle === false) throw new RuntimeException('Unable to open data store.');
    try {
        if (!@flock($handle, LOCK_EX)) throw new RuntimeException('Unable to lock data store.');
        $data = decodeJsonResource($handle);
        if (!is_array($data)) $data = [];
        $result = $callback($data);
        rewind($handle);
        ftruncate($handle, 0);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        fwrite($handle, $encoded);
        fflush($handle);
        @chmod($file, 0600);
        @flock($handle, LOCK_UN);
        return $result;
    } finally {
        fclose($handle);
    }
}

function seedData(): void {
    if (!is_file(dbFile('users'))) writeJson('users', []);
    if (!is_file(dbFile('posts'))) writeJson('posts', []);
    if (!is_file(dbFile('replies'))) writeJson('replies', []);
    if (!is_file(dbFile('user_actions'))) writeJson('user_actions', []);
    if (!is_file(dbFile('reports'))) writeJson('reports', []);
    // Privacy migration: never retain an exact birth date once the 13+ check is complete.
    $users = readJson('users');
    $changed = false;
    foreach ($users as $i => $user) {
        if (array_key_exists('birth_date', $user)) {
            unset($user['birth_date']);
            $changed = true;
        }
        if (empty($user['age_verified_at'])) {
            $user['age_verified_at'] = (int)($user['created_at'] ?? time());
            $changed = true;
        }
        $users[$i] = normalizeUser($user);
    }
    if ($changed) writeJson('users', $users);
}

seedData();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function u_strlen(string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function u_strtolower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function u_substr(string $value, int $start, ?int $length = null): string {
    if (function_exists('mb_substr')) return mb_substr($value, $start, $length, 'UTF-8');
    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function u_strtoupper(string $value): string {
    return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}


function normalizeUser(array $user): array {
    $username = (string)($user['username'] ?? '');
    $defaults = [
        'id' => bin2hex(random_bytes(16)),
        'username' => $username,
        'email' => (string)($user['email'] ?? ''),
        'password_hash' => (string)($user['password_hash'] ?? ''),
        'created_at' => (int)($user['created_at'] ?? time()),
        'age_verified_at' => (int)($user['age_verified_at'] ?? $user['created_at'] ?? time()),
        'display_name' => $username,
        'bio' => '',
        'website' => '',
        'avatar' => '',
        'theme' => 'system',
        'accent' => '#e85d2a',
        'language' => 'en',
        'terms_accepted_at' => null,
        'policy_version' => POLICY_VERSION,
    ];
    $merged = array_merge($defaults, $user);
    unset($merged['birth_date']);
    $merged['username'] = trim((string)$merged['username']);
    $merged['email'] = trim((string)$merged['email']);
    $merged['display_name'] = trim((string)$merged['display_name']);
    if ($merged['display_name'] === '') $merged['display_name'] = $username;
    $merged['bio'] = (string)$merged['bio'];
    $merged['website'] = (string)$merged['website'];
    if (!in_array($merged['theme'], ['system', 'light', 'dark'], true)) $merged['theme'] = 'system';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$merged['accent'])) $merged['accent'] = '#e85d2a';
    if (!in_array($merged['language'], ['en', 'ja'], true)) $merged['language'] = 'en';
    return $merged;
}

function userByUsername(string $username): ?array {
    foreach (readJson('users') as $user) {
        if (strtolower((string)($user['username'] ?? '')) === strtolower($username)) return normalizeUser($user);
    }
    return null;
}

function currentUser(): ?array {
    $username = (string)($_SESSION['username'] ?? '');
    if ($username === '') return null;
    $user = userByUsername($username);
    if (!$user) {
        unset($_SESSION['username']);
        return null;
    }
    return $user;
}

function passwordHash(string $password): string {
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    return password_hash($password, $algo);
}

function passwordVerifyAndUpgrade(string $password, array &$user): bool {
    $hash = (string)($user['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) return false;
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    if (password_needs_rehash($hash, $algo)) {
        $user['password_hash'] = password_hash($password, $algo);
    }
    return true;
}

function loginUser(array $user): void {
    $user = normalizeUser($user);
    session_regenerate_id(true);
    $_SESSION = [
        'username' => $user['username'],
        'created_at' => time(),
        'last_activity' => time(),
        'csrf' => bin2hex(random_bytes(32)),
    ];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?: '',
            'secure' => (bool)$params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function saveUser(array $updatedUser): void {
    $updatedUser = normalizeUser($updatedUser);
    updateJson('users', function (array &$users) use ($updatedUser): void {
        foreach ($users as $index => $user) {
            if ((string)($user['id'] ?? '') === (string)$updatedUser['id']) {
                $users[$index] = $updatedUser;
                return;
            }
        }
        throw new RuntimeException('User account could not be found.');
    });
}

function requireLogin(): void {
    if (!currentUser()) {
        header('Location: login.php?next=create.php', true, 303);
        exit;
    }
}

function redirect(string $url): never {
    header('Location: ' . $url, true, 303);
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = (string)($_POST['csrf'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
        http_response_code(419);
        exit('Invalid form token. Please go back and try again.');
    }
}

function throttle(string $action, int $limit, int $windowSeconds): bool {
    $now = time();
    $bucket = $_SESSION['rate'][$action] ?? [];
    if (!is_array($bucket)) $bucket = [];
    $bucket = array_values(array_filter($bucket, static fn($t): bool => is_int($t) && $t > ($now - $windowSeconds)));
    if (count($bucket) >= $limit) {
        $_SESSION['rate'][$action] = $bucket;
        return false;
    }
    $bucket[] = $now;
    $_SESSION['rate'][$action] = $bucket;
    return true;
}

function formatTime(int $timestamp): string {
    $diff = max(0, time() - $timestamp);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $timestamp);
}

function validUrl(string $url): bool {
    if ($url === '' || u_strlen($url) > 2048) return false;
    if (filter_var($url, FILTER_VALIDATE_URL) === false) return false;
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function safeNext(string $value): string {
    $value = trim($value);
    if ($value === '' || strlen($value) > 300) return 'index.php';
    $parts = parse_url($value);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return 'index.php';
    $path = (string)($parts['path'] ?? '');
    $allowed = ['index.php', 'create.php', 'explore.php', 'settings.php', 'profile.php', 'post.php', 'saved.php', 'edit.php', 'report.php'];
    if (!in_array(basename($path), $allowed, true)) return 'index.php';
    return ltrim($value, '/');
}

function makePostId(): string {
    return 'post-' . bin2hex(random_bytes(12));
}

function makeReplyId(): string {
    return 'reply-' . bin2hex(random_bytes(12));
}

function actionBucketForUser(array $user): array {
    $uid = (string)($user['id'] ?? '');
    foreach (readJson('user_actions') as $bucket) {
        if ((string)($bucket['user_id'] ?? '') === $uid) {
            return [
                'user_id' => $uid,
                'favorites' => array_values(array_unique(array_filter($bucket['favorites'] ?? [], 'is_string'))),
                'archives' => array_values(array_unique(array_filter($bucket['archives'] ?? [], 'is_string'))),
                'likes' => array_values(array_unique(array_filter($bucket['likes'] ?? [], 'is_string'))),
            ];
        }
    }
    return ['user_id' => $uid, 'favorites' => [], 'archives' => [], 'likes' => []];
}

function setUserActionState(array $user, string $kind, string $postId, bool $enabled): bool {
    if (!in_array($kind, ['favorites', 'archives', 'likes'], true)) return false;
    if (!preg_match('/^post-[a-f0-9]{24}$/', $postId)) return false;
    $uid = (string)($user['id'] ?? '');
    if ($uid === '') return false;

    return (bool)updateJson('user_actions', function (array &$rows) use ($uid, $kind, $postId, $enabled): bool {
        $index = null;
        foreach ($rows as $i => $row) {
            if ((string)($row['user_id'] ?? '') === $uid) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            $rows[] = ['user_id' => $uid, 'favorites' => [], 'archives' => [], 'likes' => []];
            $index = array_key_last($rows);
        }
        $items = array_values(array_unique(array_filter($rows[$index][$kind] ?? [], 'is_string')));
        $has = in_array($postId, $items, true);
        if ($enabled && !$has) $items[] = $postId;
        if (!$enabled && $has) $items = array_values(array_filter($items, static fn($id): bool => $id !== $postId));
        $rows[$index][$kind] = $items;
        return $enabled ? !$has : $has;
    });
}

function isPostFavorited(?array $user, string $postId): bool {
    if (!$user) return false;
    return in_array($postId, actionBucketForUser($user)['favorites'], true);
}

function isPostArchived(?array $user, string $postId): bool {
    if (!$user) return false;
    return in_array($postId, actionBucketForUser($user)['archives'], true);
}

function togglePostFavorite(array $user, string $postId): bool {
    $enabled = !isPostFavorited($user, $postId);
    setUserActionState($user, 'favorites', $postId, $enabled);
    return $enabled;
}

function togglePostArchive(array $user, string $postId): bool {
    $enabled = !isPostArchived($user, $postId);
    setUserActionState($user, 'archives', $postId, $enabled);
    return $enabled;
}

function isPostLiked(?array $user, string $postId): bool {
    if (!$user) return false;
    return in_array($postId, actionBucketForUser($user)['likes'], true);
}

function setPostLike(array $user, string $postId, bool $enabled): bool {
    $result = setUserActionState($user, 'likes', $postId, $enabled);
    if (!$result) return false;
    updateJson('posts', function (array &$posts) use ($postId, $enabled): void {
        foreach ($posts as &$post) {
            if ((string)($post['id'] ?? '') !== $postId) continue;
            $likes = max(0, (int)($post['likes'] ?? 0));
            $post['likes'] = $enabled ? $likes + 1 : max(0, $likes - 1);
            break;
        }
        unset($post);
    });
    return $enabled;
}

function togglePostLike(array $user, string $postId): bool {
    return setPostLike($user, $postId, !isPostLiked($user, $postId));
}

function removePostFromUserActions(string $postId): void {
    if (!preg_match('/^post-[a-f0-9]{24}$/', $postId)) return;
    updateJson('user_actions', function (array &$rows) use ($postId): void {
        foreach ($rows as &$row) {
            foreach (['favorites', 'archives', 'likes'] as $kind) {
                $row[$kind] = array_values(array_filter($row[$kind] ?? [], static fn($id): bool => $id !== $postId));
            }
        }
        unset($row);
    });
}

function hasReportedPost(array $user, string $postId): bool {
    $uid = (string)($user['id'] ?? '');
    foreach (readJson('reports') as $report) {
        if ((string)($report['user_id'] ?? '') === $uid && (string)($report['post_id'] ?? '') === $postId && ($report['status'] ?? 'open') !== 'dismissed') return true;
    }
    return false;
}

function reportPost(array $user, string $postId, string $reason, string $details = ''): bool {
    if (!preg_match('/^post-[a-f0-9]{24}$/', $postId)) return false;
    if (hasReportedPost($user, $postId)) return false;
    $reason = trim($reason);
    $allowed = ['spam', 'harassment', 'privacy', 'copyright', 'dangerous', 'other'];
    if (!in_array($reason, $allowed, true)) return false;
    if (u_strlen($details) > 1000) $details = u_substr($details, 0, 1000);
    $row = [
        'id' => 'report-' . bin2hex(random_bytes(12)),
        'post_id' => $postId,
        'user_id' => (string)$user['id'],
        'reason' => $reason,
        'details' => $details,
        'created_at' => time(),
        'status' => 'open',
    ];
    updateJson('reports', function (array &$reports) use ($row): void { $reports[] = $row; });
    return true;
}

function deletePost(string $postId, array $user): bool {
    if (!preg_match('/^post-[a-f0-9]{24}$/', $postId)) return false;
    $deleted = null;
    $changed = updateJson('posts', function (array &$posts) use ($postId, $user, &$deleted): bool {
        $next = [];
        $removed = false;
        foreach ($posts as $post) {
            $isTarget = (string)($post['id'] ?? '') === $postId;
            $isOwner = strtolower((string)($post['author'] ?? '')) === strtolower((string)$user['username']);
            if ($isTarget && $isOwner) {
                $deleted = $post;
                $removed = true;
                continue;
            }
            $next[] = $post;
        }
        if ($removed) $posts = $next;
        return $removed;
    });
    if ($changed && is_array($deleted)) {
        if (($deleted['image'] ?? '') !== '') removePrivateUpload((string)$deleted['image']);
        updateJson('replies', function (array &$replies) use ($postId): void {
            $replies = array_values(array_filter($replies, static fn($reply): bool => (string)($reply['post_id'] ?? '') !== $postId));
        });
        removePostFromUserActions($postId);
    }
    return $changed;
}

function updatePost(string $postId, array $user, array $updates): bool {
    if (!preg_match('/^post-[a-f0-9]{24}$/', $postId)) return false;
    return (bool)updateJson('posts', function (array &$posts) use ($postId, $user, $updates): bool {
        foreach ($posts as $i => $post) {
            if ((string)($post['id'] ?? '') !== $postId) continue;
            if (strtolower((string)($post['author'] ?? '')) !== strtolower((string)$user['username'])) return false;
            foreach (['title','content','image','link','tags'] as $key) {
                if (array_key_exists($key, $updates)) $post[$key] = $updates[$key];
            }
            $post['updated_at'] = time();
            $posts[$i] = $post;
            return true;
        }
        return false;
    });
}

function postById(string $id): ?array {
    if (!preg_match('/^post-[a-f0-9]{24}$/', $id)) return null;
    foreach (readJson('posts') as $post) if (($post['id'] ?? '') === $id) return $post;
    return null;
}

function mediaUrl(string $filename): string {
    $filename = basename($filename);
    return 'media.php?file=' . rawurlencode($filename);
}

function initials(array $user): string {
    $name = trim((string)($user['display_name'] ?? $user['username'] ?? ''));
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) $letters .= u_substr($part, 0, 1);
    return u_strtoupper($letters ?: u_substr($name, 0, 1));
}

function hexToRgba(string $hex, float $alpha): string {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return sprintf('rgba(%d, %d, %d, %.2f)', $r, $g, $b, $alpha);
}

function avatarMarkup(array $user, string $class = 'avatar'): string {
    $avatar = (string)($user['avatar'] ?? '');
    $alt = h((string)($user['display_name'] ?? $user['username'] ?? ''));
    if ($avatar !== '' && preg_match('/^[A-Za-z0-9._-]+$/', basename($avatar))) {
        return '<img class="' . h($class) . ' avatar-image" src="' . h(mediaUrl($avatar)) . '" alt="' . $alt . '" loading="lazy">';
    }
    return '<span class="' . h($class) . ' avatar-fallback" aria-hidden="true">' . h(initials($user)) . '</span>';
}

function saveUploadedImage(array $file, string $prefix, int $maxBytes): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file((string)$file['tmp_name'])) return null;
    if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) return null;

    $dimensions = @getimagesize((string)$file['tmp_name']);
    if (!is_array($dimensions)) return null;
    $width = (int)($dimensions[0] ?? 0);
    $height = (int)($dimensions[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) return null;

    $filename = $prefix . '-' . bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
    $destination = UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) return null;
    @chmod($destination, 0600);
    return $filename;
}

function removePrivateUpload(string $filename): void {
    $filename = basename($filename);
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) return;
    $path = UPLOAD_DIR . '/' . $filename;
    if (is_file($path)) @unlink($path);
}

function currentLanguage(): string {
    $cookie = (string)($_COOKIE['webmore_lang'] ?? '');
    if (in_array($cookie, ['en', 'ja'], true)) return $cookie;
    $user = currentUser();
    if ($user && in_array(($user['language'] ?? 'en'), ['en', 'ja'], true)) return (string)$user['language'];
    return 'en';
}

function setLanguageCookie(string $language): void {
    $language = in_array($language, ['en', 'ja'], true) ? $language : 'en';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('webmore_lang', $language, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => $https,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function renderHeader(string $title = 'Webmore'): void {
    global $cspNonce;
    $user = currentUser();
    $query = h($_GET['q'] ?? '');
    $theme = $user['theme'] ?? 'system';
    $accent = $user['accent'] ?? '#e85d2a';
    $accentSoft = hexToRgba($accent, 0.10);
    $language = currentLanguage();
    ?>
<!doctype html>
<html lang="<?= $language === 'ja' ? 'ja' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Webmore — stupid facts, ideas, images, and links.">
    <title><?= h($title) ?> · Webmore</title>
    <link rel="stylesheet" href="styles.css">
    <style nonce="<?= h($cspNonce) ?>">:root{--accent:<?= h($accent) ?>;--accent-soft:<?= h($accentSoft) ?>;}</style>
</head>
<body data-theme="<?= h($theme) ?>" data-lang="<?= h($language) ?>">
<header class="topbar">
    <nav class="nav">
        <a class="logo" href="index.php" aria-label="Webmore"><span class="logo-w">W</span>EBMORE</a>
        <div class="navlinks">
            <a href="index.php">Home</a>
            <a href="explore.php">Explore</a>
            <a href="random.php">Random</a>
        </div>
        <div class="spacer"></div>
        <form class="language-mini" method="post" action="language.php">
            <?php if ($user): ?><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><?php endif; ?>
            <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
            <select name="language" aria-label="Language">
                <option value="en" <?= $language === 'en' ? 'selected' : '' ?>>EN</option>
                <option value="ja" <?= $language === 'ja' ? 'selected' : '' ?>>日本語</option>
            </select>
        </form>
        <form class="mini-search" action="explore.php" method="get">
            <input name="q" value="<?= $query ?>" placeholder="Search" aria-label="Search">
        </form>
        <?php if ($user): ?>
            <a class="btn small accent nav-post" href="create.php">Post</a>
            <details class="account-menu">
                <summary class="user-chip">
                    <?= avatarMarkup($user, 'avatar tiny') ?>
                    <span><?= h((string)$user['display_name']) ?></span>
                    <span class="chevron">⌄</span>
                </summary>
                <div class="account-popover">
                    <div class="account-popover-head">
                        <?= avatarMarkup($user, 'avatar small') ?>
                        <div>
                            <strong><?= h((string)$user['display_name']) ?></strong>
                            <div class="muted">@<?= h((string)$user['username']) ?></div>
                        </div>
                    </div>
                    <a href="profile.php?user=<?= urlencode((string)$user['username']) ?>">Profile</a>
                    <a href="saved.php?view=favorites">Favourites</a>
                    <a href="saved.php?view=archive">Archive</a>
                    <a href="settings.php">Settings</a>
                    <form method="post" action="logout.php" class="logout-form">
                        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                        <button type="submit" class="popover-logout text-button">Log out</button>
                    </form>
                </div>
            </details>
        <?php else: ?>
            <a class="btn small ghost nav-post" href="login.php?next=create.php">Post</a>
            <a class="btn small ghost" href="login.php">Log in</a>
            <a class="btn small accent" href="register.php">Sign up</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?php
}

function renderFooter(): void {
    ?>
</main>
<footer class="footer">
    <div class="footer-main">
        <span>© Andrew Sebastian · YouTube: <a href="https://www.youtube.com/@AndrewSudiro" target="_blank" rel="noopener noreferrer">AndrewSudiro</a> · GitHub: <a href="https://github.com/Andrew-Sebastian-com" target="_blank" rel="noopener noreferrer">andrew-sebastian-com</a></span>
        <nav class="footer-links" aria-label="Legal">
            <a href="rules.php">Rules</a>
            <a href="privacy.php">Privacy</a>
            <a href="security.php">Security</a>
            <a href="disclaimer.php">Disclaimer</a>
            <a href="terms.php">Terms</a>
        </nav>
    </div>
</footer>
<script src="app.js"></script>
</body>
</html>
    <?php
}
