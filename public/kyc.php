<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_user($_SESSION['user_id']);
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nin = sanitize($_POST['nin'] ?? '');
    $bvn = sanitize($_POST['bvn'] ?? '');

    if ($nin === '' || $bvn === '') {
        $errors[] = 'NIN and BVN are required.';
    }

    if (!isset($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Selfie upload failed.';
    }

    if (empty($errors)) {
        $ext = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];
        $size_limit = 2 * 1024 * 1024;
        if (!in_array(strtolower($ext), $allowed, true)) {
            $errors[] = 'Selfie must be a JPG or PNG image.';
        }
        if ($_FILES['selfie']['size'] > $size_limit) {
            $errors[] = 'Selfie must be less than 2MB.';
        }
    }

    if (empty($errors)) {
        $ext = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
        $filename = 'selfie_' . $user['id'] . '_' . time() . '.' . strtolower($ext);
        $target = UPLOAD_DIR . '/' . $filename;
        if (move_uploaded_file($_FILES['selfie']['tmp_name'], $target)) {
            $stmt = $mysqli->prepare('INSERT INTO kyc (user_id, nin, bvn, selfie_path, status, submitted_at) VALUES (?, ?, ?, ?, "Pending", NOW())');
            $stmt->bind_param('isss', $user['id'], $nin, $bvn, $filename);
            $stmt->execute();
            add_notification($user['id'], 'kyc', 'KYC submitted and pending review.');
            $messages[] = 'KYC submitted successfully.';
        } else {
            $errors[] = 'Unable to save selfie.';
        }
    }
}

$latest_status = kyc_status($user['id']);
?>
<div class="bg-white p-8 rounded shadow max-w-xl mx-auto">
    <h2 class="text-2xl font-semibold mb-2">KYC Verification</h2>
    <p class="text-sm text-gray-600 mb-4">Current status: <span class="font-semibold"><?php echo $latest_status; ?></span></p>

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

    <form method="post" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-sm mb-1">NIN</label>
            <input type="text" name="nin" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">BVN</label>
            <input type="text" name="bvn" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Selfie upload</label>
            <input type="file" name="selfie" accept="image/*" class="w-full" required>
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Submit KYC</button>
    </form>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
