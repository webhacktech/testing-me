<?php
require_once __DIR__ . '/config.php';
$page_title = $page_title ?? APP_NAME;
$page_description = $page_description ?? 'Buy, sell, swap, and withdraw crypto safely in Nigeria with admin-controlled rates and full compliance.';
$page_image = $page_image ?? (BASE_URL . '/assets/images/og-image.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($page_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app.css">
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js" defer></script>
</head>
<body class="bg-gray-50 text-gray-900">
<nav class="bg-white shadow-sm">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <a class="text-xl font-bold text-[#106b28]" href="<?php echo BASE_URL; ?>/index.php"><?php echo APP_NAME; ?></a>
        <div class="space-x-4 text-sm">
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/index.php">Home</a>
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/about.php">About</a>
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/faq.php">FAQ</a>
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/privacy.php">Privacy</a>
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/terms.php">Terms</a>
            <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/contact.php">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/dashboard.php">Dashboard</a>
                <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/logout.php">Logout</a>
            <?php else: ?>
                <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/login.php">Login</a>
                <a class="hover:text-[#106b28]" href="<?php echo BASE_URL; ?>/public/register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="max-w-6xl mx-auto px-4 py-8">
