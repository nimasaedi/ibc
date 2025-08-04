<?php
defined('ABSPATH') or die('No script kiddies please!');

// توجه: تابع ibetcoin_generate_tracking_code فقط در functions.php تعریف شده و اینجا حذف شده

// ثبت واریز با تولید کد پیگیری
add_action('wp_ajax_ibetcoin_submit_deposit', 'ibetcoin_ajax_submit_deposit');
function ibetcoin_ajax_submit_deposit() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('لطفاً ابتدا وارد شوید');
    }

    $user_id = get_current_user_id();
    $amount = floatval($_POST['amount']);
    $txid = sanitize_text_field($_POST['txid']);

    $settings = get_option('ibetcoin_settings');
    $min_deposit = floatval($settings['min_deposit']);

    if ($amount < $min_deposit) {
        wp_send_json_error('حداقل مبلغ واریز ' . $min_deposit . ' USDT می‌باشد');
    }

    if (empty($txid)) {
        wp_send_json_error('شناسه تراکنش را وارد نمایید');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';

    // بررسی تکراری نبودن تراکنش
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE txid = %s AND type = 'deposit'",
        $txid
    ));

    if ($existing) {
        wp_send_json_error('این تراکنش قبلاً ثبت شده است');
    }

    // تولید کد پیگیری (تابع از functions.php فراخوانی می‌شود)
    $tracking_code = ibetcoin_generate_tracking_code();
    if (!$tracking_code) {
        wp_send_json_error('خطا در تولید کد پیگیری، لطفاً دوباره تلاش کنید');
    }

    // ثبت تراکنش با کد پیگیری
    $inserted = $wpdb->insert($table, array(
        'user_id' => $user_id,
        'type' => 'deposit',
        'amount' => $amount,
        'txid' => $txid,
        'tracking_code' => $tracking_code,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ));

    if ($inserted) {
        // ارسال ایمیل به مدیر
        $admin_email = get_option('admin_email');
        $subject = 'درخواست واریز جدید';
        $message = "کاربر #$user_id درخواست واریز $amount USDT را ثبت کرده است.\nTXID: $txid\nکد پیگیری: $tracking_code";
        wp_mail($admin_email, $subject, $message);

        wp_send_json_success('درخواست واریز ثبت شد');
    } else {
        wp_send_json_error('خطا در ثبت درخواست');
    }
}

// تایید واریز (مدیریت) - بدون تغییر
function ibetcoin_approve_deposit($tx_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';

    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND type = 'deposit' AND status = 'pending'",
        $tx_id
    ));

    if (!$transaction) return false;

    $wpdb->query('START TRANSACTION');

    try {
        $wpdb->update($table,
            array(
                'status' => 'completed',
                'admin_id' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $tx_id)
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE $main_wallet_table SET balance = balance + %f WHERE id = 1",
            $transaction->amount
        ));

        $user = get_userdata($transaction->user_id);
        if ($user) {
            $subject = __('Your Deposit Has Been Approved', 'ibetcoin');
            $message = sprintf(__('Your deposit of %s USDT has been approved and added to your account balance.', 'ibetcoin'), $transaction->amount);
            ibetcoin_send_email($user->user_email, $subject, $message);
        }

        $wpdb->query('COMMIT');
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return false;
    }
}

// ثبت برداشت با تولید کد پیگیری (شبیه واریز)
function ibetcoin_submit_withdrawal($user_id, $amount, $wallet_address) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';

    $min_withdrawal = defined('IBETCOIN_MIN_WITHDRAWAL') ? IBETCOIN_MIN_WITHDRAWAL : 50;
    if ($amount < $min_withdrawal) {
        return new WP_Error('amount_too_low', 'Minimum withdrawal amount is ' . $min_withdrawal . ' USDT.');
    }

    // Generate unique tracking code (make sure this function exists)
    if (!function_exists('ibetcoin_generate_tracking_code')) {
        return new WP_Error('tracking_code_error', 'Tracking code generation function missing.');
    }
    $tracking_code = ibetcoin_generate_tracking_code();
    if (!$tracking_code) {
        return new WP_Error('tracking_code_error', 'Error generating tracking code, please try again.');
    }

    $inserted = $wpdb->insert($table, array(
        'user_id' => $user_id,
        'type' => 'withdraw',
        'amount' => $amount,
        'wallet_address' => sanitize_text_field($wallet_address),
        'tracking_code' => $tracking_code,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ));

    if ($inserted) {
        return true;
    } else {
        return new WP_Error('db_error', 'Error recording withdrawal request.');
    }
}


// تایید برداشت (مدیریت) - بدون تغییر
function ibetcoin_approve_withdrawal($tx_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';

    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND type = 'withdraw' AND status = 'pending'",
        $tx_id
    ));

    if (!$transaction) return false;

    $main_balance = $wpdb->get_var("SELECT balance FROM $main_wallet_table WHERE id = 1");

    if ($main_balance < $transaction->amount) {
        return false;
    }

    $wpdb->query('START TRANSACTION');

    try {
        $wpdb->update($table,
            array(
                'status' => 'completed',
                'admin_id' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $tx_id)
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE $main_wallet_table SET balance = balance - %f WHERE id = 1",
            $transaction->amount
        ));

        $user = get_userdata($transaction->user_id);
        if ($user) {
            $subject = __('Your Withdrawal Has Been Approved', 'ibetcoin');
            $message = sprintf(__('Your withdrawal request of %s USDT has been approved. The amount will be transferred to your wallet shortly.', 'ibetcoin'), $transaction->amount);
            ibetcoin_send_email($user->user_email, $subject, $message);
        }

        $wpdb->query('COMMIT');
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return false;
    }
}

// ثبت درخواست برداشت توسط کاربر از طریق ایجکس
add_action('wp_ajax_ibetcoin_request_withdrawal', 'ibetcoin_ajax_request_withdrawal');
function ibetcoin_ajax_request_withdrawal() {
    check_ajax_referer('ibetcoin-nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('Please login first.', 'ibetcoin'));
    }

    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';

    if ($amount <= 0) {
        wp_send_json_error(__('Invalid amount.', 'ibetcoin'));
    }

    $min_withdrawal = defined('IBETCOIN_MIN_WITHDRAWAL') ? IBETCOIN_MIN_WITHDRAWAL : 50;
    if ($amount < $min_withdrawal) {
        wp_send_json_error(sprintf(__('Minimum withdrawal amount is %s USDT.', 'ibetcoin'), $min_withdrawal));
    }

    if (empty($wallet_address)) {
        wp_send_json_error(__('Wallet address is required.', 'ibetcoin'));
    }

    // بررسی موجودی کاربر
    $balance = ibetcoin_get_user_balance($user_id);
    if ($balance < $amount) {
        wp_send_json_error(__('Insufficient balance.', 'ibetcoin'));
    }

    // تابع ثبت برداشت، در functions.php یا فایل اصلی پلاگین باید تعریف شده باشد
    $result = ibetcoin_submit_withdrawal($user_id, $amount, $wallet_address);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } elseif ($result === true) {
        wp_send_json_success(__('Withdrawal request submitted successfully.', 'ibetcoin'));
    } else {
        wp_send_json_error(__('An unknown error occurred.', 'ibetcoin'));
    }
}

// رد تراکنش (مدیریت) - بدون تغییر
function ibetcoin_reject_transaction($tx_id, $reason = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';

    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND status = 'pending'",
        $tx_id
    ));

    if (!$transaction) return false;

    $updated = $wpdb->update($table,
        array(
            'status' => 'rejected',
            'admin_id' => get_current_user_id(),
            'notes' => $reason,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $tx_id)
    );

    if ($updated) {
        if ($transaction->type == 'withdraw') {
            ibetcoin_add_transaction(array(
                'user_id' => $transaction->user_id,
                'type' => 'refund',
                'amount' => $transaction->amount,
                'status' => 'completed',
                'notes' => __('Withdrawal rejection refund', 'ibetcoin')
            ));
        }

        $user = get_userdata($transaction->user_id);
        if ($user) {
            $subject = __('Your Transaction Has Been Rejected', 'ibetcoin');
            $message = sprintf(__('Your %s request of %s USDT has been rejected. Reason: %s', 'ibetcoin'),
                $transaction->type,
                $transaction->amount,
                $reason ?: __('Not specified', 'ibetcoin')
            );
            ibetcoin_send_email($user->user_email, $subject, $message);
        }

        return true;
    }

    return false;
}





// تابع دریافت موجودی کاربر
function ibetcoin_get_user_balance($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_wallets';

    $balance = $wpdb->get_var($wpdb->prepare(
        "SELECT balance FROM $table WHERE user_id = %d",
        $user_id
    ));

    return $balance !== null ? floatval($balance) : 0;
}







