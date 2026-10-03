<?php
require_once __DIR__ . '/replies.php';
$authorUser = userByUsername((string)($post['author'] ?? '')) ?? [
    'username' => (string)($post['author'] ?? 'unknown'),
    'display_name' => (string)($post['author'] ?? 'unknown'),
    'avatar' => '',
];
$currentViewer = currentUser();
$postId = (string)($post['id'] ?? '');
$isOwnPost = $currentViewer && strtolower((string)$currentViewer['username']) === strtolower((string)($post['author'] ?? ''));
$isFavorite = $currentViewer ? isPostFavorited($currentViewer, $postId) : false;
$isArchived = $currentViewer ? isPostArchived($currentViewer, $postId) : false;
$isLiked = $currentViewer ? isPostLiked($currentViewer, $postId) : false;
$likeCount = max(0, (int)($post['likes'] ?? 0));
$replyCount = count(repliesForPost($postId));
?>
<article class="card" data-post-id="<?= h($postId) ?>">
    <div class="meta author-row">
        <a class="author-link" href="profile.php?user=<?= urlencode((string)($authorUser['username'] ?? 'unknown')) ?>">
            <?= avatarMarkup($authorUser, 'avatar tiny') ?>
            <span><strong><?= h((string)($authorUser['display_name'] ?? 'unknown')) ?></strong><small>@<?= h((string)($authorUser['username'] ?? 'unknown')) ?></small></span>
        </a>
        <span class="dot">·</span>
        <span><?= h(formatTime((int)($post['created_at'] ?? time()))) ?><?php if (!empty($post['updated_at'])): ?> · edited<?php endif; ?></span>
    </div>

    <h3><a href="post.php?id=<?= urlencode($postId) ?>"><?= h((string)$post['title']) ?></a></h3>

    <?php if (($post['content'] ?? '') !== ''): ?>
        <p class="user-generated post-content"><?= nl2br(h((string)$post['content'])) ?></p>
    <?php endif; ?>

    <?php if (($post['image'] ?? '') !== ''): ?>
        <div class="card-media"><img src="<?= h(mediaUrl((string)$post['image'])) ?>" alt="" loading="lazy" data-lightbox></div>
    <?php endif; ?>

    <?php if (($post['link'] ?? '') !== ''): ?>
        <a class="linkbox" href="<?= h((string)$post['link']) ?>" target="_blank" rel="noopener noreferrer">
            <strong>Open link</strong>
            <div class="url" data-no-i18n><?= h((string)$post['link']) ?></div>
        </a>
    <?php endif; ?>

    <div class="card-footer post-toolbar">
        <div class="tags">
            <?php foreach (($post['tags'] ?? []) as $tag): ?>
                <a class="tag" href="explore.php?q=<?= urlencode((string)$tag) ?>">#<?= h((string)$tag) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="post-actions" aria-label="Post actions">
            <?php if ($currentViewer): ?>
                <form method="post" action="actions.php" class="inline-action-form" data-action-form>
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="post_id" value="<?= h($postId) ?>">
                    <input type="hidden" name="action" value="like">
                    <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
                    <button class="post-action <?= $isLiked ? 'active' : '' ?>" type="submit" aria-label="<?= $isLiked ? 'Unlike post' : 'Like post' ?>">
                        <?= $isLiked ? '♥' : '♡' ?> <span><?= $likeCount ?></span>
                    </button>
                </form>

                <form method="post" action="actions.php" class="inline-action-form" data-action-form>
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="post_id" value="<?= h($postId) ?>">
                    <input type="hidden" name="action" value="favorite">
                    <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
                    <button class="post-action <?= $isFavorite ? 'active' : '' ?>" type="submit" aria-label="<?= $isFavorite ? 'Remove from favourites' : 'Add to favourites' ?>">
                        <?= $isFavorite ? '★' : '☆' ?> <span>Favourite</span>
                    </button>
                </form>

                <form method="post" action="actions.php" class="inline-action-form" data-action-form>
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="post_id" value="<?= h($postId) ?>">
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
                    <button class="post-action <?= $isArchived ? 'active' : '' ?>" type="submit" aria-label="<?= $isArchived ? 'Remove from archive' : 'Add to archive' ?>">
                        <?= $isArchived ? '✓' : '＋' ?> <span>Archive</span>
                    </button>
                </form>
            <?php else: ?>
                <span class="reaction-static">♡ <?= $likeCount ?></span>
            <?php endif; ?>

            <a class="post-action reply-stat" href="post.php?id=<?= urlencode($postId) ?>#replies" aria-label="View replies">↩ <span><?= $replyCount ?></span></a>

            <button class="post-action copy-post-link" type="button" data-copy-link="post.php?id=<?= h(rawurlencode($postId)) ?>" aria-label="Copy post link">↗</button>

            <details class="action-menu">
                <summary class="post-action" aria-label="More post options">•••</summary>
                <div class="action-popover">
                    <?php if ($isOwnPost): ?>
                        <a class="action-menu-link" href="edit.php?id=<?= urlencode($postId) ?>">Edit</a>
                        <form method="post" action="actions.php" data-confirm="Delete this post? This also removes its replies and uploaded image.">
                            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="post_id" value="<?= h($postId) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="back" value="profile.php?user=<?= urlencode((string)$currentViewer['username']) ?>">
                            <button class="action-menu-link danger-text" type="submit">Delete</button>
                        </form>
                    <?php elseif ($currentViewer): ?>
                        <?php if (!hasReportedPost($currentViewer, $postId)): ?>
                            <a class="action-menu-link" href="report.php?id=<?= urlencode($postId) ?>">Report</a>
                        <?php else: ?>
                            <span class="action-menu-note">Report received</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="action-menu-link" href="login.php?next=<?= urlencode('post.php?id=' . $postId) ?>">Log in to save or report</a>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </div>
</article>
