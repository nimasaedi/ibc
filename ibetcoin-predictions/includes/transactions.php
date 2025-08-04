<?php
defined('ABSPATH') or die('No script kiddies please!');



if (!function_exists('ibetcoin_add_transaction')) {
    function ibetcoin_add_transaction($args) {
        global $wpdb;

        $defaults = array(
            'user_id' => 0,
            'amount' => 0,
            'type' => '',         // withdraw, deposit, bet, win, refund, etc.
            'status' => 'pending',// pending, completed, rejected
            'notes' => '',
            'wallet_address' => '',
            'txid' => '',
            'tracking_code' => '',
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($args, $defaults);

        if (empty($data['user_id']) || empty($data['type']) || $data['amount'] <= 0) {
            return new WP_Error('invalid_data', __('Invalid transaction data', 'ibetcoin'));
        }

        $table = $wpdb->prefix . 'ibetcoin_transactions';

        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'status' => $data['status'],
                // 'details' => $data['details'],
                'notes' => $data['notes'],
                'wallet_address' => sanitize_text_field($data['wallet_address']),
                'txid' => sanitize_text_field($data['txid']),
                'tracking_code' => $data['tracking_code'],
                'created_at' => $data['created_at'],
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Error inserting transaction', 'ibetcoin'));
        }

        return true;
    }
}


if (!function_exists('ibetcoin_generate_tracking_code')) {
    // تابع تولید کد پیگیری (Tracking Code) یکتا
    function ibetcoin_generate_tracking_code($length = 5) {
        global $wpdb;
        $table = $wpdb->prefix . 'ibetcoin_transactions';

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max_attempts = 10;

        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $tracking_code = '';
            for ($i = 0; $i < $length; $i++) {
                $tracking_code .= $characters[rand(0, strlen($characters) - 1)];
            }

            // چک یکتا بودن در دیتابیس
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE tracking_code = %s", $tracking_code));
            if (!$exists) {
                return $tracking_code;
            }
        }

        // در صورت عدم موفقیت در تولید کد یکتا
        return false;
    }
}

if (!function_exists('ibetcoin_ajax_submit_deposit')) {
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

        // تولید کد پیگیری
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
}

if (!function_exists('ibetcoin_approve_deposit')) {
    // تایید واریز (مدیریت)
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
}

if (!function_exists('ibetcoin_submit_withdrawal')) {
    // ثبت برداشت با تولید کد پیگیری (شبیه واریز)
    function ibetcoin_submit_withdrawal($user_id, $amount, $wallet_address) {
        global $wpdb;
        $table = $wpdb->prefix . 'ibetcoin_transactions';

        $min_withdrawal = defined('IBETCOIN_MIN_WITHDRAWAL') ? IBETCOIN_MIN_WITHDRAWAL : 50;
        if ($amount < $min_withdrawal) {
            return new WP_Error('amount_too_low', 'حداقل مبلغ برداشت ' . $min_withdrawal . ' USDT می‌باشد');
        }

        $tracking_code = ibetcoin_generate_tracking_code();
        if (!$tracking_code) {
            return new WP_Error('tracking_code_error', 'خطا در تولید کد پیگیری، لطفاً دوباره تلاش کنید');
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
            return new WP_Error('db_error', 'خطا در ثبت درخواست برداشت');
        }
    }
}

if (!function_exists('ibetcoin_approve_withdrawal')) {
    // تایید برداشت (مدیریت)
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
}

if (!function_exists('ibetcoin_reject_transaction')) {
    // رد تراکنش (مدیریت)
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
}

?>
