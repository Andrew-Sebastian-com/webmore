<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (currentUser()) redirect('index.php');

$errors = [];
$next = safeNext((string)($_GET['next'] ?? $_POST['next'] ?? 'index.php'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!throttle('login', 8, 600)) {
        $errors[] = 'Too many login attempts from this session. Please wait a few minutes and try again.';
    } else {
        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $users = readJson('users');
        $foundIndex = null;
        $found = null;
        foreach ($users as $index => $rawUser) {
            if (strtolower((string)($rawUser['username'] ?? '')) === strtolower($identifier) || strtolower((string)($rawUser['email'] ?? '')) === strtolower($identifier)) {
                $foundIndex = $index;
                $found = normalizeUser($rawUser);
                break;
            }
        }
        if (!$found || !passwordVerifyAndUpgrade($password, $found)) {
            $errors[] = 'Those login details did not match.';
        } else {
            if ($foundIndex !== null && $found['password_hash'] !== $users[$foundIndex]['password_hash']) {
                $updated = $found;
                updateJson('users', function (array &$data) use ($updated): void {
                    foreach ($data as $i => $item) {
                        if ((string)($item['id'] ?? '') === (string)$updated['id']) {
                            $data[$i] = $updated;
                            return;
                        }
                    }
                });
            }
            $_SESSION['rate']['login'] = [];
            loginUser($found);
            redirect($next);
        }
    }
}

renderHeader('Log in');
?>
<section class="auth-shell">
    <div class="eyebrow">welcome back</div>
    <h1>Log in.</h1>
    <p class="lede">Your posts, profile, and preferences live with your account.</p>
    <div class="form-card">
        <?php if ($errors): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="next" value="<?= h($next) ?>">
            <div class="field"><label for="identifier">Username or email</label><input id="identifier" name="identifier" required maxlength="254" value="<?= h($_POST['identifier'] ?? '') ?>" placeholder="yourname" autocomplete="username"></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
            <button class="btn accent full">Log in</button>
        </form>
        <div class="notice small-note">For safety, failed logins are limited per session. Webmore never reveals whether a specific username or email exists through a login error.</div>
    </div>
</section>
<?php renderFooter(); ?>
