<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$posts = readJson('posts');
if (!$posts) {
    redirect('index.php');
}
$post = $posts[array_rand($posts)];
redirect('post.php?id=' . urlencode((string)$post['id']));
