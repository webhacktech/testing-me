<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

require_admin();

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_rate') {
        $coin = sanitize($_POST['coin'] ?? '');
        $market_rate = (float)($_POST['market_rate'] ?? 0);
        $buy_rate = (float)($_POST['buy_rate'] ?? 0);
        $sell_rate = (float)($_POST['sell_rate'] ?? 0);
        if (!in_array($coin, COINS, true)) {
            $errors[] = 'Invalid coin.';
        } else {
            $stmt = $mysqli->prepare('UPDATE rates SET market_rate = ?, buy_rate = ?, sell_rate = ?, updated_at = NOW() WHERE coin = ?');
            $stmt->bind_param('ddds', $market_rate, $buy_rate, $sell_rate, $coin);
            $stmt->execute();
            $messages[] = 'Rates updated for ' . $coin . '.';
        }
    }

    if ($action === 'toggle_coin') {
        $coin = sanitize($_POST['coin'] ?? '');
        $enabled = (int)($_POST['enabled'] ?? 0);
        if (!in_array($coin, COINS, true)) {
            $errors[] = 'Invalid coin.';
        } else {
            $stmt = $mysqli->prepare('UPDATE rates SET enabled = ?, updated_at = NOW() WHERE coin = ?');
            $stmt->bind_param('is', $enabled, $coin);
            $stmt->execute();
            $messages[] = 'Coin status updated for ' . $coin . '.';
        }
    }

    if ($action === 'set_platform_rate') {
        $usd_to_ngn = (float)($_POST['usd_to_ngn'] ?? 0);
        if ($usd_to_ngn <= 0) {
            $errors[] = 'Enter a valid USD/NGN rate.';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO platform_rates (usd_to_ngn, created_at) VALUES (?, NOW())');
            $stmt->bind_param('d', $usd_to_ngn);
            $stmt->execute();
            $messages[] = 'Platform USD/NGN rate updated.';
        }
    }

    if ($action === 'kyc_decision') {
        $kyc_id = (int)($_POST['kyc_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Pending');
        if (!in_array($status, ['Approved', 'Rejected'], true)) {
            $errors[] = 'Invalid KYC status.';
        } else {
            $stmt = $mysqli->prepare('UPDATE kyc SET status = ?, approved_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $status, $kyc_id);
            $stmt->execute();
            $user_stmt = $mysqli->prepare('SELECT user_id FROM kyc WHERE id = ?');
            $user_stmt->bind_param('i', $kyc_id);
            $user_stmt->execute();
            $user_row = $user_stmt->get_result()->fetch_assoc();
            if ($user_row) {
                $update_user = $mysqli->prepare('UPDATE users SET kyc_status = ? WHERE id = ?');
                $update_user->bind_param('si', $status, $user_row['user_id']);
                $update_user->execute();
                add_notification($user_row['user_id'], 'kyc', 'Your KYC has been ' . strtolower($status) . '.');
                $user_info = get_user($user_row['user_id']);
                $email_body = kyc_email($user_info['name'], $status);
                $sent = send_email($user_info['email'], APP_NAME . ' KYC Update', $email_body);
                log_email($user_row['user_id'], APP_NAME . ' KYC Update', $email_body, $sent ? 'sent' : 'failed');
            }
            $messages[] = 'KYC updated.';
        }
    }

    if ($action === 'transaction_decision') {
        $tx_id = (int)($_POST['tx_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'Pending');
        if (!in_array($status, ['Approved', 'Rejected'], true)) {
            $errors[] = 'Invalid transaction status.';
        } else {
            $stmt = $mysqli->prepare('SELECT * FROM transactions WHERE id = ?');
            $stmt->bind_param('i', $tx_id);
            $stmt->execute();
            $tx = $stmt->get_result()->fetch_assoc();
            if ($tx && $tx['status'] === 'Pending') {
                if ($status === 'Approved') {
                    if ($tx['type'] === 'buy') {
                        update_wallet_balance($tx['user_id'], $tx['coin'], $tx['amount']);
                    } elseif ($tx['type'] === 'sell') {
                        $ngn_credit = $tx['amount'] * $tx['rate_used'];
                        update_wallet_balance($tx['user_id'], 'NGN', $ngn_credit);
                    } elseif ($tx['type'] === 'swap') {
                        $from_coin = null;
                        $from_amount = null;
                        if (!empty($tx['reference'])) {
                            $parts = explode(';', $tx['reference']);
                            foreach ($parts as $part) {
                                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
                                if ($key === 'from') {
                                    $from_coin = $value;
                                }
                                if ($key === 'amount') {
                                    $from_amount = (float)$value;
                                }
                            }
                        }
                        if ($from_coin && $from_amount) {
                            update_wallet_balance($tx['user_id'], $from_coin, -$from_amount);
                        }
                        update_wallet_balance($tx['user_id'], $tx['coin'], $tx['amount']);
                    } elseif ($tx['type'] === 'withdraw') {
                        update_wallet_balance($tx['user_id'], 'NGN', -$tx['amount']);
                    }
                    add_notification($tx['user_id'], 'transaction', 'Your ' . $tx['type'] . ' transaction has been approved.');
                } else {
                    add_notification($tx['user_id'], 'transaction', 'Your ' . $tx['type'] . ' transaction was rejected.');
                }
                $user_info = get_user($tx['user_id']);
                $email_body = transaction_email($user_info['name'], $tx['type'], $status, $tx['amount'], $tx['coin']);
                $sent = send_email($user_info['email'], APP_NAME . ' Transaction Update', $email_body);
                log_email($tx['user_id'], APP_NAME . ' Transaction Update', $email_body, $sent ? 'sent' : 'failed');
                $update = $mysqli->prepare('UPDATE transactions SET status = ? WHERE id = ?');
                $update->bind_param('si', $status, $tx_id);
                $update->execute();
                $messages[] = 'Transaction updated.';
            }
        }
    }

    if ($action === 'toggle_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $user_id);
        $stmt->execute();
        $messages[] = 'User status updated.';
    }
}

$rates = get_rates();
$platform_rate = get_platform_rate();
$kyc_requests = $mysqli->query('SELECT kyc.*, users.name, users.email FROM kyc JOIN users ON users.id = kyc.user_id ORDER BY kyc.submitted_at DESC');
$transactions = $mysqli->query('SELECT transactions.*, users.name FROM transactions JOIN users ON users.id = transactions.user_id ORDER BY transactions.created_at DESC LIMIT 50');
$users = $mysqli->query('SELECT id, name, email, phone, kyc_status, status, created_at FROM users ORDER BY created_at DESC');

$profit_query = $mysqli->query('SELECT SUM(CASE WHEN type = "buy" THEN (rate_used - (SELECT market_rate FROM rates WHERE coin = transactions.coin)) * amount WHEN type = "sell" THEN ((SELECT market_rate FROM rates WHERE coin = transactions.coin) - rate_used) * amount ELSE 0 END) AS profit FROM transactions WHERE status = "Approved"');
$profit_row = $profit_query->fetch_assoc();
$profit = $profit_row ? (float)$profit_row['profit'] : 0.0;
?>

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

<div class="grid md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Platform USD/NGN</h3>
        <p class="text-2xl font-bold mb-4"><?php echo number_format($platform_rate, 2); ?></p>
        <form method="post" class="space-y-2">
            <input type="hidden" name="action" value="set_platform_rate">
            <input type="number" step="0.01" name="usd_to_ngn" class="w-full border rounded px-3 py-2" placeholder="Set new rate" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Update Rate</button>
        </form>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Profit Summary</h3>
        <p class="text-2xl font-bold">₦<?php echo number_format($profit * $platform_rate, 2); ?></p>
        <p class="text-sm text-gray-500">Estimated profit from approved trades.</p>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Coins Enabled</h3>
        <p class="text-sm text-gray-600">Whitelist: BTC, ETH, USDT, BNB</p>
        <p class="text-sm text-gray-600">Disable by setting rates to 0 if needed.</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Admin Tools</h3>
        <div class="flex flex-wrap gap-3">
            <a class="btn-primary text-white px-4 py-2 rounded-lg" href="<?php echo BASE_URL; ?>/admin/emails.php">Email Center</a>
            <a class="btn-outline px-4 py-2 rounded-lg" href="<?php echo BASE_URL; ?>/admin/users.php">Edit Users</a>
        </div>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Compliance Checklist</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            <li>✔ Review pending KYC requests daily</li>
            <li>✔ Approve withdrawals only after bank verification</li>
            <li>✔ Update rates during market volatility</li>
        </ul>
    </div>
</div>

<div class="bg-white p-6 rounded shadow mt-8">
    <h3 class="font-semibold mb-4">Manage Rates</h3>
    <div class="grid md:grid-cols-2 gap-4">
        <?php foreach ($rates as $coin => $rate): ?>
            <form method="post" class="border rounded p-4 space-y-2">
                <input type="hidden" name="action" value="update_rate">
                <input type="hidden" name="coin" value="<?php echo $coin; ?>">
                <h4 class="font-semibold"><?php echo $coin; ?></h4>
                <p class="text-sm text-gray-500">Status: <?php echo $rate['enabled'] ? 'Enabled' : 'Disabled'; ?></p>
                <label class="text-sm">Market rate (USD)</label>
                <input type="number" step="0.01" name="market_rate" value="<?php echo $rate['market_rate']; ?>" class="w-full border rounded px-3 py-2" required>
                <label class="text-sm">Buy rate (NGN)</label>
                <input type="number" step="0.01" name="buy_rate" value="<?php echo $rate['buy_rate']; ?>" class="w-full border rounded px-3 py-2" required>
                <label class="text-sm">Sell rate (NGN)</label>
                <input type="number" step="0.01" name="sell_rate" value="<?php echo $rate['sell_rate']; ?>" class="w-full border rounded px-3 py-2" required>
                <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Save</button>
            </form>
            <form method="post" class="mt-2">
                <input type="hidden" name="action" value="toggle_coin">
                <input type="hidden" name="coin" value="<?php echo $coin; ?>">
                <?php if ($rate['enabled']): ?>
                    <button class="bg-red-600 text-white px-4 py-2 rounded" name="enabled" value="0" type="submit">Disable</button>
                <?php else: ?>
                    <button class="bg-green-600 text-white px-4 py-2 rounded" name="enabled" value="1" type="submit">Enable</button>
                <?php endif; ?>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div class="bg-white p-6 rounded shadow mt-8">
    <h3 class="font-semibold mb-4">KYC Requests</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">User</th>
                    <th>NIN</th>
                    <th>BVN</th>
                    <th>Status</th>
                    <th>Selfie</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($kyc = $kyc_requests->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo sanitize($kyc['name']); ?> (<?php echo $kyc['email']; ?>)</td>
                        <td><?php echo $kyc['nin']; ?></td>
                        <td><?php echo $kyc['bvn']; ?></td>
                        <td><?php echo $kyc['status']; ?></td>
                        <td>
                            <a class="text-blue-600" href="<?php echo BASE_URL; ?>/uploads/selfies/<?php echo $kyc['selfie_path']; ?>" target="_blank">View</a>
                        </td>
                        <td>
                            <form method="post" class="flex gap-2">
                                <input type="hidden" name="action" value="kyc_decision">
                                <input type="hidden" name="kyc_id" value="<?php echo $kyc['id']; ?>">
                                <button name="status" value="Approved" class="bg-green-600 text-white px-3 py-1 rounded" type="submit">Approve</button>
                                <button name="status" value="Rejected" class="bg-red-600 text-white px-3 py-1 rounded" type="submit">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white p-6 rounded shadow mt-8">
    <h3 class="font-semibold mb-4">Transactions</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">User</th>
                    <th>Type</th>
                    <th>Coin</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = $transactions->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo sanitize($tx['name']); ?></td>
                        <td class="capitalize"><?php echo $tx['type']; ?></td>
                        <td><?php echo $tx['coin']; ?></td>
                        <td><?php echo number_format($tx['amount'], 8); ?></td>
                        <td><?php echo $tx['status']; ?></td>
                        <td>
                            <?php if ($tx['status'] === 'Pending'): ?>
                                <form method="post" class="flex gap-2">
                                    <input type="hidden" name="action" value="transaction_decision">
                                    <input type="hidden" name="tx_id" value="<?php echo $tx['id']; ?>">
                                    <button name="status" value="Approved" class="bg-green-600 text-white px-3 py-1 rounded" type="submit">Approve</button>
                                    <button name="status" value="Rejected" class="bg-red-600 text-white px-3 py-1 rounded" type="submit">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-500">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white p-6 rounded shadow mt-8">
    <h3 class="font-semibold mb-4">User Management</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>KYC</th>
                    <th>Status</th>
                    <th>Action</th>
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
                            <form method="post">
                                <input type="hidden" name="action" value="toggle_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <?php if ($user['status'] === 'active'): ?>
                                    <button name="status" value="banned" class="bg-red-600 text-white px-3 py-1 rounded" type="submit">Ban</button>
                                <?php else: ?>
                                    <button name="status" value="active" class="bg-green-600 text-white px-3 py-1 rounded" type="submit">Unban</button>
                                <?php endif; ?>
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
