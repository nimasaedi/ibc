<div class="wrap ibetcoin-dashboard">
    <h1><?php _e('iBetCoin Dashboard', 'ibetcoin'); ?></h1>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3><?php _e('Total Users', 'ibetcoin'); ?></h3>
            <p><?php echo number_format($users_count['total_users']); ?></p>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Total Deposits', 'ibetcoin'); ?></h3>
            <p><?php echo number_format($deposits, 2); ?> USDT</p>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Total Withdrawals', 'ibetcoin'); ?></h3>
            <p><?php echo number_format($withdrawals, 2); ?> USDT</p>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Active Bets', 'ibetcoin'); ?></h3>
            <p><?php echo number_format($active_bets); ?></p>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Main Wallet Balance', 'ibetcoin'); ?></h3>
            <p><?php echo number_format($main_balance, 2); ?> USDT</p>
        </div>
    </div>
    
    <div class="recent-activity">
        <h2><?php _e('Recent Activity', 'ibetcoin'); ?></h2>
        
        <div class="activity-tabs">
            <ul>
                <li class="active"><a href="#recent-transactions"><?php _e('Transactions', 'ibetcoin'); ?></a></li>
                <li><a href="#recent-predictions"><?php _e('Predictions', 'ibetcoin'); ?></a></li>
            </ul>
            
            <div id="recent-transactions" class="tab-content active">
                <?php
                $transactions = $wpdb->get_results("
                    SELECT t.*, u.user_login 
                    FROM {$wpdb->prefix}ibetcoin_transactions t
                    LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
                    ORDER BY t.created_at DESC
                    LIMIT 10
                ");
                
                if ($transactions) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>
                        <th>ID</th>
                        <th>'.__('User', 'ibetcoin').'</th>
                        <th>'.__('Type', 'ibetcoin').'</th>
                        <th>'.__('Amount', 'ibetcoin').'</th>
                        <th>'.__('Status', 'ibetcoin').'</th>
                        <th>'.__('Date', 'ibetcoin').'</th>
                    </tr></thead>';
                    
                    foreach ($transactions as $tx) {
                        echo '<tr>
                            <td>'.$tx->id.'</td>
                            <td>'.$tx->user_login.'</td>
                            <td>'.ucfirst($tx->type).'</td>
                            <td>'.number_format($tx->amount, 2).' USDT</td>
                            <td><span class="status-badge '.$tx->status.'">'.ucfirst($tx->status).'</span></td>
                            <td>'.date('Y-m-d H:i', strtotime($tx->created_at)).'</td>
                        </tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>'.__('No transactions found', 'ibetcoin').'</p>';
                }
                ?>
            </div>
            
            <div id="recent-predictions" class="tab-content">
                <?php
                $predictions = $wpdb->get_results("
                    SELECT p.*, u.user_login 
                    FROM {$wpdb->prefix}ibetcoin_predictions p
                    LEFT JOIN {$wpdb->prefix}users u ON p.user_id = u.ID
                    ORDER BY p.created_at DESC
                    LIMIT 10
                ");
                
                if ($predictions) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>
                        <th>ID</th>
                        <th>'.__('User', 'ibetcoin').'</th>
                        <th>'.__('Direction', 'ibetcoin').'</th>
                        <th>'.__('Amount', 'ibetcoin').'</th>
                        <th>'.__('Odds', 'ibetcoin').'</th>
                        <th>'.__('Status', 'ibetcoin').'</th>
                        <th>'.__('Date', 'ibetcoin').'</th>
                    </tr></thead>';
                    
                    foreach ($predictions as $pred) {
                        echo '<tr>
                            <td>'.$pred->id.'</td>
                            <td>'.$pred->user_login.'</td>
                            <td>'.strtoupper($pred->direction).'</td>
                            <td>'.number_format($pred->amount, 2).' USDT</td>
                            <td>'.$pred->odds.'x</td>
                            <td><span class="status-badge '.$pred->status.'">'.ucfirst($pred->status).'</span></td>
                            <td>'.date('Y-m-d H:i', strtotime($pred->created_at)).'</td>
                        </tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>'.__('No predictions found', 'ibetcoin').'</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.activity-tabs ul li a').click(function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href');
        
        $('.activity-tabs ul li').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $(this).parent().addClass('active');
        $(tab_id).addClass('active');
    });
});
</script>