<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$messages = [];
$errors = [];
$token = sanitize($_GET['token'] ?? '');

if ($token === '') {
    $errors[] = 'Verification token missing.';
} else {
    $stmt = $mysqli->prepare('SELECT id, email_verified FROM users WHERE verification_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        if ((int)$user['email_verified'] === 1) {
            $messages[] = 'Email already verified. You can login.';
        } else {
            $update = $mysqli->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?');
            $update->bind_param('i', $user['id']);
            $update->execute();
            $messages[] = 'Email verified successfully. You can now login.';
        }
    } else {
        $errors[] = 'Invalid or expired token.';
    }
}
?>
<div class="bg-white p-8 rounded-2xl card-shadow max-w-xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Email Verification</h1>
    <?php if ($messages): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            <?php foreach ($messages as $message): ?>
                <p><?php echo $message; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn-primary text-white px-5 py-3 rounded-lg">Go to login</a>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
