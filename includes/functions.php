<?php
require_once __DIR__ . '/db.php';

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function get_user($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function ensure_wallets($user_id) {
    global $mysqli;
    $currencies = array_merge(['NGN'], COINS);
    foreach ($currencies as $currency) {
        $stmt = $mysqli->prepare('SELECT id FROM wallets WHERE user_id = ? AND currency = ?');
        $stmt->bind_param('is', $user_id, $currency);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if (!$exists) {
            $insert = $mysqli->prepare('INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, 0)');
            $insert->bind_param('is', $user_id, $currency);
            $insert->execute();
        }
    }
}

function get_wallet_balance($user_id, $currency) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT balance FROM wallets WHERE user_id = ? AND currency = ?');
    $stmt->bind_param('is', $user_id, $currency);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (float)$row['balance'] : 0.0;
}

function update_wallet_balance($user_id, $currency, $amount) {
    global $mysqli;
    $stmt = $mysqli->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?');
    $stmt->bind_param('dis', $amount, $user_id, $currency);
    return $stmt->execute();
}

function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/public/login.php');
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

function kyc_status($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT status FROM kyc WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['status'] : 'Pending';
}

function user_can_trade($user_id) {
    if (!KYC_REQUIRED) {
        return true;
    }
    return kyc_status($user_id) === 'Approved';
}

function log_transaction($user_id, $type, $coin, $amount, $rate_used, $status, $reference = null) {
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, type, coin, amount, rate_used, reference, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->bind_param('issddss', $user_id, $type, $coin, $amount, $rate_used, $reference, $status);
    $stmt->execute();
}

function rate_limit_check($ip, $action, $limit = 5, $minutes = 10) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND action = ? AND created_at > (NOW() - INTERVAL ? MINUTE)');
    $stmt->bind_param('ssi', $ip, $action, $minutes);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['attempts'] < $limit;
}

function log_attempt($ip, $action) {
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO login_attempts (ip_address, action, created_at) VALUES (?, ?, NOW())');
    $stmt->bind_param('ss', $ip, $action);
    $stmt->execute();
}

function get_rates() {
    global $mysqli;
    $rates = [];
    $result = $mysqli->query('SELECT * FROM rates');
    while ($row = $result->fetch_assoc()) {
        $rates[$row['coin']] = $row;
    }
    return $rates;
}

function get_enabled_rates() {
    global $mysqli;
    $rates = [];
    $result = $mysqli->query('SELECT * FROM rates WHERE enabled = 1');
    while ($row = $result->fetch_assoc()) {
        $rates[$row['coin']] = $row;
    }
    return $rates;
}

function get_platform_rate() {
    global $mysqli;
    $result = $mysqli->query('SELECT usd_to_ngn FROM platform_rates ORDER BY id DESC LIMIT 1');
    $row = $result->fetch_assoc();
    return $row ? (float)$row['usd_to_ngn'] : 0.0;
}

function add_notification($user_id, $type, $message) {
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO notifications (user_id, type, message, read_status, created_at) VALUES (?, ?, ?, 0, NOW())');
    $stmt->bind_param('iss', $user_id, $type, $message);
    $stmt->execute();
}

function log_email($user_id, $subject, $body, $status) {
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO emails (user_id, subject, body, status, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->bind_param('isss', $user_id, $subject, $body, $status);
    $stmt->execute();
}
