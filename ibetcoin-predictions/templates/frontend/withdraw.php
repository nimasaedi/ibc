<div class="ibetcoin-withdraw-container">
    <h2><?php _e('Withdraw Funds', 'ibetcoin'); ?></h2>
    
    <div class="withdraw-info">
        <p><?php _e('Request a withdrawal to your USDT (TRC20) wallet address:', 'ibetcoin'); ?></p>
        
        <div class="balance-info">
            <span><?php _e('Available Balance:', 'ibetcoin'); ?></span>
            <span class="amount"><?php echo number_format(ibetcoin_get_user_balance(get_current_user_id()), 2); ?> USDT</span>
        </div>
        
        <div class="withdraw-notice">
            <p><?php printf(__('Minimum withdrawal amount: %s USDT', 'ibetcoin'), IBETCOIN_MIN_WITHDRAWAL); ?></p>
            <p><?php _e('Withdrawal requests are processed within 24 hours.', 'ibetcoin'); ?></p>
        </div>
    </div>
    
    <div class="withdraw-form">
        <form id="ibetcoin-withdraw-form">
            <div class="form-group">
                <label for="withdraw-amount"><?php _e('Amount (USDT)', 'ibetcoin'); ?></label>
                <input type="number" id="withdraw-amount" name="amount" 
                       min="<?php echo IBETCOIN_MIN_WITHDRAWAL; ?>" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="withdraw-address"><?php _e('Your Wallet Address', 'ibetcoin'); ?></label>
                <input type="text" id="withdraw-address" name="wallet_address" required>
                <p class="description">
                    <?php _e('Enter your TRC20 wallet address', 'ibetcoin'); ?>
                </p>
            </div>
            
            <button type="submit" class="btn-submit">
                <?php _e('Request Withdrawal', 'ibetcoin'); ?>
            </button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // ثبت درخواست برداشت
    $('#ibetcoin-withdraw-form').on('submit', function(e) {
        e.preventDefault();
        
        var amount = parseFloat($('#withdraw-amount').val());
        var wallet_address = $('#withdraw-address').val().trim();
        
        if (amount < <?php echo IBETCOIN_MIN_WITHDRAWAL; ?>) {
            alert('<?php printf(__('Minimum withdrawal amount is %s USDT', 'ibetcoin'), IBETCOIN_MIN_WITHDRAWAL); ?>');
            return;
        }
        
        if (!wallet_address) {
            alert('<?php _e('Please enter your wallet address', 'ibetcoin'); ?>');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_request_withdrawal',
                amount: amount,
                wallet_address: wallet_address,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#ibetcoin-withdraw-form')[0].reset();
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
</script>