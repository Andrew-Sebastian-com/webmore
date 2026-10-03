<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$q = trim((string)($_GET['q'] ?? ''));
$sort = $_GET['sort'] ?? 'new';
$posts = readJson('posts');

if ($q !== '') {
    $needle = u_strtolower($q);
    $posts = array_values(array_filter($posts, function (array $post) use ($needle): bool {
        $haystack = u_strtolower(($post['title'] ?? '') . ' ' . ($post['content'] ?? '') . ' ' . implode(' ', $post['tags'] ?? []));
        return str_contains($haystack, $needle);
    }));
}

if ($sort === 'popular') {
    usort($posts, fn($a, $b) => ($b['likes'] ?? 0) <=> ($a['likes'] ?? 0));
} else {
    usort($posts, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
}

renderHeader('Explore');
?>
<section class="compact-hero">
    <div class="eyebrow">explore</div>
    <h1><?= $q !== '' ? 'Results for “' . h($q) . '”' : 'Explore everything.' ?></h1>
    <p>Search through the strange little archive.</p>
</section>
<div class="feed-head">
    <h2><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?></h2>
    <div class="filters">
        <a class="filter <?= $sort !== 'popular' ? 'active' : '' ?>" href="explore.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">New</a>
        <a class="filter <?= $sort === 'popular' ? 'active' : '' ?>" href="explore.php?sort=popular<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">Popular</a>
    </div>
</div>
<div class="feed">
<?php if (!$posts): ?>
    <div class="empty">Nothing found. That is either a bug or a very successful search.</div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php include __DIR__ . '/post-card.php'; ?>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<?php renderFooter(); ?>
