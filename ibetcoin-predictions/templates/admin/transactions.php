<div class="wrap ibetcoin-transactions">
    <h1 class="wp-heading-inline"><?php _e('Transactions Management', 'ibetcoin'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="ibetcoin-transactions">
                <select name="type">
                    <option value=""><?php _e('All Types', 'ibetcoin'); ?></option>
                    <option value="deposit" <?php selected(isset($_GET['type']) && $_GET['type'] == 'deposit'); ?>>
                        <?php _e('Deposit', 'ibetcoin'); ?>
                    </option>
                    <option value="withdraw" <?php selected(isset($_GET['type']) && $_GET['type'] == 'withdraw'); ?>>
                        <?php _e('Withdraw', 'ibetcoin'); ?>
                    </option>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'ibetcoin'); ?></option>
                    <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] == 'pending'); ?>>
                        <?php _e('Pending', 'ibetcoin'); ?>
                    </option>
                    <option value="completed" <?php selected(isset($_GET['status']) && $_GET['status'] == 'completed'); ?>>
                        <?php _e('Completed', 'ibetcoin'); ?>
                    </option>
                    <option value="rejected" <?php selected(isset($_GET['status']) && $_GET['status'] == 'rejected'); ?>>
                        <?php _e('Rejected', 'ibetcoin'); ?>
                    </option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'ibetcoin'); ?>">
            </form>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'ibetcoin'); ?></th>
                <th><?php _e('User', 'ibetcoin'); ?></th>
                <th><?php _e('Type', 'ibetcoin'); ?></th>
                <th><?php _e('Amount', 'ibetcoin'); ?></th>
                <th><?php _e('Wallet/TXID', 'ibetcoin'); ?></th>
                <th><?php _e('Status', 'ibetcoin'); ?></th>
                <th><?php _e('Date', 'ibetcoin'); ?></th>
                <th><?php _e('Actions', 'ibetcoin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx) : ?>
            <tr>
                <td><?php echo $tx->id; ?></td>
                <td><?php echo $tx->user_login ? $tx->user_login : 'User #'.$tx->user_id; ?></td>
                <td><?php echo ucfirst($tx->type); ?></td>
                <td><?php echo number_format($tx->amount, 2); ?> USDT</td>
                <td><?php echo $tx->txid ? substr($tx->txid, 0, 10).'...' : ($tx->wallet_address ? substr($tx->wallet_address, 0, 10).'...' : '-'); ?></td>
                <td>
                    <span class="status-badge <?php echo $tx->status; ?>">
                        <?php echo ucfirst($tx->status); ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
                <td>
                    <?php if ($tx->status == 'pending') : ?>
                    <div class="row-actions">
                        <a href="#" class="edit-tx" data-txid="<?php echo $tx->id; ?>">
                            <?php _e('Edit', 'ibetcoin'); ?>
                        </a>
                    </div>
                    
                    <div class="tx-edit-form" id="tx-edit-<?php echo $tx->id; ?>" style="display:none;">
                        <form method="post">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="tx_id" value="<?php echo $tx->id; ?>">
                            <?php wp_nonce_field('ibetcoin_update_transaction'); ?>
                            
                            <select name="status" required>
                                <option value="completed" <?php selected($tx->status, 'completed'); ?>>
                                    <?php _e('Completed', 'ibetcoin'); ?>
                                </option>
                                <option value="rejected" <?php selected($tx->status, 'rejected'); ?>>
                                    <?php _e('Rejected', 'ibetcoin'); ?>
                                </option>
                            </select>
                            
                            <textarea name="notes" placeholder="<?php _e('Notes', 'ibetcoin'); ?>"><?php echo esc_textarea($tx->notes); ?></textarea>
                            
                            <button type="submit" class="button button-primary">
                                <?php _e('Update', 'ibetcoin'); ?>
                            </button>
                            <a href="#" class="button cancel-edit">
                                <?php _e('Cancel', 'ibetcoin'); ?>
                            </a>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('.edit-tx').click(function(e) {
        e.preventDefault();
        var tx_id = $(this).data('txid');
        $('#tx-edit-' + tx_id).show();
    });
    
    $('.cancel-edit').click(function(e) {
        e.preventDefault();
        $(this).closest('.tx-edit-form').hide();
    });
});
</script>