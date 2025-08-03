<?php
defined('ABSPATH') or die('No script kiddies please!');

// ثبت‌نام کاربر جدید
function ibetcoin_register_user($username, $email, $password) {
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // تنظیم نقش کاربر
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // ایجاد رکورد کیف پول کاربر
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'ibetcoin_transactions',
        array(
            'user_id' => $user_id,
            'type' => 'initial_balance',
            'amount' => 0,
            'status' => 'completed'
        )
    );
    
    return $user_id;
}

// ورود کاربر
function ibetcoin_login_user($username, $password) {
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        return $user;
    }
    
    return $user->ID;
}

// شورتکد فرم ثبت‌نام
function ibetcoin_register_form_shortcode() {
    if (is_user_logged_in()) {
        return '<div class="ibetcoin-alert">شما قبلاً وارد شده‌اید</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/register.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_register', 'ibetcoin_register_form_shortcode');

// شورتکد فرم ورود
function ibetcoin_login_form_shortcode() {
    if (is_user_logged_in()) {
        return '<div class="ibetcoin-alert">شما قبلاً وارد شده‌اید</div>';
    }
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/login.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_login', 'ibetcoin_login_form_shortcode');

// پردازش AJAX ثبت‌نام
add_action('wp_ajax_nopriv_ibetcoin_register', 'ibetcoin_ajax_register');
function ibetcoin_ajax_register() {
    check_ajax_referer('ibetcoin-auth-nonce', 'nonce');
    
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // اعتبارسنجی
    if ($password !== $confirm_password) {
        wp_send_json_error('رمز عبور و تکرار آن مطابقت ندارند');
    }
    
    if (strlen($password) < 6) {
        wp_send_json_error('رمز عبور باید حداقل 6 کاراکتر باشد');
    }
    
    $user_id = ibetcoin_register_user($username, $email, $password);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }
    
    // ورود خودکار پس از ثبت‌نام
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    wp_send_json_success('ثبت‌نام با موفقیت انجام شد');
}

// پردازش AJAX ورود
add_action('wp_ajax_nopriv_ibetcoin_login', 'ibetcoin_ajax_login');
function ibetcoin_ajax_login() {
    check_ajax_referer('ibetcoin-auth-nonce', 'nonce');
    
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    
    $user_id = ibetcoin_login_user($username, $password);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error('نام کاربری یا رمز عبور نادرست است');
    }
    
    wp_send_json_success('ورود با موفقیت انجام شد');
}