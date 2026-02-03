<?php
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app.css">
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js" defer></script>
</head>
<body class="bg-gray-100 text-gray-900">
<nav class="bg-white shadow-sm">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <a class="text-xl font-bold" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a>
        <div class="space-x-4">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <a class="text-sm hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                <a class="text-sm hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/admin/emails.php">Emails</a>
                <a class="text-sm hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/admin/users.php">Users</a>
                <a class="text-sm hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/admin/logout.php">Logout</a>
            <?php else: ?>
                <a class="text-sm hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/admin/login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="max-w-6xl mx-auto px-4 py-8">
