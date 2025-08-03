<div class="ibetcoin-wallet-container">
    <h2><?php _e('My Wallet', 'ibetcoin'); ?></h2>
    
    <div class="wallet-balance">
        <h3><?php _e('Current Balance', 'ibetcoin'); ?></h3>
        <p class="amount"><?php echo number_format(ibetcoin_get_user_balance(get_current_user_id()), 2); ?> USDT</p>
    </div>
    
    <div class="wallet-actions">
        <a href="<?php echo get_permalink(get_page_by_path('deposit')); ?>" class="btn-deposit">
            <?php _e('Deposit', 'ibetcoin'); ?>
        </a>
        <a href="<?php echo get_permalink(get_page_by_path('withdraw')); ?>" class="btn-withdraw">
            <?php _e('Withdraw', 'ibetcoin'); ?>
        </a>
    </div>
    
    <div class="wallet-transactions">
        <h3><?php _e('Recent Transactions', 'ibetcoin'); ?></h3>
        <?php echo do_shortcode('[ibetcoin_transactions_history]'); ?>
    </div>
</div>