<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$user = currentUser();
if ($user) verifyCsrf();

$language = (string)($_POST['language'] ?? 'en');
if (!in_array($language, ['en', 'ja'], true)) $language = 'en';

setLanguageCookie($language);
if ($user) {
    $user['language'] = $language;
    saveUser($user);
}


$back = safeNext((string)($_POST['back'] ?? 'index.php'));
redirect($back);
