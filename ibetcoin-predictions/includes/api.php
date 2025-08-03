<?php
defined('ABSPATH') or die('No script kiddies please!');

// REST API برای دریافت اطلاعات کاربر
add_action('rest_api_init', function() {
    register_rest_route('ibetcoin/v1', '/user/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'ibetcoin_rest_get_user',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    register_rest_route('ibetcoin/v1', '/transactions', array(
        'methods' => 'GET',
        'callback' => 'ibetcoin_rest_get_transactions',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
});

// تابع دریافت اطلاعات کاربر
function ibetcoin_rest_get_user($data) {
    global $wpdb;
    
    $user_id = $data['id'];
    $user = get_userdata($user_id);
    
    if (!$user) {
        return new WP_Error('user_not_found', __('User not found', 'ibetcoin'), array('status' => 404));
    }
    
    $wallets_table = $wpdb->prefix . 'ibetcoin_wallets';
    $transactions_table = $wpdb->prefix . 'ibetcoin_transactions';
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    
    $wallet = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $wallets_table WHERE user_id = %d LIMIT 1",
        $user_id
    ));
    
    $balance = ibetcoin_get_user_balance($user_id);
    
    $deposits = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $transactions_table 
        WHERE user_id = %d AND type = 'deposit' AND status = 'completed'",
        $user_id
    ));
    
    $withdrawals = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $transactions_table 
        WHERE user_id = %d AND type = 'withdraw' AND status = 'completed'",
        $user_id
    ));
    
    $total_bets = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $predictions_table 
        WHERE user_id = %d",
        $user_id
    ));
    
    $wins = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $predictions_table 
        WHERE user_id = %d AND status = 'win'",
        $user_id
    ));
    
    $losses = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $predictions_table 
        WHERE user_id = %d AND status = 'lose'",
        $user_id
    ));
    
    return array(
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'registered' => $user->user_registered,
        'wallet' => $wallet,
        'balance' => $balance,
        'stats' => array(
            'deposits' => floatval($deposits),
            'withdrawals' => floatval($withdrawals),
            'total_bets' => floatval($total_bets),
            'wins' => intval($wins),
            'losses' => intval($losses),
            'win_rate' => ($wins + $losses) > 0 ? round($wins / ($wins + $losses) * 100, 2) : 0
        )
    );
}

// تابع دریافت تراکنش‌ها
function ibetcoin_rest_get_transactions($data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $users_table = $wpdb->prefix . 'users';
    
    $params = $data->get_params();
    $page = isset($params['page']) ? intval($params['page']) : 1;
    $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
    $offset = ($page - 1) * $per_page;
    
    $where = array();
    $prepare_args = array();
    
    if (isset($params['user_id'])) {
        $where[] = 't.user_id = %d';
        $prepare_args[] = $params['user_id'];
    }
    
    if (isset($params['type'])) {
        $where[] = 't.type = %s';
        $prepare_args[] = $params['type'];
    }
    
    if (isset($params['status'])) {
        $where[] = 't.status = %s';
        $prepare_args[] = $params['status'];
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "SELECT t.*, u.user_login, u.user_email 
        FROM $table t
        LEFT JOIN $users_table u ON t.user_id = u.ID
        $where_sql
        ORDER BY t.created_at DESC
        LIMIT %d, %d";
    
    $prepare_args[] = $offset;
    $prepare_args[] = $per_page;
    
    $transactions = $wpdb->get_results($wpdb->prepare($query, $prepare_args));
    
    $total_query = "SELECT COUNT(*) 
        FROM $table t
        LEFT JOIN $users_table u ON t.user_id = u.ID
        $where_sql";
    
    $total = $wpdb->get_var($wpdb->prepare($total_query, $prepare_args));
    
    return array(
        'data' => $transactions,
        'pagination' => array(
            'total' => intval($total),
            'per_page' => $per_page,
            'current_page' => $page,
            'last_page' => ceil($total / $per_page)
        )
    );
}

// Webhook برای پرداخت‌ها
add_action('rest_api_init', function() {
    register_rest_route('ibetcoin/v1', '/payment/webhook', array(
        'methods' => 'POST',
        'callback' => 'ibetcoin_payment_webhook',
        'permission_callback' => '__return_true'
    ));
});

function ibetcoin_payment_webhook($request) {
    $settings = get_option('ibetcoin_settings');
    $secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';
    
    $headers = $request->get_headers();
    $signature = isset($headers['x_signature']) ? $headers['x_signature'][0] : '';
    
    if (empty($secret) || $signature !== hash_hmac('sha256', $request->get_body(), $secret)) {
        return new WP_Error('invalid_signature', __('Invalid signature', 'ibetcoin'), array('status' => 403));
    }
    
    $data = $request->get_json_params();
    
    if (empty($data['txid']) || empty($data['amount']) || empty($data['user_id'])) {
        return new WP_Error('invalid_data', __('Invalid data', 'ibetcoin'), array('status' => 400));
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    
    // بررسی تکراری نبودن تراکنش
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE txid = %s AND type = 'deposit'",
        $data['txid']
    ));
    
    if ($existing) {
        return new WP_Error('duplicate_transaction', __('Duplicate transaction', 'ibetcoin'), array('status' => 400));
    }
    
    // ثبت تراکنش
    $tx_id = ibetcoin_add_transaction(array(
        'user_id' => $data['user_id'],
        'type' => 'deposit',
        'amount' => $data['amount'],
        'txid' => $data['txid'],
        'status' => 'completed'
    ));
    
    if ($tx_id) {
        // به‌روزرسانی کیف پول اصلی
        $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';
        $wpdb->query($wpdb->prepare(
            "UPDATE $main_wallet_table SET balance = balance + %f WHERE id = 1",
            $data['amount']
        ));
        
        return array(
            'success' => true,
            'transaction_id' => $tx_id
        );
    }
    
    return new WP_Error('failed', __('Failed to process transaction', 'ibetcoin'), array('status' => 500));
}