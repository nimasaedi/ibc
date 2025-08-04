<?php
defined('ABSPATH') or die('No script kiddies please!');

// شورتکد نمایش تاریخچه تراکنش‌ها

function ibetcoin_transactions_history_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to view transactions history', 'ibetcoin') . '</div>';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $user_id = get_current_user_id();

    $deposit_transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND type = 'deposit' ORDER BY id DESC LIMIT 20",
        $user_id
    ));

    $withdraw_transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND type = 'withdraw' ORDER BY id DESC LIMIT 20",
        $user_id
    ));

    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/transactions-history.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_transactions_history', 'ibetcoin_transactions_history_shortcode');



// شورتکد نمایش تاریخچه پیش‌بینی‌ها
function ibetcoin_predictions_history_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to view predictions history', 'ibetcoin') . '</div>';
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_predictions';
    $user_id = get_current_user_id();
    
    $predictions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
        WHERE user_id = %d 
        ORDER BY created_at DESC 
        LIMIT 20",
        $user_id
    ));
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/predictions-history.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_predictions_history', 'ibetcoin_predictions_history_shortcode');

// شورتکد نمایش آخرین بردها
function ibetcoin_last_wins_shortcode($atts) {
    global $wpdb;
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    $users_table = $wpdb->prefix . 'users';
    
    $atts = shortcode_atts(array(
        'limit' => 5
    ), $atts);
    
    $wins = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.user_login 
        FROM $predictions_table p
        LEFT JOIN $users_table u ON p.user_id = u.ID
        WHERE p.status = 'win'
        ORDER BY p.updated_at DESC
        LIMIT %d",
        $atts['limit']
    ));
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/last-wins.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_last_wins', 'ibetcoin_last_wins_shortcode');

// شورتکد نمایش قیمت لحظه‌ای
function ibetcoin_price_ticker_shortcode($atts) {
    $coin = isset($atts['coin']) ? sanitize_text_field($atts['coin']) : 'bitcoin';
    
    ob_start();
    ?>
    <div class="ibetcoin-price-ticker" data-coin="<?php echo esc_attr($coin); ?>">
        <span class="coin-name"><?php echo strtoupper($coin); ?></span>:
        <span class="coin-price">--</span> USD
        <span class="coin-change"></span>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ibetcoin_price_ticker', 'ibetcoin_price_ticker_shortcode');


// شورت‌کد نمایش موجودی کاربر
function ibetcoin_user_balance_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>لطفاً ابتدا وارد شوید تا موجودی شما نمایش داده شود.</p>';
    }

    $user_id = get_current_user_id();
    $balance = ibetcoin_get_user_balance($user_id);

    return '<p>' . number_format($balance, 2) . ' USDT</p>';
}
add_shortcode('ibetcoin_balance', 'ibetcoin_user_balance_shortcode');







// شورت کد نمایش واریز و برداشت ها
// تابع نمایش تاریخچه تراکنش‌ها با تب (واریز و برداشت)
// Transaction history shortcode with modern tabs (Deposits & Withdrawals)
function ibetcoin_transaction_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in first.</p>';
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';

    $deposit_transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND type = 'deposit' ORDER BY created_at DESC",
        $user_id
    ));
    $withdraw_transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND type = 'withdraw' ORDER BY created_at DESC",
        $user_id
    ));

    ob_start();
    ?>

    <style>
    /* Container */
    .ibetcoin-tabs {
        max-width: 100%;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgb(0 0 0 / 0.1);
        overflow: hidden;
    }

    /* Tab Buttons */
    .ibetcoin-tab-buttons {
        display: flex;
        background: #f9fafb;
        border-bottom: 2px solid #e2e8f0;
    }
    .ibetcoin-tab-buttons button {
        flex: 1;
        padding: 14px 0;
        border: none;
        background: none;
        font-weight: 600;
        font-size: 1.1rem;
        color: #475569;
        cursor: pointer;
        transition: color 0.3s ease, background-color 0.3s ease;
        position: relative;
    }
    .ibetcoin-tab-buttons button:hover {
        color: #1d4ed8;
    }
    .ibetcoin-tab-buttons button.active {
        color: #1d4ed8;
        font-weight: 700;
    }
    .ibetcoin-tab-buttons button.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 25%;
        width: 50%;
        height: 3px;
        background-color: #1d4ed8;
        border-radius: 2px 2px 0 0;
    }

    /* Tab Content */
    .ibetcoin-tab-content {
        padding: 20px 24px;
        animation: fadeIn 0.35s ease forwards;
        display: none;
    }
    .ibetcoin-tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }

    /* Table Styling */
    table.ibetcoin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        color: #334155;
    }
    table.ibetcoin-table th, table.ibetcoin-table td {
        border: 1px solid #e2e8f0;
        padding: 10px 14px;
        text-align: center;
        vertical-align: middle;
    }
    table.ibetcoin-table th {
        background-color: #f1f5f9;
        font-weight: 600;
    }

    /* Status Badges */
    .status-badge {
        padding: 4px 12px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #fff;
        text-transform: capitalize;
        display: inline-block;
        min-width: 75px;
    }
    .status-badge.pending {
        background-color: #f59e0b; /* Amber */
    }
    .status-badge.completed {
        background-color: #16a34a; /* Green */
    }
    .status-badge.rejected {
        background-color: #dc2626; /* Red */
    }
    </style>

    <div class="ibetcoin-tabs" role="tablist" aria-label="Transaction History Tabs">
        <div class="ibetcoin-tab-buttons">
            <button class="active" role="tab" aria-selected="true" aria-controls="deposit-tab" id="deposit-tab-btn">Deposit History</button>
            <button role="tab" aria-selected="false" aria-controls="withdraw-tab" id="withdraw-tab-btn">Withdraw History</button>
        </div>

        <div id="deposit-tab" class="ibetcoin-tab-content active" role="tabpanel" aria-labelledby="deposit-tab-btn" tabindex="0">
            <?php if ($deposit_transactions): ?>
                <table class="ibetcoin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tracking Code</th>
                            <th>Amount (USDT)</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($deposit_transactions as $tx): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo esc_html($tx->tracking_code ?: '-'); ?></td>
                                <td><?php echo number_format($tx->amount, 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($tx->status); ?>">
                                        <?php echo ucfirst($tx->status); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($tx->notes) ? esc_html($tx->notes) : '-'; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No deposit transactions found.</p>
            <?php endif; ?>
        </div>

        <div id="withdraw-tab" class="ibetcoin-tab-content" role="tabpanel" aria-labelledby="withdraw-tab-btn" tabindex="0">
            <?php if ($withdraw_transactions): ?>
                <table class="ibetcoin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tracking Code</th>
                            <th>Amount (USDT)</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($withdraw_transactions as $tx): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo esc_html($tx->tracking_code ?: '-'); ?></td>
                                <td><?php echo number_format($tx->amount, 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($tx->status); ?>">
                                        <?php echo ucfirst($tx->status); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($tx->notes) ? esc_html($tx->notes) : '-'; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No withdraw transactions found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Accessible tab switching
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.ibetcoin-tab-buttons button');
            const tabContents = document.querySelectorAll('.ibetcoin-tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Deactivate all tabs and panels
                    tabs.forEach(t => {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                        document.getElementById(t.getAttribute('aria-controls')).classList.remove('active');
                    });

                    // Activate current tab and panel
                    tab.classList.add('active');
                    tab.setAttribute('aria-selected', 'true');
                    document.getElementById(tab.getAttribute('aria-controls')).classList.add('active');
                });

                // Keyboard navigation support
                tab.addEventListener('keydown', e => {
                    let index = Array.from(tabs).indexOf(document.activeElement);
                    if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        tabs[(index + 1) % tabs.length].focus();
                    } else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        tabs[(index - 1 + tabs.length) % tabs.length].focus();
                    }
                });
            });
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('ibetcoin_transaction_history', 'ibetcoin_transaction_history_shortcode');





// شورتکد داشبورد کاربر
function ibetcoin_wallet_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to access your wallet', 'ibetcoin') . '</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/wallet.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_wallet', 'ibetcoin_wallet_shortcode');

// شورتکد صفحه واریز
function ibetcoin_deposit_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to deposit funds', 'ibetcoin') . '</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/deposit.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_deposit', 'ibetcoin_deposit_shortcode');

// شورتکد صفحه برداشت
function ibetcoin_withdraw_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to withdraw funds', 'ibetcoin') . '</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/withdraw.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_withdraw', 'ibetcoin_withdraw_shortcode');

// شورتکد صفحه پروفایل
function ibetcoin_profile_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to view your profile', 'ibetcoin') . '</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/profile.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_profile', 'ibetcoin_profile_shortcode');

// تابع AJAX برای دریافت موجودی کاربر
add_action('wp_ajax_ibetcoin_get_balance', 'ibetcoin_ajax_get_balance');
function ibetcoin_ajax_get_balance() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Authentication required', 'ibetcoin'));
    }
    
    $balance = ibetcoin_get_user_balance(get_current_user_id());
    
    wp_send_json_success(array(
        'balance' => number_format($balance, 2),
        'currency' => 'USDT'
    ));
}
