<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$post = postById($id);
if (!$post || strtolower((string)$post['author']) !== strtolower((string)$user['username'])) {
    http_response_code(403);
    renderHeader('Not available');
    echo '<section class="panel"><div class="empty"><strong>This post is not yours to edit.</strong><p>Other people\'s posts are yours to read, save, favourite, and archive. Editing stays with the author.</p></div></section>';
    renderFooter();
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!throttle('edit', 10, 600)) $errors[] = 'You are editing a little too quickly. Please wait a moment.';

    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $link = trim((string)($_POST['link'] ?? ''));
    $tagsRaw = trim((string)($_POST['tags'] ?? ''));

    if ($title === '') $errors[] = 'A title is required.';
    if (u_strlen($title) > 120) $errors[] = 'Title is too long.';
    if (u_strlen($content) > 5000) $errors[] = 'Thought is too long.';
    if (u_strlen($link) > 2048 || ($link !== '' && !validUrl($link))) $errors[] = 'Link must be a valid http:// or https:// URL.';

    $tags = array_values(array_unique(array_filter(array_map(
        static fn($tag) => u_strtolower(trim((string)$tag)),
        explode(',', $tagsRaw)
    ))));
    $tags = array_values(array_filter($tags, static fn($tag) => $tag !== '' && u_strlen($tag) <= 32 && preg_match('/^[\p{L}\p{N}_ -]+$/u', $tag) === 1));
    $tags = array_slice($tags, 0, 8);

    $newImage = null;
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $newImage = saveUploadedImage($_FILES['image'], 'post', MAX_IMAGE_BYTES);
        if ($newImage === null) $errors[] = 'Please upload a real JPG, PNG, GIF, or WebP image up to 5 MB and 7000×7000 pixels.';
    }

    if (!$errors) {
        $updates = ['title'=>$title, 'content'=>$content, 'link'=>$link, 'tags'=>$tags];
        if ($newImage !== null) $updates['image'] = $newImage;
        elseif ($removeImage) $updates['image'] = '';
        if (updatePost($id, $user, $updates)) {
            if (($newImage !== null || $removeImage) && ($post['image'] ?? '') !== '') removePrivateUpload((string)$post['image']);
            redirect('post.php?id=' . rawurlencode($id));
        }
        if ($newImage !== null) removePrivateUpload($newImage);
        $errors[] = 'The post could not be updated.';
    }
}

$display = $post;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display['title'] = (string)($_POST['title'] ?? $post['title']);
    $display['content'] = (string)($_POST['content'] ?? $post['content']);
    $display['link'] = (string)($_POST['link'] ?? $post['link']);
    $display['tags'] = array_filter(array_map('trim', explode(',', (string)($_POST['tags'] ?? implode(', ', $post['tags'] ?? [])))));
}

renderHeader('Edit post');
?>
<section class="panel">
    <div class="form-card">
        <a class="backlink" href="post.php?id=<?= urlencode($id) ?>">← Back to post</a>
        <div class="eyebrow">edit</div>
        <h1>Change your mind.</h1>
        <p class="lede">You can update anything you posted. The post will keep its original place in the archive and show when it was updated.</p>
        <?php if ($errors): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= h($id) ?>">
            <div class="field"><label for="title">Title</label><input id="title" name="title" required maxlength="120" value="<?= h((string)$display['title']) ?>"></div>
            <div class="field"><label for="content">Text</label><textarea id="content" name="content" maxlength="5000"><?= h((string)$display['content']) ?></textarea></div>
            <div class="field"><label for="link">Link</label><input id="link" name="link" type="url" maxlength="2048" value="<?= h((string)$display['link']) ?>" placeholder="https://..."></div>
            <div class="field"><label for="image">Replace image</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"><div class="hint">Leave this alone to keep the current image.</div></div>
            <?php if (($post['image'] ?? '') !== ''): ?><label class="check-row"><input type="checkbox" name="remove_image" value="1"> <span>Remove the current image</span></label><?php endif; ?>
            <div class="field"><label for="tags">Tags</label><input id="tags" name="tags" maxlength="200" value="<?= h(implode(', ', $display['tags'] ?? [])) ?>"></div>
            <div class="form-actions"><a class="btn ghost" href="post.php?id=<?= urlencode($id) ?>">Cancel</a><button class="btn accent" type="submit">Save changes</button></div>
        </form>
    </div>
</section>
<?php renderFooter(); ?>
