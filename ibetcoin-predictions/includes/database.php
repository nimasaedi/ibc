<?php
defined('ABSPATH') or die('No script kiddies please!');
function ibetcoin_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
	
	
	
	
	// اضافه کردن ستون balance به جدول users اگر وجود ندارد
    $column_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = '{$wpdb->users}' 
        AND COLUMN_NAME = 'ibetcoin_balance'"
    ));
    
    if (!$column_exists) {
        $wpdb->query("ALTER TABLE {$wpdb->users} ADD COLUMN ibetcoin_balance DECIMAL(20,8) NOT NULL DEFAULT 0");
    }
    
	
    // جدول تراکنش‌ها
    $table_transactions = $wpdb->prefix . 'ibetcoin_transactions';
    $sql_transactions = "CREATE TABLE $table_transactions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(20) NOT NULL,
        amount decimal(20,8) NOT NULL,
        txid varchar(100) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        wallet_address varchar(100) DEFAULT NULL,
        admin_id bigint(20) DEFAULT NULL,
        notes text DEFAULT NULL,
        tracking_code varchar(20) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY tracking_code_unique (tracking_code),
        KEY user_id (user_id),
        KEY type (type),
        KEY status (status)
    ) $charset_collate;";


    // جدول پیش‌بینی‌ها
    $table_predictions = $wpdb->prefix . 'ibetcoin_predictions';
    $sql_predictions = "CREATE TABLE $table_predictions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        direction varchar(10) NOT NULL,
        amount decimal(20,8) NOT NULL,
        start_price decimal(20,8) NOT NULL,
        end_price decimal(20,8) DEFAULT NULL,
        odds decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        profit decimal(20,8) DEFAULT NULL,
        started_at datetime NOT NULL,
        ended_at datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    // جدول ضرایب
    $table_odds = $wpdb->prefix . 'ibetcoin_odds';
    $sql_odds = "CREATE TABLE $table_odds (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        base_odds decimal(10,2) NOT NULL,
        increase_rate decimal(10,2) NOT NULL,
        streak_count int(11) NOT NULL DEFAULT 0,
        is_default tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_default (is_default)
    ) $charset_collate;";

    // جدول تاریخچه ضرایب
    $table_odds_history = $wpdb->prefix . 'ibetcoin_odds_history';
    $sql_odds_history = "CREATE TABLE $table_odds_history (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        odds_id bigint(20) NOT NULL,
        old_value decimal(10,2) NOT NULL,
        new_value decimal(10,2) NOT NULL,
        changed_by bigint(20) NOT NULL,
        reason text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY odds_id (odds_id)
    ) $charset_collate;";

    // جدول کیف پول اصلی
    $table_main_wallet = $wpdb->prefix . 'ibetcoin_main_wallet';
    $sql_main_wallet = "CREATE TABLE $table_main_wallet (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        balance decimal(20,8) NOT NULL DEFAULT 0,
        last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql_wallets);
    dbDelta($sql_transactions);
    dbDelta($sql_predictions);
    dbDelta($sql_odds);
    dbDelta($sql_odds_history);
    dbDelta($sql_main_wallet);

    // داده اولیه برای کیف پول اصلی
    $wpdb->query("INSERT IGNORE INTO $table_main_wallet (id, balance) VALUES (1, 0)");

    // داده اولیه برای ضرایب پیش‌فرض
    $default_odds = $wpdb->get_var("SELECT COUNT(*) FROM $table_odds WHERE is_default = 1");
    if (!$default_odds) {
        $settings = get_option('ibetcoin_settings');

        // اگر تنظیمات نبود، مقدار پیش‌فرض بده
        $base_odds = isset($settings['default_odds']) ? $settings['default_odds'] : 1.5;
        $increase_rate = isset($settings['odds_increase_rate']) ? $settings['odds_increase_rate'] : 0.5;

        $wpdb->insert($table_odds, array(
            'base_odds' => $base_odds,
            'increase_rate' => $increase_rate,
            'is_default' => 1
        ));
    }
}
