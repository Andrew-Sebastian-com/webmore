<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$mode = (($_GET['view'] ?? 'favorites') === 'archive') ? 'archive' : 'favorites';
$bucket = actionBucketForUser($user);
$ids = $mode === 'archive' ? $bucket['archives'] : $bucket['favorites'];
$idMap = array_fill_keys($ids, true);
$posts = array_values(array_filter(readJson('posts'), static fn($post): bool => isset($idMap[(string)($post['id'] ?? '')])));
usort($posts, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));

renderHeader($mode === 'archive' ? 'Archive' : 'Favourites');
?>
<section class="compact-hero">
    <div class="eyebrow">your saved corner</div>
    <h1><?= $mode === 'archive' ? 'Archive.' : 'Favourites.' ?></h1>
    <p><?= $mode === 'archive' ? 'Posts you tucked away for later. Yours or someone else’s. Only you can see this list.' : 'Posts you decided were worth keeping close. Only you can see this list.' ?></p>
</section>
<div class="saved-tabs" role="tablist" aria-label="Saved posts">
    <a class="filter <?= $mode === 'favorites' ? 'active' : '' ?>" href="saved.php?view=favorites">Favourites (<?= count($bucket['favorites']) ?>)</a>
    <a class="filter <?= $mode === 'archive' ? 'active' : '' ?>" href="saved.php?view=archive">Archive (<?= count($bucket['archives']) ?>)</a>
</div>
<div class="feed">
<?php if (!$posts): ?>
    <div class="empty"><strong>Nothing here yet.</strong><p><?= $mode === 'archive' ? 'Archive something when you are not ready to let it disappear into the feed.' : 'Favourite something when you want it easy to find again.' ?></p><a class="btn accent small" href="explore.php">Go exploring</a></div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php include __DIR__ . '/post-card.php'; ?>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<?php renderFooter(); ?>
