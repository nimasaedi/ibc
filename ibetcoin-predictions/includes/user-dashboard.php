<?php
defined('ABSPATH') or die('No script kiddies please!');

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

// تابع AJAX برای ثبت درخواست برداشت
add_action('wp_ajax_ibetcoin_request_withdrawal', 'ibetcoin_ajax_request_withdrawal');
function ibetcoin_ajax_request_withdrawal() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Authentication required', 'ibetcoin'));
    }
    
    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
    
    $settings = get_option('ibetcoin_settings');
    $min_withdrawal = $settings['min_withdrawal'];
    $balance = ibetcoin_get_user_balance($user_id);
    
    // اعتبارسنجی
    if ($amount < $min_withdrawal) {
        wp_send_json_error(sprintf(__('Minimum withdrawal amount is %s USDT', 'ibetcoin'), $min_withdrawal));
    }
    
    if ($amount > $balance) {
        wp_send_json_error(__('Insufficient balance', 'ibetcoin'));
    }
    
    if (empty($wallet_address)) {
        wp_send_json_error(__('Please enter your wallet address', 'ibetcoin'));
    }
    
    // ثبت تراکنش
    $tx_id = ibetcoin_add_transaction(array(
        'user_id' => $user_id,
        'type' => 'withdraw',
        'amount' => $amount,
        'wallet_address' => $wallet_address,
        'status' => 'pending'
    ));
    
    if ($tx_id) {
        // ارسال ایمیل به مدیر
        $admin_email = get_option('admin_email');
        $subject = __('New Withdrawal Request', 'ibetcoin');
        $message = sprintf(__('User ID %d has requested a withdrawal of %s USDT. Please review it in admin panel.', 'ibetcoin'), $user_id, $amount);
        ibetcoin_send_email($admin_email, $subject, $message);
        
        wp_send_json_success(__('Withdrawal request submitted successfully', 'ibetcoin'));
    } else {
        wp_send_json_error(__('Failed to submit withdrawal request', 'ibetcoin'));
    }
}