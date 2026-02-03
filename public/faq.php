<?php
$page_title = 'FAQ | NaijaCryptoX';
$page_description = 'Frequently asked questions about buying, selling, swapping, KYC, and withdrawals.';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bg-white p-8 rounded-2xl card-shadow">
    <h1 class="text-3xl font-bold mb-6">Frequently Asked Questions</h1>
    <div class="space-y-4">
        <?php
        $faqs = [
            ['How do I buy crypto?', 'Register, complete KYC, fund your NGN wallet, and place a buy request. Admins approve the trade before settlement.'],
            ['How long does KYC take?', 'KYC is usually reviewed within 24 hours. You will receive an email once approved or rejected.'],
            ['Can I swap between coins?', 'Yes. Use the swap form in your dashboard to convert between BTC, ETH, USDT, and BNB.'],
            ['How do withdrawals work?', 'Submit a withdrawal request with your bank details. Admins verify and approve before sending NGN.'],
            ['Why are rates set by admins?', 'Admin-controlled rates ensure stability and allow quick response to local market conditions and liquidity.'],
            ['Is my data secure?', 'We use encrypted connections, hashed passwords, and role-based approvals to safeguard your account.']
        ];
        foreach ($faqs as $faq):
        ?>
            <div class="accordion-item border rounded-xl p-4">
                <button type="button" class="accordion-header flex items-center justify-between w-full text-left">
                    <span class="font-semibold"><?php echo $faq[0]; ?></span>
                    <span class="accordion-icon text-[#106b28] text-xl transition-all">+</span>
                </button>
                <div class="accordion-content text-sm text-gray-600 mt-2">
                    <p><?php echo $faq[1]; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
