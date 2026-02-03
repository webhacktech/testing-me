<?php
$page_title = 'NaijaCryptoX | Secure Nigerian Crypto Exchange';
$page_description = 'Buy, sell, swap, and withdraw crypto with NGN wallets, admin-approved trades, and KYC-first compliance.';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
$rates = get_enabled_rates();
?>
<section class="brand-gradient text-white rounded-2xl p-10 card-shadow">
    <div class="grid md:grid-cols-2 gap-8 items-center">
        <div data-animate>
            <h1 class="text-4xl font-bold mb-4">Nigeria&apos;s trusted crypto exchange built for compliance and speed</h1>
            <p class="text-lg text-white/90 mb-6">Trade BTC, ETH, USDT, and BNB with transparent admin-set rates, secure KYC verification, and NGN withdrawals to any Nigerian bank.</p>
            <div class="flex flex-wrap gap-4">
                <a class="btn-primary text-white px-6 py-3 rounded-lg" href="<?php echo BASE_URL; ?>/public/register.php">Get started</a>
                <a class="btn-outline px-6 py-3 rounded-lg" href="<?php echo BASE_URL; ?>/public/login.php">Login</a>
            </div>
        </div>
        <div class="bg-white/10 rounded-xl p-6" data-animate>
            <h2 class="text-xl font-semibold mb-4">Live admin-set rates</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <?php foreach ($rates as $coin => $rate): ?>
                    <div class="bg-white/20 rounded-lg p-3">
                        <p class="font-semibold"><?php echo $coin; ?></p>
                        <p>Buy: ₦<?php echo number_format($rate['buy_rate'], 2); ?></p>
                        <p>Sell: ₦<?php echo number_format($rate['sell_rate'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-white/80 mt-4">Rates are updated by admins based on market movement.</p>
        </div>
    </div>
</section>

<section class="grid md:grid-cols-3 gap-6 mt-10">
    <div class="bg-white p-6 rounded-xl card-shadow" data-animate>
        <h2 class="text-lg font-semibold mb-2">Compliance-first KYC</h2>
        <p class="text-sm text-gray-600">Submit NIN, BVN, and selfie verification for trusted trading and withdrawal access.</p>
    </div>
    <div class="bg-white p-6 rounded-xl card-shadow" data-animate>
        <h2 class="text-lg font-semibold mb-2">NGN &amp; crypto wallets</h2>
        <p class="text-sm text-gray-600">Manage balances across NGN, BTC, ETH, USDT, and BNB with instant internal ledger updates.</p>
    </div>
    <div class="bg-white p-6 rounded-xl card-shadow" data-animate>
        <h2 class="text-lg font-semibold mb-2">Admin-approved safety</h2>
        <p class="text-sm text-gray-600">Every buy, sell, swap, and withdrawal is verified by an admin to protect customers.</p>
    </div>
</section>

<section class="mt-12 bg-white p-8 rounded-2xl card-shadow">
    <div class="grid md:grid-cols-2 gap-8 items-center">
        <div data-animate>
            <h2 class="text-2xl font-bold mb-4">Everything you need to trade in Nigeria</h2>
            <p class="text-gray-600 mb-4">NaijaCryptoX gives you a compliant, mobile-ready trading experience with transparent rates, clear transaction history, and human-led approvals for each trade.</p>
            <ul class="space-y-2 text-sm text-gray-600">
                <li>✔ Bank withdrawals with approval workflows</li>
                <li>✔ Swap crypto instantly with admin-set conversion rates</li>
                <li>✔ Automated notifications and email confirmations</li>
            </ul>
        </div>
        <div class="bg-gray-50 p-6 rounded-xl" data-animate>
            <h3 class="font-semibold mb-3">Ready to start?</h3>
            <p class="text-sm text-gray-600 mb-4">Join Nigerian traders using a platform built for trust and clarity.</p>
            <a class="btn-primary text-white px-5 py-3 rounded-lg" href="<?php echo BASE_URL; ?>/public/register.php">Create free account</a>
        </div>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
