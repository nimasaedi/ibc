<?php
defined('ABSPATH') or die('No script kiddies please!');

// تابع AJAX برای ثبت واریز


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
    
    // ثبت تراکنش
    $inserted = $wpdb->insert($table, array(
        'user_id' => $user_id,
        'type' => 'deposit',
        'amount' => $amount,
        'txid' => $txid,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ));
    
    if ($inserted) {
        // ارسال ایمیل به مدیر
        $admin_email = get_option('admin_email');
        $subject = 'درخواست واریز جدید';
        $message = "کاربر #$user_id درخواست واریز $amount USDT را ثبت کرده است.\nTXID: $txid";
        wp_mail($admin_email, $subject, $message);
        
        wp_send_json_success('درخواست واریز ثبت شد');
    } else {
        wp_send_json_error('خطا در ثبت درخواست');
    }
}







    















// تابع تایید واریز توسط مدیر
function ibetcoin_approve_deposit($tx_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';
    
    // دریافت اطلاعات تراکنش
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND type = 'deposit' AND status = 'pending'",
        $tx_id
    ));
    
    if (!$transaction) return false;
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // به‌روزرسانی وضعیت تراکنش
        $wpdb->update($table, 
            array(
                'status' => 'completed',
                'admin_id' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $tx_id)
        );
        
        // افزایش موجودی کیف پول اصلی
        $wpdb->query($wpdb->prepare(
            "UPDATE $main_wallet_table SET balance = balance + %f WHERE id = 1",
            $transaction->amount
        ));
        
        // ارسال ایمیل به کاربر
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

// تابع تایید برداشت توسط مدیر
function ibetcoin_approve_withdrawal($tx_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';
    
    // دریافت اطلاعات تراکنش
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND type = 'withdraw' AND status = 'pending'",
        $tx_id
    ));
    
    if (!$transaction) return false;
    
    // بررسی موجودی کیف پول اصلی
    $main_balance = $wpdb->get_var("SELECT balance FROM $main_wallet_table WHERE id = 1");
    
    if ($main_balance < $transaction->amount) {
        return false;
    }
    
    // شروع تراکنش
    $wpdb->query('START TRANSACTION');
    
    try {
        // به‌روزرسانی وضعیت تراکنش
        $wpdb->update($table, 
            array(
                'status' => 'completed',
                'admin_id' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $tx_id)
        );
        
        // کاهش موجودی کیف پول اصلی
        $wpdb->query($wpdb->prepare(
            "UPDATE $main_wallet_table SET balance = balance - %f WHERE id = 1",
            $transaction->amount
        ));
        
        // ارسال ایمیل به کاربر
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

// تابع رد تراکنش توسط مدیر
function ibetcoin_reject_transaction($tx_id, $reason = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    
    // دریافت اطلاعات تراکنش
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND status = 'pending'",
        $tx_id
    ));
    
    if (!$transaction) return false;
    
    // به‌روزرسانی وضعیت تراکنش
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
        // اگر تراکنش برداشت بود، مبلغ را به حساب کاربر برگردان
        if ($transaction->type == 'withdraw') {
            ibetcoin_add_transaction(array(
                'user_id' => $transaction->user_id,
                'type' => 'refund',
                'amount' => $transaction->amount,
                'status' => 'completed',
                'notes' => __('Withdrawal rejection refund', 'ibetcoin')
            ));
        }
        
        // ارسال ایمیل به کاربر
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