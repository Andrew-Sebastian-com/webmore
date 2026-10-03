<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!throttle('create', 10, 600)) {
        $errors[] = 'You are posting a little too quickly. Please wait a moment.';
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $link = trim((string)($_POST['link'] ?? ''));
    $tagsRaw = trim((string)($_POST['tags'] ?? ''));
    $imagePath = '';

    if (count($errors) === 0) {
        if ($title === '') $errors[] = 'A title is required.';
        if (u_strlen($title) > 120) $errors[] = 'Title is too long.';
        if (u_strlen($content) > 5000) $errors[] = 'Thought is too long.';
        if (u_strlen($link) > 2048 || ($link !== '' && !validUrl($link))) $errors[] = 'Link must be a valid http:// or https:// URL.';

        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'The image upload failed.';
            } else {
                $imagePath = saveUploadedImage($file, 'post', MAX_IMAGE_BYTES) ?? '';
                if ($imagePath === '') $errors[] = 'Please upload a real JPG, PNG, GIF, or WebP image up to 5 MB and 7000×7000 pixels.';
            }
        }
    }

    $tags = array_values(array_unique(array_filter(array_map(
        static fn($tag) => u_strtolower(trim((string)$tag)),
        explode(',', $tagsRaw)
    ))));
    $tags = array_values(array_filter($tags, static fn($tag) => $tag !== '' && u_strlen($tag) <= 32 && preg_match('/^[\p{L}\p{N}_ -]+$/u', $tag) === 1));
    $tags = array_slice($tags, 0, 8);

    if ($errors && $imagePath !== '') {
        removePrivateUpload($imagePath);
        $imagePath = '';
    }

    if (!$errors) {
        $user = currentUser();
        $post = [
            'id' => makePostId(),
            'title' => $title,
            'content' => $content,
            'image' => $imagePath,
            'link' => $link,
            'tags' => $tags,
            'author' => $user['username'],
            'created_at' => time(),
            'likes' => 0,
        ];
        updateJson('posts', function (array &$posts) use ($post): void { array_unshift($posts, $post); });
        redirect('post.php?id=' . rawurlencode($post['id']));
    }
}

renderHeader('Create');
?>
<section class="panel">
    <div class="form-card">
        <div class="eyebrow">new post</div>
        <h1>Leave something here.</h1>
        <p class="lede">A thought, a picture, a link, or an unnecessarily specific observation.</p>
        <?php if ($errors): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="field"><label for="title">Title</label><input id="title" name="title" required maxlength="120" value="<?= h($_POST['title'] ?? '') ?>"></div>
            <div class="field"><label for="content">Text</label><textarea id="content" name="content" maxlength="5000" placeholder="What did you notice?"><?= h($_POST['content'] ?? '') ?></textarea></div>
            <div class="field"><label for="link">Link</label><input id="link" name="link" type="url" maxlength="2048" value="<?= h($_POST['link'] ?? '') ?>" placeholder="https://..."><div class="hint">Only http:// and https:// links are accepted.</div></div>
            <div class="field"><label for="image">Image</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"><div class="hint">JPG, PNG, GIF, or WebP. Max 5 MB and 7000×7000 pixels. Avoid uploading images that contain private metadata.</div></div>
            <div class="field"><label for="tags">Tags</label><input id="tags" name="tags" maxlength="200" value="<?= h($_POST['tags'] ?? '') ?>" placeholder="weird, thought, internet"><div class="hint">Comma separated, up to 8 tags.</div></div>
            <div class="form-actions"><button class="btn accent" type="submit">Publish</button></div>
        </form>
    </div>
</section>
<?php renderFooter(); ?>
