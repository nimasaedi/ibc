<?php
defined('ABSPATH') or die('No script kiddies please!');

// شورتکد نمایش تاریخچه تراکنش‌ها
function ibetcoin_transactions_history_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to view transactions history', 'ibetcoin') . '</div>';
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_transactions';
    $user_id = get_current_user_id();
    
    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
        WHERE user_id = %d 
        ORDER BY created_at DESC 
        LIMIT 20",
        $user_id
    ));
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/transactions-history.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_transactions_history', 'ibetcoin_transactions_history_shortcode');

// شورتکد نمایش تاریخچه پیش‌بینی‌ها
function ibetcoin_predictions_history_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="ibetcoin-alert">' . __('Please login to view predictions history', 'ibetcoin') . '</div>';
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ibetcoin_predictions';
    $user_id = get_current_user_id();
    
    $predictions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
        WHERE user_id = %d 
        ORDER BY created_at DESC 
        LIMIT 20",
        $user_id
    ));
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/predictions-history.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_predictions_history', 'ibetcoin_predictions_history_shortcode');

// شورتکد نمایش آخرین بردها
function ibetcoin_last_wins_shortcode($atts) {
    global $wpdb;
    $predictions_table = $wpdb->prefix . 'ibetcoin_predictions';
    $users_table = $wpdb->prefix . 'users';
    
    $atts = shortcode_atts(array(
        'limit' => 5
    ), $atts);
    
    $wins = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.user_login 
        FROM $predictions_table p
        LEFT JOIN $users_table u ON p.user_id = u.ID
        WHERE p.status = 'win'
        ORDER BY p.updated_at DESC
        LIMIT %d",
        $atts['limit']
    ));
    
    ob_start();
    include IBETCOIN_PLUGIN_DIR . 'templates/frontend/last-wins.php';
    return ob_get_clean();
}
add_shortcode('ibetcoin_last_wins', 'ibetcoin_last_wins_shortcode');

// شورتکد نمایش قیمت لحظه‌ای
function ibetcoin_price_ticker_shortcode($atts) {
    $coin = isset($atts['coin']) ? sanitize_text_field($atts['coin']) : 'bitcoin';
    
    ob_start();
    ?>
    <div class="ibetcoin-price-ticker" data-coin="<?php echo esc_attr($coin); ?>">
        <span class="coin-name"><?php echo strtoupper($coin); ?></span>:
        <span class="coin-price">--</span> USD
        <span class="coin-change"></span>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ibetcoin_price_ticker', 'ibetcoin_price_ticker_shortcode');




