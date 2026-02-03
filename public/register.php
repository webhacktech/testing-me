<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $honeypot = $_POST['website'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Bot detection triggered.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Email already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(16));
            $stmt = $mysqli->prepare('INSERT INTO users (name, email, password, phone, email_verified, verification_token, verification_sent_at, kyc_status, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW(), "Pending", NOW())');
            $stmt->bind_param('sssss', $name, $email, $hashed, $phone, $token);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            ensure_wallets($user_id);
            add_notification($user_id, 'account', 'Welcome to ' . APP_NAME . '. Verify your email and complete KYC to trade.');
            $email_body = verification_email($name, $token);
            $sent = send_email($email, APP_NAME . ' Email Verification', $email_body);
            log_email($user_id, APP_NAME . ' Email Verification', $email_body, $sent ? 'sent' : 'failed');
            redirect(BASE_URL . '/public/login.php?verify=1');
        }
    }
}
?>
<div class="bg-white p-8 rounded shadow max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Create your account</h2>
    <?php if ($errors): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm mb-1">Full name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Phone</label>
            <input type="text" name="phone" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Confirm password</label>
            <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="hidden">
            <label>Website</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Register</button>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
