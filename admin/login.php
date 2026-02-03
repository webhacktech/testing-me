<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$admin_count = $mysqli->query('SELECT COUNT(*) as total FROM admin')->fetch_assoc();
if ($admin_count['total'] == 0) {
    redirect(BASE_URL . '/admin/setup.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!rate_limit_check($ip, 'admin_login')) {
        $errors[] = 'Too many login attempts. Try again later.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, password FROM admin WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            log_attempt($ip, 'admin_login');
            $errors[] = 'Invalid credentials.';
        }
    }
}
?>
<div class="bg-white p-8 rounded shadow max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Admin Login</h2>
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
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Login</button>
    </form>
</div>
<?php
require_once __DIR__ . '/footer.php';
?>
