<?php
defined('ABSPATH') or die('No script kiddies please!');

// بارگذاری اسکریپت‌های مورد نیاز و نانس
wp_enqueue_script('jquery');

?>

<div class="max-w-md mx-auto p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800"><?php _e('Withdraw Funds', 'ibetcoin'); ?></h2>
    
    <div class="mb-4 text-gray-700">
        <p><?php _e('Request a withdrawal to your USDT (TRC20) wallet address:', 'ibetcoin'); ?></p>
        
        <div class="mt-2 flex justify-between items-center bg-gray-100 px-4 py-3 rounded">
            <span class="font-medium"><?php _e('Available Balance:', 'ibetcoin'); ?></span>
            <span class="font-semibold text-indigo-600"><?php echo number_format(ibetcoin_get_user_balance(get_current_user_id()), 2); ?> USDT</span>
        </div>
        
        <div class="mt-3 text-sm text-gray-500">
            <p><?php printf(__('Minimum withdrawal amount: %s USDT', 'ibetcoin'), IBETCOIN_MIN_WITHDRAWAL); ?></p>
            <p><?php _e('Withdrawal requests are processed within 24 hours.', 'ibetcoin'); ?></p>
        </div>
    </div>
    
    <form id="ibetcoin-withdraw-form" class="space-y-5">
        <div>
            <label for="withdraw-amount" class="block mb-1 font-medium text-gray-700"><?php _e('Amount (USDT)', 'ibetcoin'); ?></label>
            <input 
                type="number" 
                id="withdraw-amount" 
                name="amount" 
                min="<?php echo IBETCOIN_MIN_WITHDRAWAL; ?>" 
                step="0.01" 
                required
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="<?php _e('Enter amount to withdraw', 'ibetcoin'); ?>"
            >
        </div>
        
        <div>
            <label for="withdraw-address" class="block mb-1 font-medium text-gray-700"><?php _e('Your Wallet Address', 'ibetcoin'); ?></label>
            <input 
                type="text" 
                id="withdraw-address" 
                name="wallet_address" 
                required
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="<?php _e('Enter your TRC20 wallet address', 'ibetcoin'); ?>"
            >
            <p class="mt-1 text-sm text-gray-500"><?php _e('Enter your TRC20 wallet address', 'ibetcoin'); ?></p>
        </div>
        
        <button type="submit" 
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded transition-colors duration-300">
            <?php _e('Request Withdrawal', 'ibetcoin'); ?>
        </button>
    </form>
    
    <div id="ibetcoin-withdraw-message" class="mt-4 text-center font-medium"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ibetcoin-withdraw-form').on('submit', function(e) {
        e.preventDefault();
        
        var amount = parseFloat($('#withdraw-amount').val());
        var wallet_address = $('#withdraw-address').val().trim();
        var minWithdrawal = <?php echo IBETCOIN_MIN_WITHDRAWAL; ?>;
        
        if (isNaN(amount) || amount < minWithdrawal) {
            $('#ibetcoin-withdraw-message').css('color', 'red').text('<?php printf(__('Minimum withdrawal amount is %s USDT', 'ibetcoin'), IBETCOIN_MIN_WITHDRAWAL); ?>');
            return;
        }
        
        if (!wallet_address) {
            $('#ibetcoin-withdraw-message').css('color', 'red').text('<?php _e('Please enter your wallet address', 'ibetcoin'); ?>');
            return;
        }
        
        $('#ibetcoin-withdraw-message').text('...').css('color', 'gray');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'ibetcoin_request_withdrawal',
                amount: amount,
                wallet_address: wallet_address,
                nonce: '<?php echo wp_create_nonce('ibetcoin-nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#ibetcoin-withdraw-message').css('color', 'green').text(response.data);
                    $('#ibetcoin-withdraw-form')[0].reset();
                } else {
                    $('#ibetcoin-withdraw-message').css('color', 'red').text(response.data);
                }
            },
            error: function() {
                $('#ibetcoin-withdraw-message').css('color', 'red').text('<?php _e('An error occurred. Please try again.', 'ibetcoin'); ?>');
            }
        });
    });
});
</script>
