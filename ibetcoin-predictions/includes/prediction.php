<?php
defined('ABSPATH') or die('No script kiddies please!');

// شورتکد صفحه پیش‌بینی
function ibetcoin_prediction_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to make predictions', 'ibetcoin') . '</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/prediction.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_prediction', 'ibetcoin_prediction_shortcode');

// تابع AJAX برای دریافت قیمت لحظه‌ای
add_action('wp_ajax_ibetcoin_get_price', 'ibetcoin_ajax_get_price');
add_action('wp_ajax_nopriv_ibetcoin_get_price', 'ibetcoin_ajax_get_price');
function ibetcoin_ajax_get_price() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');
    
    $price = ibetcoin_get_current_price();
    
    if ($price) {
        wp_send_json_success(array(
            'price' => number_format($price, 2),
            'currency' => 'USD',
            'timestamp' => time()
        ));
    } else {
        wp_send_json_error(__('Failed to get current price', 'ibetcoin'));
    }
}

// تابع AJAX برای ثبت پیش‌بینی
add_action('wp_ajax_ibetcoin_make_prediction', 'ibetcoin_ajax_make_prediction');
function ibetcoin_ajax_make_prediction() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Authentication required', 'ibetcoin'));
    }
    
    $user_id = get_current_user_id();
    $direction = isset($_POST['direction']) ? sanitize_text_field($_POST['direction']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    $settings = get_option('ibetcoin_settings');
    $min_bet = $settings['min_bet'];
    $max_bet = $settings['max_bet'];
    $prediction_time = $settings['prediction_time'];
    $balance = ibetcoin_get_user_balance($user_id);
    
    // اعتبارسنجی
    if (!in_array($direction, array('up', 'down'))) {
        wp_send_json_error(__('Invalid prediction direction', 'ibetcoin'));
    }
    
    if ($amount < $min_bet || $amount > $max_bet) {
        wp_send_json_error(sprintf(__('Bet amount must be between %s and %s USDT', 'ibetcoin'), $min_bet, $max_bet));
    }
    
    if ($amount > $balance) {
        wp_send_json_error(__('Insufficient balance', 'ibetcoin'));
    }
    
    // دریافت ضرایب
    $odds = ibetcoin_get_user_odds($user_id);
    
    // دریافت قیمت فعلی
    $current_price = ibetcoin_get_current_price();
    
    if (!$current_price) {
        wp_send_json_error(__('Failed to get current price', 'ibetcoin'));
    }
    
    global $wpdb;
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    $transactions_table = $wpdb->prefix . 'ibetcoin_transactions';
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // ثبت پیش‌بینی
        $wpdb->insert($predictions_table, array(
            'user_id' => $user_id,
            'direction' => $direction,
            'amount' => $amount,
            'start_price' => $current_price,
            'odds' => $odds,
            'status' => 'pending',
            'started_at' => current_time('mysql'),
            'ended_at' => date('Y-m-d H:i:s', time() + $prediction_time)
        ));
        
        $prediction_id = $wpdb->insert_id;
        
        // کسر مبلغ از حساب کاربر
        ibetcoin_add_transaction(array(
            'user_id' => $user_id,
            'type' => 'bet',
            'amount' => $amount,
            'status' => 'completed'
        ));
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'prediction_id' => $prediction_id,
            'end_time' => time() + $prediction_time,
            'message' => __('Prediction submitted successfully', 'ibetcoin')
        ));
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(__('Failed to submit prediction', 'ibetcoin'));
    }
}

// تابع دریافت ضرایب کاربر
function ibetcoin_get_user_odds($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_odds';
    
    // دریافت ضرایب اختصاصی کاربر
    $odds = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d LIMIT 1",
        $user_id
    ));
    
    // اگر ضرایب اختصاصی وجود نداشت، از ضرایب پیش‌فرض استفاده کن
    if (!$odds) {
        $odds = $wpdb->get_row("SELECT * FROM $table WHERE is_default = 1 LIMIT 1");
    }
    
    // محاسبه ضریب نهایی بر اساس بردهای متوالی
    $streak = ibetcoin_get_user_streak($user_id);
    $final_odds = $odds->base_odds + ($streak * $odds->increase_rate / 100);
    
    return $final_odds;
}

// تابع دریافت تعداد بردهای متوالی کاربر
function ibetcoin_get_user_streak($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_predictions';
    
    $streak = 0;
    $last_predictions = $wpdb->get_results($wpdb->prepare(
        "SELECT status FROM $table 
        WHERE user_id = %d 
        ORDER BY created_at DESC 
        LIMIT 10",
        $user_id
    ));
    
    foreach ($last_predictions as $pred) {
        if ($pred->status == 'win') {
            $streak++;
        } else {
            break;
        }
    }
    
    return $streak;
}

// تابع بررسی نتیجه پیش‌بینی‌ها
function ibetcoin_check_predictions() {
    global $wpdb;
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    $transactions_table = $wpdb->prefix . 'ibetcoin_transactions';
    
    // دریافت پیش‌بینی‌های در انتظار که زمانشان به پایان رسیده
    $pending_predictions = $wpdb->get_results("
        SELECT * FROM $predictions_table 
        WHERE status = 'pending' 
        AND ended_at <= UTC_TIMESTAMP()
        LIMIT 10
    ");
    
    foreach ($pending_predictions as $prediction) {
        // دریافت قیمت فعلی
        $current_price = ibetcoin_get_current_price();
        
        if (!$current_price) continue;
        
        // تعیین نتیجه
        $status = 'lose';
        $profit = 0;
        
        if (($prediction->direction == 'up' && $current_price > $prediction->start_price) ||
            ($prediction->direction == 'down' && $current_price < $prediction->start_price)) {
            $status = 'win';
            $profit = $prediction->amount * $prediction->odds;
        }
        
        // شروع تراکنش
        $wpdb->query('START TRANSACTION');
        
        try {
            // به‌روزرسانی وضعیت پیش‌بینی
            $wpdb->update($predictions_table, 
                array(
                    'end_price' => $current_price,
                    'status' => $status,
                    'profit' => $profit,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $prediction->id)
            );
            
            // واریز سود در صورت برد
            if ($status == 'win') {
                ibetcoin_add_transaction(array(
                    'user_id' => $prediction->user_id,
                    'type' => 'win',
                    'amount' => $profit,
                    'status' => 'completed'
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
        }
    }
}
add_action('init', 'ibetcoin_check_predictions');