<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$profileErrors = [];
$appearanceErrors = [];
$languageErrors = [];
$passwordErrors = [];
$deleteError = '';
$success = (string)($_GET['saved'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $section = (string)($_POST['section'] ?? '');
    $user = currentUser();

    if ($section === 'profile') {
        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));

        if (!throttle('settings_profile', 10, 600)) $profileErrors[] = 'Too many profile changes. Please wait a moment.';
        if ($displayName === '') $profileErrors[] = 'Display name cannot be empty.';
        if (u_strlen($displayName) > 40) $profileErrors[] = 'Display name must be 40 characters or fewer.';
        if (u_strlen($bio) > 280) $profileErrors[] = 'Bio must be 280 characters or fewer.';
        if (u_strlen($website) > 2048 || ($website !== '' && !validUrl($website))) $profileErrors[] = 'Website must be a valid http:// or https:// URL.';

        $newAvatar = null;
        if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newAvatar = saveUploadedImage($_FILES['avatar'], 'avatar', MAX_AVATAR_BYTES);
            if ($newAvatar === null) {
                $profileErrors[] = 'Please upload a real JPG, PNG, GIF, or WebP avatar up to 2 MB and 7000×7000 pixels.';
            }
        }

        if (!$profileErrors) {
            $oldAvatar = (string)($user['avatar'] ?? '');
            $user['display_name'] = $displayName;
            $user['bio'] = $bio;
            $user['website'] = $website;
            if ($newAvatar !== null) $user['avatar'] = $newAvatar;
            saveUser($user);
            if ($newAvatar !== null && $oldAvatar !== '') removePrivateUpload($oldAvatar);
            redirect('settings.php?saved=profile#profile');
        } elseif ($newAvatar !== null) {
            removePrivateUpload($newAvatar);
        }
    }

    if ($section === 'appearance') {
        $theme = (string)($_POST['theme'] ?? 'system');
        $accent = (string)($_POST['accent'] ?? '#e85d2a');
        if (!throttle('settings_appearance', 20, 600)) $appearanceErrors[] = 'Too many appearance changes. Please wait a moment.';
        if (!in_array($theme, ['system', 'light', 'dark'], true)) $appearanceErrors[] = 'Choose a valid theme.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) $appearanceErrors[] = 'Choose a valid accent color.';

        if (!$appearanceErrors) {
            $user['theme'] = $theme;
            $user['accent'] = $accent;
            saveUser($user);
            redirect('settings.php?saved=appearance#appearance');
        }
    }

    if ($section === 'language') {
        $language = (string)($_POST['language'] ?? 'en');
        if (!throttle('settings_language', 20, 600)) $languageErrors[] = 'Too many language changes. Please wait a moment.';
        if (!in_array($language, ['en', 'ja'], true)) $languageErrors[] = 'Choose English or Japanese.';
        if (!$languageErrors) {
            $user['language'] = $language;
            saveUser($user);
            setLanguageCookie($language);
            redirect('settings.php?saved=language#language');
        }
    }

    if ($section === 'password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        if (!throttle('settings_password', 5, 900)) $passwordErrors[] = 'Too many password changes. Please wait before trying again.';
        if (!$passwordErrors && !password_verify($currentPassword, (string)$user['password_hash'])) $passwordErrors[] = 'Your current password is incorrect.';
        if (strlen($newPassword) < MIN_PASSWORD_LENGTH) $passwordErrors[] = 'New password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        if ($newPassword !== $confirmPassword) $passwordErrors[] = 'New passwords do not match.';
        if ($newPassword !== '' && password_verify($newPassword, (string)$user['password_hash'])) $passwordErrors[] = 'Choose a different new password.';

        if (!$passwordErrors) {
            $user['password_hash'] = passwordHash($newPassword);
            saveUser($user);
            // A password change invalidates the current session and requires a fresh login.
            logoutUser();
            redirect('login.php?changed=1');
        }
    }

    if ($section === 'delete_account') {
        $currentPassword = (string)($_POST['delete_password'] ?? '');
        $confirm = (string)($_POST['confirm_delete'] ?? '');
        if (!throttle('settings_delete', 3, 1800)) {
            $deleteError = 'Too many deletion attempts. Please wait.';
        } elseif (!password_verify($currentPassword, (string)$user['password_hash'])) {
            $deleteError = 'Your password is incorrect.';
        } elseif ($confirm !== 'DELETE') {
            $deleteError = 'Type DELETE exactly to confirm account removal.';
        } else {
            $username = strtolower((string)$user['username']);

            $ownedFiles = [];
            $keptPosts = [];
            foreach (readJson('posts') as $post) {
                if (strtolower((string)($post['author'] ?? '')) === $username) {
                    if (($post['image'] ?? '') !== '') $ownedFiles[] = (string)$post['image'];
                    continue;
                }
                $keptPosts[] = $post;
            }
            writeJson('posts', $keptPosts);

            $keptReplies = [];
            foreach (readJson('replies') as $reply) {
                if (strtolower((string)($reply['author'] ?? '')) === $username) continue;
                $keptReplies[] = $reply;
            }
            writeJson('replies', $keptReplies);

            $avatar = (string)($user['avatar'] ?? '');
            if ($avatar !== '') $ownedFiles[] = $avatar;
            foreach ($ownedFiles as $file) removePrivateUpload((string)$file);

            updateJson('users', function (array &$users) use ($user): void {
                $users = array_values(array_filter($users, fn($item) => (string)($item['id'] ?? '') !== (string)$user['id']));
            });
            updateJson('user_actions', function (array &$rows) use ($user): void {
                $rows = array_values(array_filter($rows, fn($item) => (string)($item['user_id'] ?? '') !== (string)$user['id']));
            });
            logoutUser();
            redirect('register.php?deleted=1');
        }
    }
}

$user = currentUser();
renderHeader('Settings');
?>
<section class="settings-page">
    <div class="settings-intro">
        <div class="eyebrow">account</div>
        <h1>Settings.</h1>
        <p class="lede">Keep your profile simple, or make it feel like yours.</p>
    </div>

    <?php if ($success !== ''): ?><div class="success">Your <?= h($success) ?> settings were saved.</div><?php endif; ?>

    <div class="settings-layout">
        <aside class="settings-nav">
            <a href="#profile">Profile</a>
            <a href="#language">Language</a>
            <a href="#appearance">Appearance</a>
            <a href="#privacy">Privacy</a>
            <a href="#account">Account</a>
        </aside>

        <div class="settings-main">
            <section class="settings-section" id="profile">
                <div class="section-heading"><div><h2>Profile</h2><p>What other people see.</p></div></div>
                <?php if ($profileErrors): ?><div class="alert" role="alert"><?php foreach ($profileErrors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="section" value="profile">
                    <div class="field"><label for="display_name">Display name</label><input id="display_name" name="display_name" maxlength="40" value="<?= h((string)$user['display_name']) ?>" required></div>
                    <div class="field"><label for="bio">Bio</label><textarea id="bio" name="bio" maxlength="280" placeholder="A small description of you."><?= h((string)$user['bio']) ?></textarea></div>
                    <div class="field"><label for="website">Website</label><input id="website" name="website" type="url" maxlength="2048" value="<?= h((string)$user['website']) ?>" placeholder="https://..."></div>
                    <div class="field"><label for="avatar">Avatar</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/gif,image/webp"><div class="hint">Max 2 MB. Your avatar is public when used.</div></div>
                    <div class="form-actions"><button class="btn accent">Save profile</button></div>
                </form>
            </section>


            <section class="settings-section" id="language">
                <div class="section-heading"><div><h2>Language</h2><p>Choose the language used by the Webmore interface.</p></div></div>
                <?php if ($languageErrors): ?><div class="alert" role="alert"><?php foreach ($languageErrors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="section" value="language">
                    <div class="field"><label for="language">Language</label><select id="language" name="language"><option value="en" <?= ($user['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option><option value="ja" <?= ($user['language'] ?? 'en') === 'ja' ? 'selected' : '' ?>>日本語</option></select></div>
                    <div class="form-actions"><button class="btn accent">Save language</button></div>
                </form>
            </section>

            <section class="settings-section" id="appearance">
                <div class="section-heading"><div><h2>Appearance</h2><p>Choose how Webmore looks for you.</p></div></div>
                <?php if ($appearanceErrors): ?><div class="alert" role="alert"><?php foreach ($appearanceErrors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="section" value="appearance">
                    <div class="field"><label for="theme">Theme</label><select id="theme" name="theme"><option value="system" <?= $user['theme'] === 'system' ? 'selected' : '' ?>>System default</option><option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>>Light</option><option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option></select></div>
                    <div class="field color-field"><label for="accent">Accent</label><div class="color-control"><input id="accent" name="accent" type="color" value="<?= h((string)$user['accent']) ?>"><code><?= h(strtoupper((string)$user['accent'])) ?></code></div><div class="hint">Used for buttons, small labels, and links.</div></div>
                    <div class="form-actions"><button class="btn accent">Save appearance</button></div>
                </form>
            </section>

            <section class="settings-section" id="privacy">
                <div class="section-heading"><div><h2>Privacy</h2><p>See what is kept private and take a copy of your account data.</p></div></div>
                <div class="account-details">
                    <div><span class="detail-label">Public</span><strong>Posts, replies, display name, username, bio, website, and avatar</strong></div>
                    <div><span class="detail-label">Private</span><strong>Email, password, and age-check record</strong></div>
                </div>
                <p class="hint">The exact date of birth used during signup is not retained. Webmore keeps only the time the 13+ check was completed.</p>
                <div class="danger-zone">
                    <div><strong>Download your data</strong><p>Get a copy of your account, posts, replies, and profile information. Password hashes are never included.</p></div>
                    <a class="btn" href="export.php">Download</a>
                </div>
                <div class="danger-zone">
                    <div><strong>Read the privacy policy</strong><p>See the full description of data handling and your choices.</p></div>
                    <a class="btn" href="privacy.php">Privacy</a>
                </div>
            </section>

            <section class="settings-section" id="account">
                <div class="section-heading"><div><h2>Account</h2><p>Login details, password, and account removal.</p></div></div>
                <?php if ($passwordErrors): ?><div class="alert" role="alert"><?php foreach ($passwordErrors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
                <div class="account-details">
                    <div><span class="detail-label">Username</span><strong>@<?= h((string)$user['username']) ?></strong></div>
                    <div><span class="detail-label">Email</span><strong><?= h((string)$user['email']) ?></strong></div>
                </div>
                <form method="post" class="password-form">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="section" value="password">
                    <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
                    <div class="row"><div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required autocomplete="new-password"></div><div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required autocomplete="new-password"></div></div>
                    <div class="form-actions"><button class="btn">Change password</button></div>
                </form>
                <div class="danger-zone"><div><strong>Sign out</strong><p>End your current Webmore session on this device.</p></div><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><button class="btn" type="submit">Log out</button></form></div>
                <div class="danger-zone account-delete-zone"><div><strong>Delete account</strong><p>This removes your account, profile, posts, replies, and uploaded images from this installation.</p></div></div>
                <?php if ($deleteError): ?><div class="alert account-delete-alert" role="alert"><?= h($deleteError) ?></div><?php endif; ?>
                <form method="post" data-confirm="Delete this account and all of its content? This cannot be undone.">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="section" value="delete_account">
                    <div class="row">
                        <div class="field"><label for="delete_password">Current password</label><input id="delete_password" name="delete_password" type="password" required autocomplete="current-password"></div>
                        <div class="field"><label for="confirm_delete">Type DELETE</label><input id="confirm_delete" name="confirm_delete" autocomplete="off" required placeholder="DELETE"></div>
                    </div>
                    <div class="form-actions"><button class="btn danger-btn" type="submit">Delete account permanently</button></div>
                </form>
            </section>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
