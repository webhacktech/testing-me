<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email_verified FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            if ((int)$user['email_verified'] === 1) {
                $messages[] = 'Email already verified. Please login.';
            } else {
                $token = bin2hex(random_bytes(16));
                $update = $mysqli->prepare('UPDATE users SET verification_token = ?, verification_sent_at = NOW() WHERE id = ?');
                $update->bind_param('si', $token, $user['id']);
                $update->execute();
                $email_body = verification_email($user['name'], $token);
                $sent = send_email($email, APP_NAME . ' Email Verification', $email_body);
                log_email($user['id'], APP_NAME . ' Email Verification', $email_body, $sent ? 'sent' : 'failed');
                $messages[] = 'Verification email resent. Check your inbox.';
            }
        } else {
            $errors[] = 'Email not found.';
        }
    }
}
?>
<div class="bg-white p-8 rounded-2xl card-shadow max-w-lg mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Resend Verification</h1>
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
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <button class="btn-primary text-white px-5 py-3 rounded-lg" type="submit">Resend Verification</button>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
