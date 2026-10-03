<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/replies.php';

$id = (string)($_GET['id'] ?? '');
$post = postById($id);

if (!$post) {
    http_response_code(404);
    renderHeader('Not found');
    echo '<div class="empty">Post not found.</div>';
    renderFooter();
    exit;
}

$user = currentUser();
$replyError = '';
$replySuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)($_POST['action'] ?? 'reply');

    if (in_array($action, ['favorite', 'archive', 'delete'], true)) {
        if (!throttle('post_action', 40, 60)) {
            $replyError = 'You are changing this post a little too quickly. Please wait a moment.';
        } elseif (!$user) {
        if ($action === 'favorite') {
            togglePostFavorite($user, $id);
            redirect('post.php?id=' . rawurlencode($id));
        }
        if ($action === 'archive') {
            togglePostArchive($user, $id);
            redirect('post.php?id=' . rawurlencode($id));
        }
        if ($action === 'delete') {
            if (strtolower((string)$post['author']) === strtolower((string)$user['username'])) {
                deletePost($id, $user);
                redirect('profile.php?user=' . urlencode((string)$user['username']));
            }
            $replyError = 'You can only delete your own posts.';
        }
        }
    } elseif (!throttle('reply', 20, 60)) {
        $replyError = 'You are replying a little too quickly. Please wait a moment.';
    } elseif ($action === 'delete_reply') {
        if (!$user) {
            redirect('login.php?next=' . urlencode('post.php?id=' . $id . '#replies'));
        }
        $replyId = (string)($_POST['reply_id'] ?? '');
        if (deleteReply($replyId, $user)) {
            $replySuccess = 'Reply removed.';
        } else {
            $replyError = 'That reply could not be removed.';
        }
    } else {
        if (!$user) {
            redirect('login.php?next=' . urlencode('post.php?id=' . $id . '#replies'));
        }
        $content = trim((string)($_POST['content'] ?? ''));
        if ($content === '') {
            $replyError = 'Write something before posting.';
        } elseif (u_strlen($content) > 2000) {
            $replyError = 'Replies must be 2,000 characters or fewer.';
        } else {
            addReply($id, $user, $content);
            $replySuccess = 'Reply posted.';
            $_POST['content'] = '';
        }
    }
}

$replies = repliesForPost($id);
renderHeader((string)$post['title']);
?>
<section class="panel post-page">
    <a class="backlink" href="index.php">← Back to feed</a>
    <div class="detail-card">
        <?php include __DIR__ . '/post-card.php'; ?>
    </div>

    <section class="replies-section" id="replies" aria-labelledby="replies-title">
        <div class="replies-head">
            <div>
                <div class="section-kicker">conversation</div>
                <h2 id="replies-title">Replies <span class="reply-count"><?= count($replies) ?></span></h2>
            </div>
        </div>

        <?php if ($replyError !== ''): ?><div class="alert"><?= h($replyError) ?></div><?php endif; ?>
        <?php if ($replySuccess !== ''): ?><div class="success"><?= h($replySuccess) ?></div><?php endif; ?>

        <?php if ($user): ?>
            <form class="reply-form" method="post" action="post.php?id=<?= urlencode($id) ?>#replies">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="reply">
                <div class="reply-form-head">
                    <?= avatarMarkup($user, 'avatar tiny') ?>
                    <span><strong><?= h((string)$user['display_name']) ?></strong><small>@<?= h((string)$user['username']) ?></small></span>
                </div>
                <textarea name="content" maxlength="2000" placeholder="Say something..." aria-label="Reply" required><?= h((string)($_POST['content'] ?? '')) ?></textarea>
                <div class="reply-form-footer">
                    <span class="hint">2,000 characters max.</span>
                    <button class="btn accent small" type="submit">Reply</button>
                </div>
            </form>
        <?php else: ?>
            <div class="reply-login">
                <div>
                    <strong>Want to join the conversation?</strong>
                    <p>You can read everything without an account. Sign in when you want to reply.</p>
                </div>
                <div class="hero-actions compact-actions">
                    <a class="btn" href="login.php?next=<?= urlencode('post.php?id=' . $id . '#replies') ?>">Log in</a>
                    <a class="btn accent small" href="register.php">Create an account</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="reply-list">
            <?php if (!$replies): ?>
                <div class="empty reply-empty">No replies yet. It seems someone has to be first.</div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <?php $replyUser = userByUsername((string)($reply['author'] ?? '')) ?? ['username'=>(string)($reply['author'] ?? 'unknown'),'display_name'=>(string)($reply['author'] ?? 'unknown'),'avatar'=>'']; ?>
                    <article class="reply-item">
                        <div class="reply-item-head">
                            <a class="author-link" href="profile.php?user=<?= urlencode((string)$replyUser['username']) ?>">
                                <?= avatarMarkup($replyUser, 'avatar tiny') ?>
                                <span><strong><?= h((string)$replyUser['display_name']) ?></strong><small>@<?= h((string)$replyUser['username']) ?></small></span>
                            </a>
                            <span class="meta"><?= h(formatTime((int)($reply['created_at'] ?? time()))) ?></span>
                        </div>
                        <p class="reply-content user-generated"><?= nl2br(h((string)($reply['content'] ?? ''))) ?></p>
                        <?php if ($user && strtolower((string)$user['username']) === strtolower((string)($reply['author'] ?? ''))): ?>
                            <form method="post" action="post.php?id=<?= urlencode($id) ?>#replies" class="reply-delete-form" data-confirm="Delete this reply?">
                                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete_reply">
                                <input type="hidden" name="reply_id" value="<?= h((string)$reply['id']) ?>">
                                <button class="text-button danger-text" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php renderFooter(); ?>
