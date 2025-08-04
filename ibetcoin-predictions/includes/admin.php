<?php
defined('ABSPATH') or die('No script kiddies please!');

/**
 * صفحه داشبورد مدیریت
 */
function ibetcoin_admin_dashboard() {
    global $wpdb;

    $users_count = count_users();
    $transactions_table = $wpdb->prefix . 'ibetcoin_transactions';
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    $main_wallet_table = $wpdb->prefix . 'ibetcoin_main_wallet';

    $deposits = $wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'deposit' AND status = 'completed'");
    $withdrawals = $wpdb->get_var("SELECT SUM(amount) FROM $transactions_table WHERE type = 'withdraw' AND status = 'completed'");
    $active_bets = $wpdb->get_var("SELECT COUNT(*) FROM $predictions_table WHERE status = 'pending'");
    $main_balance = $wpdb->get_var("SELECT balance FROM $main_wallet_table WHERE id = 1");

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/dashboard.php';
}

/**
 * صفحه مدیریت کاربران
 */
function ibetcoin_admin_users() {
    global $wpdb;

    $wallets_table = $wpdb->prefix . 'ibetcoin_wallets';
    $transactions_table = $wpdb->prefix . 'ibetcoin_transactions';

    if (isset($_GET['action'])) {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        switch ($_GET['action']) {
            case 'activate':
                update_user_meta($user_id, 'ibetcoin_account_status', 'active');
                break;
            case 'deactivate':
                update_user_meta($user_id, 'ibetcoin_account_status', 'inactive');
                break;
            case 'delete':
                $wpdb->delete($wallets_table, array('user_id' => $user_id));
                $wpdb->delete($transactions_table, array('user_id' => $user_id));
                wp_delete_user($user_id);
                break;
        }
    }

    $users = get_users(array(
        'exclude' => array(get_current_user_id()),
        'orderby' => 'registered',
        'order' => 'DESC'
    ));

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/users.php';
}

/**
 * صفحه تراکنش‌ها
 */
function ibetcoin_admin_transactions() {
    global $wpdb;

    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $users_table = $wpdb->prefix . 'users';
    $wallets_table = $wpdb->prefix . 'ibetcoin_wallets';

    // پردازش فرم آپدیت وضعیت تراکنش‌ها
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        check_admin_referer('ibetcoin_update_transaction');

        $tx_id = isset($_POST['tx_id']) ? intval($_POST['tx_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        $allowed_statuses = ['pending', 'completed', 'rejected'];

        if ($tx_id && in_array($status, $allowed_statuses, true)) {
            // به‌روزرسانی وضعیت تراکنش
            $wpdb->update(
                $table,
                array(
                    'status' => $status,
                    'notes' => $notes,
                    'admin_id' => get_current_user_id(),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $tx_id)
            );

            // بارگذاری اطلاعات تراکنش برای اعمال تغییرات روی کیف پول کاربر
            $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $tx_id));

            if ($transaction && $status === 'completed') {
                // اگر تراکنش واریز است، موجودی کیف پول کاربر افزایش پیدا کند
                if ($transaction->type === 'deposit') {
                    $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wallets_table WHERE user_id = %d", $transaction->user_id));

                    if ($wallet) {
                        $new_balance = floatval($wallet->balance) + floatval($transaction->amount);

                        $wpdb->update(
                            $wallets_table,
                            [
                                'balance' => $new_balance,
                                'updated_at' => current_time('mysql', 1)
                            ],
                            ['user_id' => $transaction->user_id],
                            ['%f', '%s'],
                            ['%d']
                        );
                    } else {
                        // اگر کیف پول کاربر وجود ندارد، ایجاد شود
                        $wpdb->insert(
                            $wallets_table,
                            [
                                'user_id' => $transaction->user_id,
                                'wallet_address' => '',
                                'balance' => floatval($transaction->amount),
                                'status' => 'active',
                                'created_at' => current_time('mysql', 1),
                                'updated_at' => current_time('mysql', 1)
                            ],
                            ['%d', '%s', '%f', '%s', '%s', '%s']
                        );
                    }
                }
                // اگر تراکنش برداشت است، موجودی کیف پول کاربر کاهش یابد
                elseif ($transaction->type === 'withdraw') {
                    $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wallets_table WHERE user_id = %d", $transaction->user_id));

                    if ($wallet) {
                        $new_balance = floatval($wallet->balance) - floatval($transaction->amount);

                        // جلوگیری از منفی شدن موجودی کیف پول
                        if ($new_balance < 0) {
                            $new_balance = 0;
                        }

                        $wpdb->update(
                            $wallets_table,
                            [
                                'balance' => $new_balance,
                                'updated_at' => current_time('mysql', 1)
                            ],
                            ['user_id' => $transaction->user_id],
                            ['%f', '%s'],
                            ['%d']
                        );
                    } else {
                        // اگر کیف پول کاربر وجود نداشته باشد، می‌توانید خطا لاگ کنید یا به صورت دلخواه مدیریت کنید
                    }
                }
            }

            // ریدایرکت به صفحه تراکنش‌ها بعد از عملیات
            wp_redirect(admin_url('admin.php?page=ibetcoin-transactions'));
            exit;
        }
    }

    // گرفتن لیست تراکنش‌ها بر اساس جدیدترین‌ها (id نزولی)
    $sql = "
        SELECT t.*, u.user_login, u.user_email
        FROM $table t
        LEFT JOIN $users_table u ON t.user_id = u.ID
        ORDER BY t.id DESC
        LIMIT 100
    ";

    $transactions = $wpdb->get_results($sql);

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/transactions.php';
}


/**
 * صفحه پیش‌بینی‌ها
 */
function ibetcoin_admin_predictions() {
    global $wpdb;

    $table = $wpdb->prefix . 'ibetcoin_predictions';
    $users_table = $wpdb->prefix . 'users';

    $predictions = $wpdb->get_results("SELECT p.*, u.user_login, u.user_email FROM $table p LEFT JOIN $users_table u ON p.user_id = u.ID ORDER BY p.created_at DESC LIMIT 100");

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/predictions.php';
}

/**
 * صفحه مدیریت ضرایب
 */
function ibetcoin_admin_odds() {
    global $wpdb;

    $table = $wpdb->prefix . 'ibetcoin_odds';
    $history_table = $wpdb->prefix . 'ibetcoin_odds_history';
    $users_table = $wpdb->prefix . 'users';

    if (isset($_POST['submit_odds'])) {
        check_admin_referer('ibetcoin_update_odds');

        $odds_id = isset($_POST['odds_id']) ? intval($_POST['odds_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $base_odds = isset($_POST['base_odds']) ? floatval($_POST['base_odds']) : 1.5;
        $increase_rate = isset($_POST['increase_rate']) ? floatval($_POST['increase_rate']) : 0.5;
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if ($odds_id) {
            $old_odds = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $odds_id));
            $wpdb->update($table,
                array(
                    'user_id' => $user_id,
                    'base_odds' => $base_odds,
                    'increase_rate' => $increase_rate,
                    'is_default' => $is_default,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $odds_id)
            );

            $wpdb->insert($history_table, array(
                'odds_id' => $odds_id,
                'old_value' => $old_odds->base_odds,
                'new_value' => $base_odds,
                'changed_by' => get_current_user_id(),
                'reason' => 'Manual update by admin'
            ));
        } else {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'base_odds' => $base_odds,
                'increase_rate' => $increase_rate,
                'is_default' => $is_default
            ));
        }
    }

    $odds_list = $wpdb->get_results("SELECT o.*, u.user_login, u.user_email FROM $table o LEFT JOIN $users_table u ON o.user_id = u.ID ORDER BY o.is_default DESC, o.user_id ASC");

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/odds.php';
}

/**
 * صفحه کیف پول اصلی
 */
function ibetcoin_admin_wallet() {
    global $wpdb;

    $table = $wpdb->prefix . 'ibetcoin_main_wallet';
    $balance = $wpdb->get_var("SELECT balance FROM $table WHERE id = 1");

    $settings = get_option('ibetcoin_settings');

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/wallet.php';
}

/**
 * صفحه تنظیمات پلاگین
 */
function ibetcoin_admin_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $settings = get_option('ibetcoin_settings');

    if (isset($_POST['submit_settings'])) {
        check_admin_referer('ibetcoin_update_settings');

        $new_settings = array(
            'main_wallet_address' => sanitize_text_field($_POST['main_wallet_address']),
            'default_odds' => floatval($_POST['default_odds']),
            'odds_increase_rate' => floatval($_POST['odds_increase_rate']),
            'min_deposit' => floatval($_POST['min_deposit']),
            'min_withdrawal' => floatval($_POST['min_withdrawal']),
            'min_bet' => floatval($_POST['min_bet']),
            'max_bet' => floatval($_POST['max_bet']),
            'prediction_time' => intval($_POST['prediction_time']),
            'crypto_api' => esc_url_raw($_POST['crypto_api'])
        );

        if (update_option('ibetcoin_settings', $new_settings)) {
            add_settings_error('ibetcoin_settings', 'ibetcoin_settings_updated', __('Settings saved successfully!', 'ibetcoin'), 'updated');
        } else {
            add_settings_error('ibetcoin_settings', 'ibetcoin_settings_failed', __('Failed to save settings.', 'ibetcoin'), 'error');
        }

        $settings = get_option('ibetcoin_settings');
    }

    include IBETCOIN_PLUGIN_DIR . 'templates/admin/settings.php';
}

/**
 * غیرفعال سازی کش در صفحات مدیریت پلاگین
 */
add_action('admin_init', 'ibetcoin_disable_caching');
function ibetcoin_disable_caching() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'ibetcoin') === 0) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
}

/**
 * نمایش اعلان‌های خطا یا موفقیت در تنظیمات
 */
add_action('admin_notices', 'ibetcoin_test_settings');
function ibetcoin_test_settings() {
    if (isset($_GET['page']) && $_GET['page'] === 'ibetcoin-settings') {
        $settings = get_option('ibetcoin_settings');
        echo '<div class="notice notice-info">';
        echo '<pre>تنظیمات ذخیره شده: ' . print_r($settings, true) . '</pre>';
        echo '</div>';
    }
}
