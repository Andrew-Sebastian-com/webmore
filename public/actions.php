<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

verifyCsrf();
$user = currentUser();
if (!$user) redirect('login.php');

$id = (string)($_POST['post_id'] ?? '');
$post = postById($id);
if (!$post) {
    if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Post not found.']);
        exit;
    }
    redirect('index.php');
}

if (!throttle('post_action', 40, 60)) {
    if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Too many actions.']);
        exit;
    }
    redirect('post.php?id=' . rawurlencode($id));
}

$action = (string)($_POST['action'] ?? '');
$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$ok = true;
$state = null;

switch ($action) {
    case 'like':
        $state = togglePostLike($user, $id);
        break;
    case 'favorite':
        $state = togglePostFavorite($user, $id);
        break;
    case 'archive':
        $state = togglePostArchive($user, $id);
        break;
    case 'delete':
        if (strtolower((string)$post['author']) === strtolower((string)$user['username'])) {
            $ok = deletePost($id, $user);
        } else {
            $ok = false;
        }
        break;
    default:
        $ok = false;
        break;
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $fresh = postById($id);
    echo json_encode([
        'ok' => $ok,
        'action' => $action,
        'state' => $state,
        'deleted' => $action === 'delete' && $ok,
        'liked' => $fresh ? isPostLiked($user, $id) : false,
        'favorited' => $fresh ? isPostFavorited($user, $id) : false,
        'archived' => $fresh ? isPostArchived($user, $id) : false,
        'likes' => $fresh ? max(0, (int)($fresh['likes'] ?? 0)) : 0,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'delete' && $ok) {
    redirect('profile.php?user=' . urlencode((string)$user['username']));
}

$back = safeNext((string)($_POST['back'] ?? 'post.php?id=' . $id));
redirect($back);
