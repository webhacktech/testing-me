<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

require_login();

$user = get_user($_SESSION['user_id']);
ensure_wallets($user['id']);
$kyc_status = kyc_status($user['id']);
$rates = get_enabled_rates();
$platform_rate = get_platform_rate();
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!user_can_trade($user['id']) && in_array($action, ['buy', 'sell', 'swap', 'withdraw'], true)) {
        $errors[] = 'Complete KYC approval before trading or withdrawing.';
    } else {
        if ($action === 'buy') {
            $coin = sanitize($_POST['coin'] ?? '');
            $ngn_amount = (float)($_POST['ngn_amount'] ?? 0);
            $wallet_address = sanitize($_POST['wallet_address'] ?? '');
            if (!isset($rates[$coin])) {
                $errors[] = 'Invalid coin selected.';
            } elseif ($ngn_amount <= 0 || $wallet_address === '') {
                $errors[] = 'Enter valid NGN amount and wallet address.';
            } else {
                $rate_used = (float)$rates[$coin]['buy_rate'];
                $crypto_amount = $ngn_amount / $rate_used;
                $reference = 'wallet=' . $wallet_address;
                log_transaction($user['id'], 'buy', $coin, $crypto_amount, $rate_used, 'Pending', $reference);
                add_notification($user['id'], 'buy', 'Buy request submitted for ' . $crypto_amount . ' ' . $coin . '.');
                $email_body = transaction_email($user['name'], 'buy', 'Pending', $crypto_amount, $coin);
                $sent = send_email($user['email'], APP_NAME . ' Buy Request', $email_body);
                log_email($user['id'], APP_NAME . ' Buy Request', $email_body, $sent ? 'sent' : 'failed');
                $messages[] = 'Buy order submitted and awaiting admin approval.';
            }
        }

        if ($action === 'sell') {
            $coin = sanitize($_POST['coin'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $tx_hash = sanitize($_POST['tx_hash'] ?? '');
            if (!isset($rates[$coin])) {
                $errors[] = 'Invalid coin selected.';
            } elseif ($amount <= 0 || $tx_hash === '') {
                $errors[] = 'Enter valid amount and TX hash.';
            } else {
                $rate_used = (float)$rates[$coin]['sell_rate'];
                $ngn_credit = $amount * $rate_used;
                $reference = 'tx=' . $tx_hash;
                log_transaction($user['id'], 'sell', $coin, $amount, $rate_used, 'Pending', $reference);
                add_notification($user['id'], 'sell', 'Sell request submitted for ' . $amount . ' ' . $coin . '.');
                $email_body = transaction_email($user['name'], 'sell', 'Pending', $amount, $coin);
                $sent = send_email($user['email'], APP_NAME . ' Sell Request', $email_body);
                log_email($user['id'], APP_NAME . ' Sell Request', $email_body, $sent ? 'sent' : 'failed');
                $messages[] = 'Sell order submitted. NGN will be credited after approval.';
            }
        }

        if ($action === 'swap') {
            $from_coin = sanitize($_POST['from_coin'] ?? '');
            $to_coin = sanitize($_POST['to_coin'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            if ($from_coin === $to_coin || !isset($rates[$from_coin]) || !isset($rates[$to_coin])) {
                $errors[] = 'Select two different valid coins.';
            } elseif ($amount <= 0) {
                $errors[] = 'Enter a valid amount.';
            } elseif ($amount > get_wallet_balance($user['id'], $from_coin)) {
                $errors[] = 'Insufficient balance for swap.';
            } else {
                $from_rate = (float)$rates[$from_coin]['sell_rate'];
                $to_rate = (float)$rates[$to_coin]['buy_rate'];
                $ngn_value = $amount * $from_rate;
                $to_amount = $ngn_value / $to_rate;
                $reference = 'from=' . $from_coin . ';amount=' . $amount;
                log_transaction($user['id'], 'swap', $to_coin, $to_amount, $to_rate, 'Pending', $reference);
                add_notification($user['id'], 'swap', 'Swap request submitted from ' . $from_coin . ' to ' . $to_coin . '.');
                $email_body = transaction_email($user['name'], 'swap', 'Pending', $to_amount, $to_coin);
                $sent = send_email($user['email'], APP_NAME . ' Swap Request', $email_body);
                log_email($user['id'], APP_NAME . ' Swap Request', $email_body, $sent ? 'sent' : 'failed');
                $messages[] = 'Swap request submitted for approval.';
            }
        }

        if ($action === 'withdraw') {
            $bank_name = sanitize($_POST['bank_name'] ?? '');
            $account_number = sanitize($_POST['account_number'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $ngn_balance = get_wallet_balance($user['id'], 'NGN');
            if ($bank_name === '' || $account_number === '' || $amount <= 0) {
                $errors[] = 'Complete all withdrawal fields.';
            } elseif ($amount > $ngn_balance) {
                $errors[] = 'Insufficient NGN balance.';
            } else {
                $reference = 'bank=' . $bank_name . ';acct=' . $account_number;
                log_transaction($user['id'], 'withdraw', 'NGN', $amount, 1, 'Pending', $reference);
                add_notification($user['id'], 'withdraw', 'Withdrawal request submitted for NGN ' . number_format($amount, 2) . '.');
                $email_body = transaction_email($user['name'], 'withdraw', 'Pending', number_format($amount, 2), 'NGN');
                $sent = send_email($user['email'], APP_NAME . ' Withdrawal Request', $email_body);
                log_email($user['id'], APP_NAME . ' Withdrawal Request', $email_body, $sent ? 'sent' : 'failed');
                $messages[] = 'Withdrawal request submitted for approval.';
            }
        }
    }
}

$available_coins = array_keys($rates);
$wallets = [];
$result = $mysqli->prepare('SELECT currency, balance FROM wallets WHERE user_id = ? ORDER BY currency');
$result->bind_param('i', $user['id']);
$result->execute();
$wallet_result = $result->get_result();
while ($row = $wallet_result->fetch_assoc()) {
    $wallets[$row['currency']] = $row['balance'];
}

$tx_stmt = $mysqli->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$tx_stmt->bind_param('i', $user['id']);
$tx_stmt->execute();
$transactions = $tx_stmt->get_result();
?>
<div class="grid md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Welcome, <?php echo sanitize($user['name']); ?></h2>
        <p class="text-sm text-gray-600">KYC Status: <span class="font-semibold"><?php echo $kyc_status; ?></span></p>
        <p class="text-sm text-gray-600">USD/NGN Rate: <span class="font-semibold"><?php echo number_format($platform_rate, 2); ?></span></p>
        <a href="<?php echo BASE_URL; ?>/public/kyc.php" class="mt-4 inline-block text-blue-600">Update KYC</a>
    </div>
    <div class="md:col-span-2 bg-white p-6 rounded shadow">
        <h3 class="text-lg font-semibold mb-4">Wallet Balances</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php foreach ($wallets as $currency => $balance): ?>
                <div class="border rounded p-3 text-center">
                    <p class="text-sm text-gray-500"><?php echo $currency; ?></p>
                    <p class="font-semibold"><?php echo number_format($balance, 8); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($messages): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mt-6">
        <?php foreach ($messages as $message): ?>
            <p><?php echo $message; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mt-6">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6 mt-8">
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-4">Buy Crypto</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="buy">
            <label class="block text-sm">Select coin</label>
            <select name="coin" class="w-full border rounded px-3 py-2">
                <?php foreach ($available_coins as $coin): ?>
                    <option value="<?php echo $coin; ?>"><?php echo $coin; ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">NGN Amount</label>
            <input type="number" step="0.01" name="ngn_amount" class="w-full border rounded px-3 py-2" required>
            <label class="block text-sm">Your wallet address</label>
            <input type="text" name="wallet_address" class="w-full border rounded px-3 py-2" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Submit Buy</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-4">Sell Crypto</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="sell">
            <label class="block text-sm">Select coin</label>
            <select name="coin" class="w-full border rounded px-3 py-2">
                <?php foreach ($available_coins as $coin): ?>
                    <option value="<?php echo $coin; ?>"><?php echo $coin; ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">Coin amount</label>
            <input type="number" step="0.00000001" name="amount" class="w-full border rounded px-3 py-2" required>
            <label class="block text-sm">TX hash after sending to admin wallet</label>
            <input type="text" name="tx_hash" class="w-full border rounded px-3 py-2" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Submit Sell</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-4">Swap Crypto</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="swap">
            <label class="block text-sm">From</label>
            <select name="from_coin" class="w-full border rounded px-3 py-2">
                <?php foreach ($available_coins as $coin): ?>
                    <option value="<?php echo $coin; ?>"><?php echo $coin; ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">To</label>
            <select name="to_coin" class="w-full border rounded px-3 py-2">
                <?php foreach ($available_coins as $coin): ?>
                    <option value="<?php echo $coin; ?>"><?php echo $coin; ?></option>
                <?php endforeach; ?>
            </select>
            <label class="block text-sm">Amount</label>
            <input type="number" step="0.00000001" name="amount" class="w-full border rounded px-3 py-2" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Submit Swap</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-4">Withdraw NGN</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="withdraw">
            <label class="block text-sm">Bank name</label>
            <input type="text" name="bank_name" class="w-full border rounded px-3 py-2" required>
            <label class="block text-sm">Account number</label>
            <input type="text" name="account_number" class="w-full border rounded px-3 py-2" required>
            <label class="block text-sm">Amount (NGN)</label>
            <input type="number" step="0.01" name="amount" class="w-full border rounded px-3 py-2" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Submit Withdrawal</button>
        </form>
    </div>
</div>

<div class="bg-white p-6 rounded shadow mt-8">
    <h3 class="font-semibold mb-4">Recent Transactions</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Type</th>
                    <th>Coin</th>
                    <th>Amount</th>
                    <th>Rate</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = $transactions->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2 capitalize"><?php echo $tx['type']; ?></td>
                        <td><?php echo $tx['coin']; ?></td>
                        <td><?php echo number_format($tx['amount'], 8); ?></td>
                        <td><?php echo number_format($tx['rate_used'], 2); ?></td>
                        <td><?php echo $tx['status']; ?></td>
                        <td><?php echo $tx['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
