<div class="wrap ibetcoin-wallet">
    <h1><?php _e('Main Wallet Management', 'ibetcoin'); ?></h1>
    
    <div class="wallet-info">
        <div class="wallet-balance">
            <h2><?php _e('Current Balance', 'ibetcoin'); ?></h2>
            <p class="amount"><?php echo number_format($balance, 2); ?> USDT</p>
        </div>
        
        <div class="wallet-address">
            <h2><?php _e('Wallet Address', 'ibetcoin'); ?></h2>
            <p class="address"><?php echo esc_html($settings['main_wallet_address'] ?: __('Not set', 'ibetcoin')); ?></p>
        </div>
    </div>
    
    <div class="wallet-transactions">
        <h2><?php _e('Recent Transactions', 'ibetcoin'); ?></h2>
        
        <?php
        $transactions = $wpdb->get_results("
            SELECT t.*, u.user_login 
            FROM {$wpdb->prefix}ibetcoin_transactions t
            LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
            WHERE t.type IN ('deposit', 'withdraw') AND t.status = 'completed'
            ORDER BY t.created_at DESC
            LIMIT 10
        ");
        
        if ($transactions) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>
                <th>'.__('ID', 'ibetcoin').'</th>
                <th>'.__('User', 'ibetcoin').'</th>
                <th>'.__('Type', 'ibetcoin').'</th>
                <th>'.__('Amount', 'ibetcoin').'</th>
                <th>'.__('Date', 'ibetcoin').'</th>
            </tr></thead>';
            
            foreach ($transactions as $tx) {
                echo '<tr>
                    <td>'.$tx->id.'</td>
                    <td>'.($tx->user_login ? $tx->user_login : 'User #'.$tx->user_id).'</td>
                    <td>'.ucfirst($tx->type).'</td>
                    <td>'.number_format($tx->amount, 2).' USDT</td>
                    <td>'.date('Y-m-d H:i', strtotime($tx->created_at)).'</td>
                </tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>'.__('No transactions found', 'ibetcoin').'</p>';
        }
        ?>
    </div>
</div>