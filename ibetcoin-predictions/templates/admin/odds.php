<div class="wrap ibetcoin-odds">
    <h1><?php _e('Odds Management', 'ibetcoin'); ?></h1>
    
    <div class="ibetcoin-tabs">
        <ul>
            <li class="active"><a href="#current-odds"><?php _e('Current Odds', 'ibetcoin'); ?></a></li>
            <li><a href="#add-odds"><?php _e('Add/Edit Odds', 'ibetcoin'); ?></a></li>
        </ul>
        
        <div id="current-odds" class="tab-content active">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'ibetcoin'); ?></th>
                        <th><?php _e('User', 'ibetcoin'); ?></th>
                        <th><?php _e('Base Odds', 'ibetcoin'); ?></th>
                        <th><?php _e('Increase Rate', 'ibetcoin'); ?></th>
                        <th><?php _e('Streak Count', 'ibetcoin'); ?></th>
                        <th><?php _e('Is Default', 'ibetcoin'); ?></th>
                        <th><?php _e('Actions', 'ibetcoin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($odds_list as $odds) : ?>
                    <tr>
                        <td><?php echo $odds->id; ?></td>
                        <td>
                            <?php if ($odds->user_id) : ?>
                                <?php echo $odds->user_login ? $odds->user_login : 'User #'.$odds->user_id; ?>
                            <?php else : ?>
                                <?php _e('Default', 'ibetcoin'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $odds->base_odds; ?></td>
                        <td><?php echo $odds->increase_rate; ?>%</td>
                        <td><?php echo $odds->streak_count; ?></td>
                        <td><?php echo $odds->is_default ? __('Yes', 'ibetcoin') : __('No', 'ibetcoin'); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=ibetcoin-odds&edit='.$odds->id); ?>" class="button">
                                <?php _e('Edit', 'ibetcoin'); ?>
                            </a>
                            <?php if (!$odds->is_default) : ?>
                            <a href="<?php echo admin_url('admin.php?page=ibetcoin-odds&delete='.$odds->id); ?>" 
                               class="button button-danger" 
                               onclick="return confirm('<?php _e('Are you sure you want to delete these odds?', 'ibetcoin'); ?>')">
                                <?php _e('Delete', 'ibetcoin'); ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-odds" class="tab-content">
            <form method="post">
                <?php 
                $editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
                $current_odds = $editing ? $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ibetcoin_odds WHERE id = %d", 
                    $editing
                )) : null;
                
                wp_nonce_field('ibetcoin_update_odds');
                ?>
                
                <input type="hidden" name="odds_id" value="<?php echo $editing; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php _e('User', 'ibetcoin'); ?></label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id">
                                <option value=""><?php _e('Default Odds', 'ibetcoin'); ?></option>
                                <?php foreach (get_users() as $user) : ?>
                                <option value="<?php echo $user->ID; ?>" 
                                    <?php selected($current_odds && $current_odds->user_id == $user->ID); ?>>
                                    <?php echo $user->user_login; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select user for custom odds or leave empty for default odds', 'ibetcoin'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_odds"><?php _e('Base Odds', 'ibetcoin'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="base_odds" id="base_odds" 
                                   step="0.01" min="1.0" max="10.0" required
                                   value="<?php echo $current_odds ? $current_odds->base_odds : 1.5; ?>">
                            <p class="description">
                                <?php _e('The starting odds multiplier (e.g. 1.5 means 1.5x payout)', 'ibetcoin'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="increase_rate"><?php _e('Increase Rate', 'ibetcoin'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="increase_rate" id="increase_rate" 
                                   step="0.01" min="0" max="10" required
                                   value="<?php echo $current_odds ? $current_odds->increase_rate : 0.5; ?>">%
                            <p class="description">
                                <?php _e('Percentage increase for each consecutive win (e.g. 0.5% per win)', 'ibetcoin'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="is_default"><?php _e('Default Odds', 'ibetcoin'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="is_default" id="is_default" value="1"
                                <?php checked($current_odds && $current_odds->is_default); ?>>
                            <label for="is_default">
                                <?php _e('Set as default odds for all users', 'ibetcoin'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Only one set of odds can be default. Checking this will override current default.', 'ibetcoin'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="submit_odds" class="button button-primary">
                        <?php _e('Save Odds', 'ibetcoin'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.ibetcoin-tabs ul li a').click(function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href');
        
        $('.ibetcoin-tabs ul li').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $(this).parent().addClass('active');
        $(tab_id).addClass('active');
    });
    
    // اگر در حال ویرایش هستیم، تب ویرایش را نشان بده
    <?php if ($editing) : ?>
    $('.ibetcoin-tabs ul li').removeClass('active');
    $('.tab-content').removeClass('active');
    
    $('.ibetcoin-tabs ul li:last').addClass('active');
    $('#add-odds').addClass('active');
    <?php endif; ?>
});
</script>