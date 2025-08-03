<div class="wrap ibetcoin-settings">
    <h1><?php _e('iBetCoin Settings', 'ibetcoin'); ?></h1>
    
    <form method="post">
        <?php wp_nonce_field('ibetcoin_update_settings'); ?>
        
        <h2><?php _e('General Settings', 'ibetcoin'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="main_wallet_address"><?php _e('Main Wallet Address', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="text" name="main_wallet_address" id="main_wallet_address" 
                           class="regular-text" value="<?php echo esc_attr($settings['main_wallet_address']); ?>">
                    <p class="description">
                        <?php _e('Enter your TRC20 wallet address for deposits', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="crypto_api"><?php _e('Crypto API Endpoint', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="url" name="crypto_api" id="crypto_api" 
                           class="regular-text" value="<?php echo esc_url($settings['crypto_api']); ?>">
                    <p class="description">
                        <?php _e('API endpoint for cryptocurrency prices', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Betting Settings', 'ibetcoin'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_odds"><?php _e('Default Odds', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="default_odds" id="default_odds" 
                           step="0.01" min="1.0" max="10.0" required
                           value="<?php echo floatval($settings['default_odds']); ?>">
                    <p class="description">
                        <?php _e('Default odds multiplier for predictions', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="odds_increase_rate"><?php _e('Odds Increase Rate', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="odds_increase_rate" id="odds_increase_rate" 
                           step="0.01" min="0" max="10" required
                           value="<?php echo floatval($settings['odds_increase_rate']); ?>">%
                    <p class="description">
                        <?php _e('Percentage increase in odds for each consecutive win', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="prediction_time"><?php _e('Prediction Time (seconds)', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="prediction_time" id="prediction_time" 
                           min="30" max="600" required
                           value="<?php echo intval($settings['prediction_time']); ?>">
                    <p class="description">
                        <?php _e('Duration of each prediction in seconds (default: 300 = 5 minutes)', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Financial Limits', 'ibetcoin'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="min_deposit"><?php _e('Minimum Deposit', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="min_deposit" id="min_deposit" 
                           step="0.01" min="0" required
                           value="<?php echo floatval($settings['min_deposit']); ?>"> USDT
                    <p class="description">
                        <?php _e('Minimum deposit amount users can make', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="min_withdrawal"><?php _e('Minimum Withdrawal', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="min_withdrawal" id="min_withdrawal" 
                           step="0.01" min="0" required
                           value="<?php echo floatval($settings['min_withdrawal']); ?>"> USDT
                    <p class="description">
                        <?php _e('Minimum withdrawal amount users can request', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="min_bet"><?php _e('Minimum Bet', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="min_bet" id="min_bet" 
                           step="0.01" min="0" required
                           value="<?php echo floatval($settings['min_bet']); ?>"> USDT
                    <p class="description">
                        <?php _e('Minimum bet amount per prediction', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_bet"><?php _e('Maximum Bet', 'ibetcoin'); ?></label>
                </th>
                <td>
                    <input type="number" name="max_bet" id="max_bet" 
                           step="0.01" min="0" required
                           value="<?php echo floatval($settings['max_bet']); ?>"> USDT
                    <p class="description">
                        <?php _e('Maximum bet amount per prediction', 'ibetcoin'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="submit_settings" class="button button-primary">
                <?php _e('Save Settings', 'ibetcoin'); ?>
            </button>
        </p>
    </form>
</div>