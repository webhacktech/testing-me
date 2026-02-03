<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$messages = [];
$admin_count = $mysqli->query('SELECT COUNT(*) as total FROM admin')->fetch_assoc();
if ($admin_count['total'] > 0) {
    redirect(BASE_URL . '/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare('INSERT INTO admin (name, email, password, role) VALUES (?, ?, ?, "superadmin")');
        $stmt->bind_param('sss', $name, $email, $hashed);
        $stmt->execute();
        $messages[] = 'Admin created successfully. Please login.';
    }
}
?>
<div class="bg-white p-8 rounded shadow max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Create Admin Account</h2>
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
            <label class="block text-sm mb-1">Full name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Create Admin</button>
    </form>
</div>
<?php
require_once __DIR__ . '/footer.php';
?>
