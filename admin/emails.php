<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

require_admin();

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $message = $_POST['message'] ?? '';
    $send_to = sanitize($_POST['send_to'] ?? 'all');

    if ($subject === '' || $message === '') {
        $errors[] = 'Subject and message are required.';
    } else {
        if ($send_to === 'all') {
            $users = $mysqli->query('SELECT id, name, email FROM users WHERE status = "active"');
            while ($user = $users->fetch_assoc()) {
                $body = admin_broadcast_email($subject, $message);
                $sent = send_email($user['email'], $subject, $body);
                log_email($user['id'], $subject, $body, $sent ? 'sent' : 'failed');
            }
            $messages[] = 'Broadcast sent to all active users.';
        } else {
            $user_id = (int)$send_to;
            $user = get_user($user_id);
            if ($user) {
                $body = admin_broadcast_email($subject, $message);
                $sent = send_email($user['email'], $subject, $body);
                log_email($user['id'], $subject, $body, $sent ? 'sent' : 'failed');
                $messages[] = 'Email sent to ' . sanitize($user['name']) . '.';
            } else {
                $errors[] = 'User not found.';
            }
        }
    }
}

$user_list = $mysqli->query('SELECT id, name, email FROM users ORDER BY name');
?>
<div class="bg-white p-8 rounded-2xl card-shadow">
    <h1 class="text-2xl font-semibold mb-4">Email Broadcasts</h1>
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
            <label class="block text-sm mb-1">Send to</label>
            <select name="send_to" class="w-full border rounded px-3 py-2">
                <option value="all">All active users</option>
                <?php while ($user = $user_list->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo sanitize($user['name']); ?> (<?php echo $user['email']; ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Subject</label>
            <input type="text" name="subject" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Message</label>
            <textarea name="message" rows="5" class="w-full border rounded px-3 py-2" required></textarea>
        </div>
        <button class="btn-primary text-white px-5 py-3 rounded-lg" type="submit">Send Email</button>
    </form>
</div>
<?php
require_once __DIR__ . '/footer.php';
?>
