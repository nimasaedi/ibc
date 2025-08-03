<div class="ibetcoin-predictions-history">
    <h3><?php _e('Predictions History', 'ibetcoin'); ?></h3>
    
    <?php if ($predictions) : ?>
    <table class="ibetcoin-table">
        <thead>
            <tr>
                <th><?php _e('ID', 'ibetcoin'); ?></th>
                <th><?php _e('Direction', 'ibetcoin'); ?></th>
                <th><?php _e('Amount', 'ibetcoin'); ?></th>
                <th><?php _e('Start Price', 'ibetcoin'); ?></th>
                <th><?php _e('End Price', 'ibetcoin'); ?></th>
                <th><?php _e('Result', 'ibetcoin'); ?></th>
                <th><?php _e('Date', 'ibetcoin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($predictions as $pred) : ?>
            <tr>
                <td><?php echo $pred->id; ?></td>
                <td><?php echo strtoupper($pred->direction); ?></td>
                <td><?php echo number_format($pred->amount, 2); ?> USDT</td>
                <td><?php echo number_format($pred->start_price, 2); ?> USD</td>
                <td><?php echo $pred->end_price ? number_format($pred->end_price, 2) : '-'; ?> USD</td>
                <td>
                    <?php if ($pred->status == 'win') : ?>
                        <span class="result-win">+<?php echo number_format($pred->profit, 2); ?> USDT</span>
                    <?php elseif ($pred->status == 'lose') : ?>
                        <span class="result-lose">-<?php echo number_format($pred->amount, 2); ?> USDT</span>
                    <?php else : ?>
                        <span class="result-pending"><?php _e('Pending', 'ibetcoin'); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($pred->created_at)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <p><?php _e('No predictions found', 'ibetcoin'); ?></p>
    <?php endif; ?>
</div>