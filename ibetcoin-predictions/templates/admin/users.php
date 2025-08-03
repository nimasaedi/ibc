<div class="wrap ibetcoin-users">
    <h1 class="wp-heading-inline"><?php _e('Users Management', 'ibetcoin'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="ibetcoin-users">
                <?php wp_nonce_field('ibetcoin_search_users', 'ibetcoin_nonce'); ?>
                <input type="text" name="s" placeholder="<?php _e('Search users...', 'ibetcoin'); ?>" 
                       value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'ibetcoin'); ?>">
            </form>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'ibetcoin'); ?></th>
                <th><?php _e('Username', 'ibetcoin'); ?></th>
                <th><?php _e('Email', 'ibetcoin'); ?></th>
                <th><?php _e('Balance', 'ibetcoin'); ?></th>
                <th><?php _e('Registered', 'ibetcoin'); ?></th>
                <th><?php _e('Status', 'ibetcoin'); ?></th>
                <th><?php _e('Actions', 'ibetcoin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : 
                $balance = ibetcoin_get_user_balance($user->ID);
                $status = get_user_meta($user->ID, 'ibetcoin_account_status', true) ?: 'active';
            ?>
            <tr>
                <td><?php echo $user->ID; ?></td>
                <td><?php echo $user->user_login; ?></td>
                <td><?php echo $user->user_email; ?></td>
                <td><?php echo number_format($balance, 2); ?> USDT</td>
                <td><?php echo date('Y-m-d', strtotime($user->user_registered)); ?></td>
                <td>
                    <span class="status-badge <?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="<?php echo admin_url('admin.php?page=ibetcoin-users&action=activate&user_id='.$user->ID); ?>">
                            <?php _e('Activate', 'ibetcoin'); ?>
                        </a> | 
                        <a href="<?php echo admin_url('admin.php?page=ibetcoin-users&action=deactivate&user_id='.$user->ID); ?>">
                            <?php _e('Deactivate', 'ibetcoin'); ?>
                        </a> | 
                        <a href="<?php echo admin_url('admin.php?page=ibetcoin-users&action=delete&user_id='.$user->ID); ?>" 
                           onclick="return confirm('<?php _e('Are you sure you want to delete this user?', 'ibetcoin'); ?>')">
                            <?php _e('Delete', 'ibetcoin'); ?>
                        </a> | 
                        <a href="<?php echo admin_url('admin.php?page=ibetcoin-transactions&user_id='.$user->ID); ?>">
                            <?php _e('Transactions', 'ibetcoin'); ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>