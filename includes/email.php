<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function send_email($to, $subject, $html_body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port = MAIL_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    return mail($to, $subject, $html_body, $headers);
}

function email_wrapper($title, $content_html) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial, sans-serif;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:24px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#106b28;color:#ffffff;padding:24px;font-size:20px;font-weight:bold;">' . APP_NAME . '</td></tr>'
        . '<tr><td style="padding:24px;color:#1f2937;">'
        . '<h2 style="margin-top:0;color:#106b28;">' . $title . '</h2>'
        . $content_html
        . '<p style="margin-top:24px;font-size:12px;color:#6b7280;">Need help? Contact us at ' . SUPPORT_EMAIL . '</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';
}

function verification_email($name, $token) {
    $link = BASE_URL . '/public/verify.php?token=' . urlencode($token);
    $content = '<p>Hello ' . htmlspecialchars($name) . ',</p>'
        . '<p>Welcome to ' . APP_NAME . '. Please verify your email to activate your account.</p>'
        . '<p><a href="' . $link . '" style="background:#106b28;color:#fff;padding:12px 18px;border-radius:6px;text-decoration:none;">Verify Email</a></p>'
        . '<p>If you did not create an account, ignore this email.</p>';
    return email_wrapper('Verify your email', $content);
}

function kyc_email($name, $status) {
    $content = '<p>Hello ' . htmlspecialchars($name) . ',</p>'
        . '<p>Your KYC submission has been <strong>' . htmlspecialchars($status) . '</strong>.</p>'
        . '<p>Log in to your dashboard for next steps.</p>';
    return email_wrapper('KYC Update', $content);
}

function transaction_email($name, $type, $status, $amount, $coin) {
    $content = '<p>Hello ' . htmlspecialchars($name) . ',</p>'
        . '<p>Your ' . htmlspecialchars($type) . ' transaction is <strong>' . htmlspecialchars($status) . '</strong>.</p>'
        . '<p>Amount: ' . htmlspecialchars($amount) . ' ' . htmlspecialchars($coin) . '</p>'
        . '<p>Log in to view the full details.</p>';
    return email_wrapper('Transaction Update', $content);
}

function admin_broadcast_email($title, $message) {
    $content = '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
    return email_wrapper($title, $content);
}
