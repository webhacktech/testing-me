<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        $errors[] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } else {
        $stmt = $mysqli->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $email, $phone, $user_id);
        $stmt->execute();
        $messages[] = 'User updated successfully.';
    }
}

$users = $mysqli->query('SELECT id, name, email, phone, status, kyc_status FROM users ORDER BY created_at DESC');
?>
<div class="bg-white p-8 rounded-2xl card-shadow">
    <h1 class="text-2xl font-semibold mb-4">Edit Users</h1>
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
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>KYC</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo sanitize($user['name']); ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['phone']; ?></td>
                        <td><?php echo $user['kyc_status']; ?></td>
                        <td><?php echo $user['status']; ?></td>
                        <td>
                            <form method="post" class="grid grid-cols-1 gap-2">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="text" name="name" value="<?php echo sanitize($user['name']); ?>" class="border rounded px-2 py-1" required>
                                <input type="email" name="email" value="<?php echo $user['email']; ?>" class="border rounded px-2 py-1" required>
                                <input type="text" name="phone" value="<?php echo $user['phone']; ?>" class="border rounded px-2 py-1" required>
                                <button class="btn-primary text-white px-3 py-1 rounded" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require_once __DIR__ . '/footer.php';
?>
