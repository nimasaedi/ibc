<div class="ibetcoin-transactions-history">
    <h3><?php _e('Transactions History', 'ibetcoin'); ?></h3>
    
    <?php if ($transactions) : ?>
    <table class="ibetcoin-table">
        <thead>
            <tr>
                <th><?php _e('ID', 'ibetcoin'); ?></th>
                <th><?php _e('Type', 'ibetcoin'); ?></th>
                <th><?php _e('Amount', 'ibetcoin'); ?></th>
                <th><?php _e('Status', 'ibetcoin'); ?></th>
                <th><?php _e('Date', 'ibetcoin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx) : ?>
            <tr>
                <td><?php echo $tx->id; ?></td>
                <td><?php echo ucfirst($tx->type); ?></td>
                <td><?php echo number_format($tx->amount, 2); ?> USDT</td>
                <td>
                    <span class="status-badge <?php echo $tx->status; ?>">
                        <?php echo ucfirst($tx->status); ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <p><?php _e('No transactions found', 'ibetcoin'); ?></p>
    <?php endif; ?>
</div>