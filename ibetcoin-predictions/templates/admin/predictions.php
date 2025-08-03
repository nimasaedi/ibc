<div class="wrap ibetcoin-predictions">
    <h1 class="wp-heading-inline"><?php _e('Predictions Management', 'ibetcoin'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="ibetcoin-predictions">
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'ibetcoin'); ?></option>
                    <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] == 'pending'); ?>>
                        <?php _e('Pending', 'ibetcoin'); ?>
                    </option>
                    <option value="win" <?php selected(isset($_GET['status']) && $_GET['status'] == 'win'); ?>>
                        <?php _e('Win', 'ibetcoin'); ?>
                    </option>
                    <option value="lose" <?php selected(isset($_GET['status']) && $_GET['status'] == 'lose'); ?>>
                        <?php _e('Lose', 'ibetcoin'); ?>
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
                <th><?php _e('Direction', 'ibetcoin'); ?></th>
                <th><?php _e('Amount', 'ibetcoin'); ?></th>
                <th><?php _e('Start Price', 'ibetcoin'); ?></th>
                <th><?php _e('End Price', 'ibetcoin'); ?></th>
                <th><?php _e('Odds', 'ibetcoin'); ?></th>
                <th><?php _e('Profit', 'ibetcoin'); ?></th>
                <th><?php _e('Status', 'ibetcoin'); ?></th>
                <th><?php _e('Date', 'ibetcoin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($predictions as $pred) : ?>
            <tr>
                <td><?php echo $pred->id; ?></td>
                <td><?php echo $pred->user_login ? $pred->user_login : 'User #'.$pred->user_id; ?></td>
                <td><?php echo strtoupper($pred->direction); ?></td>
                <td><?php echo number_format($pred->amount, 2); ?> USDT</td>
                <td><?php echo number_format($pred->start_price, 2); ?> USD</td>
                <td><?php echo $pred->end_price ? number_format($pred->end_price, 2) : '-'; ?> USD</td>
                <td><?php echo $pred->odds; ?>x</td>
                <td>
                    <?php if ($pred->status == 'win') : ?>
                        +<?php echo number_format($pred->profit, 2); ?> USDT
                    <?php elseif ($pred->status == 'lose') : ?>
                        -<?php echo number_format($pred->amount, 2); ?> USDT
                    <?php else : ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status-badge <?php echo $pred->status; ?>">
                        <?php echo ucfirst($pred->status); ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($pred->created_at)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>