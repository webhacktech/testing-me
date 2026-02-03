<?php
$page_title = 'Contact Us | NaijaCryptoX';
$page_description = 'Reach NaijaCryptoX support for assistance, verification, or transaction questions.';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $honeypot = $_POST['website'] ?? '';

    if ($honeypot !== '') {
        $errors[] = 'Bot detection triggered.';
    }

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO contact_messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('sss', $name, $email, $message);
        $stmt->execute();

        $email_body = admin_broadcast_email('New Contact Message', "From: $name ($email)\n\n$message");
        send_email(SUPPORT_EMAIL, 'New contact message from ' . $name, $email_body);
        $messages[] = 'Thanks for reaching out. Our team will respond shortly.';
    }
}
?>
<section class="bg-white p-8 rounded-2xl card-shadow">
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h1 class="text-3xl font-bold mb-4">Contact Us</h1>
            <p class="text-gray-600 mb-6">Need assistance with verification, trades, or withdrawals? Send us a message and our team will help.</p>
            <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-600">
                <p><strong>Email:</strong> <?php echo SUPPORT_EMAIL; ?></p>
                <p><strong>Address:</strong> 12 Marina Road, Lagos Island, Nigeria</p>
                <p><strong>Hours:</strong> Monday - Saturday, 9am - 6pm</p>
            </div>
        </div>
        <div>
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
                    <label class="block text-sm mb-1">Name</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Message</label>
                    <textarea name="message" rows="4" class="w-full border rounded px-3 py-2" required></textarea>
                </div>
                <div class="hidden">
                    <label>Website</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <button class="btn-primary text-white px-5 py-3 rounded-lg" type="submit">Send message</button>
            </form>
        </div>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
