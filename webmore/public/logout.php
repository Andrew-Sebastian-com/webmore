<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verifyCsrf();
header('Clear-Site-Data: "cache", "cookies", "storage"');
logoutUser();
redirect('index.php');
