<?php
/*
Plugin Name: iBetCoin Crypto Prediction
Plugin URI: https://example.com/ibetcoin
Description: A complete cryptocurrency price prediction system for WordPress
Version: 1.0.0
Author: Your Name
Author URI: https://example.com
License: GPLv2 or later
Text Domain: ibetcoin
*/




// امنیت پایه
defined('ABSPATH') or die('No script kiddies please!');

// ثابت‌های پلاگین
define('IBETCOIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IBETCOIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IBETCOIN_VERSION', '1.0.0');
define('IBETCOIN_MIN_DEPOSIT', 10);
define('IBETCOIN_MIN_WITHDRAWAL', 50);
define('IBETCOIN_MIN_BET', 5);
define('IBETCOIN_MAX_BET', 10000);
define('IBETCOIN_PREDICTION_TIME', 300); // 5 دقیقه به ثانیه

// فایل‌های مورد نیاز
require_once IBETCOIN_PLUGIN_DIR . 'includes/database.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/functions.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/admin.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/user-dashboard.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/prediction.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/api.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/transactions.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/shortcodes.php';
require_once IBETCOIN_PLUGIN_DIR . 'includes/auth.php';

// فعال‌سازی پلاگین
register_activation_hook(__FILE__, 'ibetcoin_activate_plugin');

function ibetcoin_activate_plugin() {
    // ایجاد جداول دیتابیس
    ibetcoin_create_database_tables();
    
    // ایجاد صفحات مورد نیاز
    ibetcoin_create_necessary_pages();
    
    // تنظیمات اولیه
    if (!get_option('ibetcoin_settings')) {
        update_option('ibetcoin_settings', array(
            'main_wallet_address' => '',
            'default_odds' => 1.5,
            'odds_increase_rate' => 0.5,
            'min_deposit' => IBETCOIN_MIN_DEPOSIT,
            'min_withdrawal' => IBETCOIN_MIN_WITHDRAWAL,
            'min_bet' => IBETCOIN_MIN_BET,
            'max_bet' => IBETCOIN_MAX_BET,
            'prediction_time' => IBETCOIN_PREDICTION_TIME,
            'crypto_api' => 'https://api.coingecko.com/api/v3'
        ));
    }
}

// غیرفعال‌سازی پلاگین
register_deactivation_hook(__FILE__, 'ibetcoin_deactivate_plugin');

function ibetcoin_deactivate_plugin() {
    // حذف داده‌های موقت
    wp_clear_scheduled_hook('ibetcoin_update_prices');
}

// بارگذاری ترجمه‌ها
add_action('plugins_loaded', 'ibetcoin_load_textdomain');

function ibetcoin_load_textdomain() {
    load_plugin_textdomain('ibetcoin', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// اضافه کردن منو به پیشخوان وردپرس
add_action('admin_menu', 'ibetcoin_admin_menu');

function ibetcoin_admin_menu() {
    add_menu_page(
        __('iBetCoin', 'ibetcoin'),
        __('iBetCoin', 'ibetcoin'),
        'manage_options',
        'ibetcoin',
        'ibetcoin_admin_dashboard',
        'dashicons-chart-area',
        30
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Dashboard', 'ibetcoin'),
        __('Dashboard', 'ibetcoin'),
        'manage_options',
        'ibetcoin',
        'ibetcoin_admin_dashboard'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Users', 'ibetcoin'),
        __('Users', 'ibetcoin'),
        'manage_options',
        'ibetcoin-users',
        'ibetcoin_admin_users'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Transactions', 'ibetcoin'),
        __('Transactions', 'ibetcoin'),
        'manage_options',
        'ibetcoin-transactions',
        'ibetcoin_admin_transactions'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Predictions', 'ibetcoin'),
        __('Predictions', 'ibetcoin'),
        'manage_options',
        'ibetcoin-predictions',
        'ibetcoin_admin_predictions'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Odds Management', 'ibetcoin'),
        __('Odds Management', 'ibetcoin'),
        'manage_options',
        'ibetcoin-odds',
        'ibetcoin_admin_odds'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Main Wallet', 'ibetcoin'),
        __('Main Wallet', 'ibetcoin'),
        'manage_options',
        'ibetcoin-wallet',
        'ibetcoin_admin_wallet'
    );
    
    add_submenu_page(
        'ibetcoin',
        __('Settings', 'ibetcoin'),
        __('Settings', 'ibetcoin'),
        'manage_options',
        'ibetcoin-settings',
        'ibetcoin_admin_settings'
    );
}

// اضافه کردن استایل‌ها و اسکریپت‌ها
add_action('admin_enqueue_scripts', 'ibetcoin_admin_scripts');
add_action('wp_enqueue_scripts', 'ibetcoin_frontend_scripts');

function ibetcoin_admin_scripts($hook) {
    if (strpos($hook, 'ibetcoin') !== false) {
        wp_enqueue_style('ibetcoin-admin-style', IBETCOIN_PLUGIN_URL . 'assets/css/admin.css', array(), IBETCOIN_VERSION);
        wp_enqueue_script('ibetcoin-admin-script', IBETCOIN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), IBETCOIN_VERSION, true);
        wp_localize_script('ibetcoin-admin-script', 'ibetcoin_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ibetcoin-admin-nonce')
        ));
    }
}

function ibetcoin_frontend_scripts() {
    if (is_page(array('prediction', 'wallet', 'profile', 'deposit', 'withdraw'))) {
        wp_enqueue_style('ibetcoin-frontend-style', IBETCOIN_PLUGIN_URL . 'assets/css/frontend.css', array(), IBETCOIN_VERSION);
        wp_enqueue_script('ibetcoin-frontend-script', IBETCOIN_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), IBETCOIN_VERSION, true);
        wp_localize_script('ibetcoin-frontend-script', 'ibetcoin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ibetcoin-nonce')
        ));
    }
}

// ایجاد صفحات مورد نیاز هنگام فعال‌سازی پلاگین
function ibetcoin_create_necessary_pages() {
    $pages = array(
        'prediction' => array(
            'title' => __('Prediction', 'ibetcoin'),
            'content' => '[ibetcoin_prediction]',
            'template' => 'full-width.php'
        ),
        'wallet' => array(
            'title' => __('My Wallet', 'ibetcoin'),
            'content' => '[ibetcoin_wallet]',
            'template' => 'full-width.php'
        ),
        'deposit' => array(
            'title' => __('Deposit', 'ibetcoin'),
            'content' => '[ibetcoin_deposit]',
            'template' => 'full-width.php'
        ),
        'withdraw' => array(
            'title' => __('Withdraw', 'ibetcoin'),
            'content' => '[ibetcoin_withdraw]',
            'template' => 'full-width.php'
        ),
        'profile' => array(
            'title' => __('My Profile', 'ibetcoin'),
            'content' => '[ibetcoin_profile]',
            'template' => 'full-width.php'
        )
    );
	
	$pages['register'] = array(
    'title' => __('Register', 'ibetcoin'),
    'content' => '[ibetcoin_register]',
    'template' => 'full-width.php'
	);
	$pages['login'] = array(
		'title' => __('Login', 'ibetcoin'),
		'content' => '[ibetcoin_login]',
		'template' => 'full-width.php'
	);

    
    foreach ($pages as $slug => $page) {
        $existing_page = get_page_by_path($slug);
        
        if (!$existing_page) {
            $new_page = array(
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            );
            
            $page_id = wp_insert_post($new_page);
            
            if ($page['template']) {
                update_post_meta($page_id, '_wp_page_template', $page['template']);
            }
        }
    }
}