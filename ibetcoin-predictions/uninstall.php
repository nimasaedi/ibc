<?php
// امنیت پایه
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف جداول دیتابیس
global $wpdb;

$tables = array(
    'ibetcoin_wallets',
    'ibetcoin_transactions',
    'ibetcoin_predictions',
    'ibetcoin_odds',
    'ibetcoin_odds_history',
    'ibetcoin_main_wallet'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// حذف گزینه‌ها
delete_option('ibetcoin_settings');

// حذف صفحات ایجاد شده
$pages = array('prediction', 'wallet', 'deposit', 'withdraw', 'profile');

foreach ($pages as $slug) {
    $page = get_page_by_path($slug);
    
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}