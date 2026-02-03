<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
if (isset($_GET['verify'])) {
    $errors[] = 'Please verify your email before logging in. Check your inbox.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $honeypot = $_POST['website'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if ($honeypot !== '') {
        $errors[] = 'Bot detection triggered.';
    } elseif (!rate_limit_check($ip, 'user_login')) {
        $errors[] = 'Too many login attempts. Try again later.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, password, status, email_verified FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['email_verified'] !== 1) {
                $errors[] = 'Email not verified. Please verify before logging in.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Account is not active. Contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                redirect(BASE_URL . '/public/dashboard.php');
            }
        } else {
            log_attempt($ip, 'user_login');
            $errors[] = 'Invalid credentials.';
        }
    }
}
?>
<div class="bg-white p-8 rounded shadow max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Login</h2>
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
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="hidden">
            <label>Website</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Login</button>
    </form>
    <p class="text-sm text-gray-600 mt-4">No verification email? <a class="text-[#106b28]" href="<?php echo BASE_URL; ?>/public/resend_verification.php">Resend verification</a></p>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
