<?php
declare(strict_types=1);

function replyById(string $id): ?array {
    if (!preg_match('/^reply-[a-f0-9]{24}$/', $id)) return null;
    foreach (readJson('replies') as $reply) {
        if (($reply['id'] ?? '') === $id) return $reply;
    }
    return null;
}

function repliesForPost(string $postId): array {
    $replies = array_values(array_filter(
        readJson('replies'),
        fn(array $reply): bool => (string)($reply['post_id'] ?? '') === $postId
    ));
    usort($replies, fn($a, $b) => ($a['created_at'] ?? 0) <=> ($b['created_at'] ?? 0));
    return $replies;
}

function addReply(string $postId, array $user, string $content): array {
    $reply = [
        'id' => makeReplyId(),
        'post_id' => $postId,
        'author' => (string)$user['username'],
        'content' => trim($content),
        'created_at' => time(),
    ];
    updateJson('replies', function (array &$replies) use ($reply): void { $replies[] = $reply; });
    return $reply;
}

function deleteReply(string $replyId, array $user): bool {
    if (!preg_match('/^reply-[a-f0-9]{24}$/', $replyId)) return false;
    return (bool)updateJson('replies', function (array &$replies) use ($replyId, $user): bool {
        $changed = false;
        $filtered = [];
        foreach ($replies as $reply) {
            $isTarget = (string)($reply['id'] ?? '') === $replyId;
            $isOwner = strtolower((string)($reply['author'] ?? '')) === strtolower((string)$user['username']);
            if ($isTarget && $isOwner) {
                $changed = true;
                continue;
            }
            $filtered[] = $reply;
        }
        if ($changed) $replies = $filtered;
        return $changed;
    });
}
