<?php
defined('ABSPATH') or die('No script kiddies please!');

// تابع دریافت موجودی کاربر

function ibetcoin_get_user_balance($user_id) {
    global $wpdb;
    
    // روش سریع با استفاده از ستون جدید
    $balance = $wpdb->get_var($wpdb->prepare(
        "SELECT ibetcoin_balance FROM {$wpdb->users} WHERE ID = %d",
        $user_id
    ));
    
    // روش دقیق با محاسبه از تاریخچه تراکنش‌ها (برای اطمینان)
    $calculated_balance = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(
            CASE 
                WHEN type IN ('deposit', 'win', 'admin_add') THEN amount
                WHEN type IN ('withdraw', 'bet', 'admin_subtract') THEN -amount
                ELSE 0
            END
        ) 
        FROM {$wpdb->prefix}ibetcoin_transactions 
        WHERE user_id = %d AND status = 'completed'",
        $user_id
    ));
    
    // اگر اختلاف وجود داشت، ستون balance را به‌روز کنیم
    if (abs($balance - $calculated_balance) > 0.0001) {
        $wpdb->update(
            $wpdb->users,
            array('ibetcoin_balance' => $calculated_balance),
            array('ID' => $user_id)
        );
        return $calculated_balance;
    }
    
    return $balance ? floatval($balance) : 0;
}























// تابع بررسی وضعیت کیف پول کاربر
function ibetcoin_check_user_wallet($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_wallets';
    
    $wallet = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND status = 'verified' LIMIT 1",
        $user_id
    ));
    
    return $wallet;
}

// تابع ثبت تراکنش جدید
function ibetcoin_add_transaction($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    
    $defaults = array(
        'user_id' => 0,
        'type' => '',
        'amount' => 0,
        'txid' => '',
        'status' => 'pending',
        'wallet_address' => '',
        'admin_id' => null,
        'notes' => ''
    );
    
    $data = wp_parse_args($data, $defaults);
    
    $wpdb->insert($table, $data);
    
    return $wpdb->insert_id;
}

// تابع دریافت قیمت لحظه‌ای ارز دیجیتال
function ibetcoin_get_current_price($coin = 'bitcoin') {
    $settings = get_option('ibetcoin_settings');
    $api_url = $settings['crypto_api'] . '/simple/price?ids=' . $coin . '&vs_currencies=usd';
    
    $response = wp_remote_get($api_url);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body[$coin]['usd'])) {
        return $body[$coin]['usd'];
    }
    
    return false;
}

// تابع ایجاد توکن امنیتی
function ibetcoin_generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// تابع ارسال ایمیل
function ibetcoin_send_email($to, $subject, $message) {
    $headers = array('Content-Type: text/html; charset=UTF-8');
    return wp_mail($to, $subject, $message, $headers);
}

// تابع بررسی دسترسی کاربر
function ibetcoin_check_user_access($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user = get_userdata($user_id);
    
    if ($user && !$user->has_cap('manage_options')) {
        return true;
    }
    
    return false;
}