<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (currentUser()) redirect('index.php');

$errors = [];
$rateLimited = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $rateLimited = !throttle('register', 4, 1800);
    if ($rateLimited) {
        $errors[] = 'Too many signup attempts from this session. Please wait and try again.';
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $birthDateRaw = trim((string)($_POST['birth_date'] ?? ''));

    if (!$rateLimited) {
        if (!preg_match('/^[A-Za-z0-9_]{2,' . MAX_USERNAME_LENGTH . '}$/', $username)) $errors[] = 'Username must be 2–24 letters, numbers, or underscores.';
        if (u_strlen($email) > MAX_EMAIL_LENGTH || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < MIN_PASSWORD_LENGTH) $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        if (($_POST['accept_terms'] ?? '') !== '1') $errors[] = 'You must accept the Terms, Rules, and Privacy Policy.';

        $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDateRaw);
        $birthDateErrors = DateTimeImmutable::getLastErrors();
        $birthDateValid = $birthDate instanceof DateTimeImmutable
            && ($birthDateErrors === false || ($birthDateErrors['warning_count'] === 0 && $birthDateErrors['error_count'] === 0))
            && $birthDate->format('Y-m-d') === $birthDateRaw;

        if (!$birthDateValid) {
            $errors[] = 'Enter your date of birth.';
        } else {
            $today = new DateTimeImmutable('today');
            if ($birthDate > $today) {
                $errors[] = 'Your date of birth cannot be in the future.';
            } elseif ($birthDate->diff($today)->y < 13) {
                $errors[] = 'You must be at least 13 years old to create a Webmore account.';
            }
        }

        $emailLower = u_strtolower($email);
        updateJson('users', function (array &$users) use (&$errors, $username, $emailLower, $email, $password): void {
            foreach ($users as $user) {
                if (strtolower((string)($user['username'] ?? '')) === strtolower($username)) $errors[] = 'That username is already taken.';
                if (strtolower((string)($user['email'] ?? '')) === $emailLower) $errors[] = 'That email is already registered.';
            }
            if ($errors) return;
            $now = time();
            $user = [
                'id' => bin2hex(random_bytes(16)),
                'username' => $username,
                'email' => $email,
                'password_hash' => passwordHash($password),
                'created_at' => $now,
                'age_verified_at' => $now,
                'display_name' => $username,
                'bio' => '',
                'website' => '',
                'avatar' => '',
                'theme' => 'system',
                'accent' => '#e85d2a',
                'terms_accepted_at' => $now,
                'policy_version' => POLICY_VERSION,
            ];
            array_unshift($users, $user);
        });
        if (!$errors) {
            $newUser = userByUsername($username);
            if ($newUser) {
                loginUser($newUser);
                redirect('index.php');
            }
            $errors[] = 'The account could not be created. Please try again.';
        }
    }
}

renderHeader('Sign up');
?>
<section class="auth-shell">
    <?php if (isset($_GET['deleted'])): ?><div class="success">Your account and your posts were deleted from this Webmore installation.</div><?php endif; ?>
    <div class="eyebrow">join webmore</div>
    <h1>Make an account.</h1>
    <p class="lede">Browse freely. Create and customize your own corner when you are ready.</p>
    <div class="form-card">
        <?php if ($errors): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="field"><label for="username">Username</label><input id="username" name="username" required maxlength="<?= MAX_USERNAME_LENGTH ?>" value="<?= h($_POST['username'] ?? '') ?>" placeholder="yourname" autocomplete="username"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required maxlength="<?= MAX_EMAIL_LENGTH ?>" value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com" autocomplete="email"></div>
            <div class="field"><label for="birth_date">Date of birth</label><input id="birth_date" name="birth_date" type="date" required value="<?= h($_POST['birth_date'] ?? '') ?>" max="<?= h((new DateTimeImmutable('today'))->format('Y-m-d')) ?>" autocomplete="bday"><div class="field-help">Only the fact that the 13+ check was completed is kept. The exact date is not retained after signup.</div></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="<?= MIN_PASSWORD_LENGTH ?>" placeholder="Use a long, unique password" autocomplete="new-password"><div class="field-help">At least <?= MIN_PASSWORD_LENGTH ?> characters. Longer is better; special character requirements are not used.</div></div>
            <label class="check-row"><input type="checkbox" name="accept_terms" value="1" required> <span>I agree to the <a href="terms.php" target="_blank" rel="noopener noreferrer">Terms of Use</a>, <a href="rules.php" target="_blank" rel="noopener noreferrer">Rules</a>, and <a href="privacy.php" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.</span></label>
            <button class="btn accent full">Create account</button>
        </form>
        <div class="notice small-note">Your email is used for your account and is not shown on public profiles. Webmore does not use it for advertising in this starter.</div>
    </div>
</section>
<?php renderFooter(); ?>
