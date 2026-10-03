<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/replies.php';
requireLogin();

$user = currentUser();
$username = strtolower((string)$user['username']);

$posts = array_values(array_filter(
    readJson('posts'),
    static fn(array $post): bool => strtolower((string)($post['author'] ?? '')) === $username
));

$replies = array_values(array_filter(
    readJson('replies'),
    static fn(array $reply): bool => strtolower((string)($reply['author'] ?? '')) === $username
));

$export = [
    'exported_at' => gmdate('c'),
    'profile' => [
        'username' => $user['username'],
        'email' => $user['email'],
        'display_name' => $user['display_name'],
        'bio' => $user['bio'],
        'website' => $user['website'],
        'avatar' => $user['avatar'],
        'theme' => $user['theme'],
        'accent' => $user['accent'],
        'created_at' => $user['created_at'],
        'age_verified_at' => $user['age_verified_at'],
        'terms_accepted_at' => $user['terms_accepted_at'],
        'policy_version' => $user['policy_version'],
    ],
    'posts' => $posts,
    'replies' => $replies,
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="webmore-data-' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$user['username']) . '.json"');
header('Cache-Control: no-store');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
