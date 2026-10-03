<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$username = trim((string)($_GET['user'] ?? (currentUser()['username'] ?? '')));
if ($username === '') {
    redirect('index.php');
}

$profile = userByUsername($username);
if (!$profile) {
    http_response_code(404);
    renderHeader('User not found');
    echo '<div class="empty">That account does not exist.</div>';
    renderFooter();
    exit;
}

$posts = array_values(array_filter(readJson('posts'), fn($p) => strtolower((string)($p['author'] ?? '')) === strtolower($profile['username'])));
usort($posts, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
$isMine = currentUser() && strtolower(currentUser()['username']) === strtolower($profile['username']);

renderHeader('@' . $profile['username']);
?>
<section class="profile-page">
    <div class="profile-head">
        <?= avatarMarkup($profile, 'avatar profile-avatar') ?>
        <div class="profile-main">
            <div class="eyebrow">profile</div>
            <h1><?= h((string)$profile['display_name']) ?></h1>
            <div class="profile-handle">@<?= h((string)$profile['username']) ?></div>
            <?php if (($profile['bio'] ?? '') !== ''): ?><p class="profile-bio"><?= nl2br(h((string)$profile['bio'])) ?></p><?php endif; ?>
            <div class="profile-meta-row">
                <span><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?></span>
                <span>Joined <?= h(date('M j, Y', (int)$profile['created_at'])) ?></span>
                <?php if (($profile['website'] ?? '') !== '' && validUrl((string)$profile['website'])): ?>
                    <a href="<?= h($profile['website']) ?>" target="_blank" rel="noopener noreferrer">Website ↗</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($isMine): ?>
            <div class="profile-actions">
                <a class="btn" href="settings.php#profile">Edit profile</a>
                <a class="btn ghost" href="settings.php">Settings</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="feed-head">
        <h2>Posts</h2>
        <?php if ($isMine): ?><a class="text-link" href="create.php">+ New post</a><?php endif; ?>
    </div>
    <div class="feed">
        <?php if (!$posts): ?>
            <div class="empty"><?= $isMine ? 'You have not posted anything yet.' : 'This account has not posted anything yet.' ?></div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/post-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php renderFooter(); ?>
