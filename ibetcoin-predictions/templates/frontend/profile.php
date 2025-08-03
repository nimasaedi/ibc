<div class="ibetcoin-profile-container">
    <h2><?php _e('My Profile', 'ibetcoin'); ?></h2>
    
    <div class="profile-sections">
        <div class="profile-section personal-info">
            <h3><?php _e('Personal Information', 'ibetcoin'); ?></h3>
            
            <form id="ibetcoin-profile-form">
                <div class="form-group">
                    <label for="profile-username"><?php _e('Username', 'ibetcoin'); ?></label>
                    <input type="text" id="profile-username" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="profile-email"><?php _e('Email', 'ibetcoin'); ?></label>
                    <input type="email" id="profile-email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="profile-display-name"><?php _e('Display Name', 'ibetcoin'); ?></label>
                    <input type="text" id="profile-display-name" name="display_name" 
                           value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                </div>
                
                <button type="submit" class="btn-save">
                    <?php _e('Save Changes', 'ibetcoin'); ?>
                </button>
            </form>
        </div>
        
        <div class="profile-section change-password">
            <h3><?php _e('Change Password', 'ibetcoin'); ?></h3>
            
            <form id="ibetcoin-password-form">
                <div class="form-group">
                    <label for="current-password"><?php _e('Current Password', 'ibetcoin'); ?></label>
                    <input type="password" id="current-password" required>
                </div>
                
                <div class="form-group">
                    <label for="new-password"><?php _e('New Password', 'ibetcoin'); ?></label>
                    <input type="password" id="new-password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password"><?php _e('Confirm Password', 'ibetcoin'); ?></label>
                    <input type="password" id="confirm-password" required>
                </div>
                
                <button type="submit" class="btn-change-password">
                    <?php _e('Change Password', 'ibetcoin'); ?>
                </button>
            </form>
        </div>
        
        <div class="profile-section wallet-info">
            <h3><?php _e('Wallet Information', 'ibetcoin'); ?></h3>
            
            <?php
            global $wpdb;
            $wallet = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ibetcoin_wallets WHERE user_id = %d",
                get_current_user_id()
            ));
            ?>
            
            <form id="ibetcoin-wallet-form">
                <div class="form-group">
                    <label for="wallet-address"><?php _e('Your USDT (TRC20) Wallet Address', 'ibetcoin'); ?></label>
                    <input type="text" id="wallet-address" name="wallet_address" 
                           value="<?php echo $wallet ? esc_attr($wallet->wallet_address) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="wallet-status"><?php _e('Status', 'ibetcoin'); ?></label>
                    <input type="text" id="wallet-status" 
                           value="<?php echo $wallet ? ucfirst($wallet->status) : __('Not set', 'ibetcoin'); ?>" disabled>
                </div>
                
                <button type="submit" class="btn-save-wallet">
                    <?php echo $wallet ? __('Update Wallet', 'ibetcoin') : __('Register Wallet', 'ibetcoin'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // ذخیره اطلاعات پروفایل
    $('#ibetcoin-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var display_name = $('#profile-display-name').val().trim();
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_update_profile',
                display_name: display_name,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Profile updated successfully', 'ibetcoin'); ?>');
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // تغییر رمز عبور
    $('#ibetcoin-password-form').on('submit', function(e) {
        e.preventDefault();
        
        var current_pass = $('#current-password').val();
        var new_pass = $('#new-password').val();
        var confirm_pass = $('#confirm-password').val();
        
        if (new_pass !== confirm_pass) {
            alert('<?php _e('New passwords do not match', 'ibetcoin'); ?>');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_change_password',
                current_pass: current_pass,
                new_pass: new_pass,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Password changed successfully', 'ibetcoin'); ?>');
                    $('#ibetcoin-password-form')[0].reset();
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // ثبت/به‌روزرسانی کیف پول
    $('#ibetcoin-wallet-form').on('submit', function(e) {
        e.preventDefault();
        
        var wallet_address = $('#wallet-address').val().trim();
        
        if (!wallet_address) {
            alert('<?php _e('Please enter your wallet address', 'ibetcoin'); ?>');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_update_wallet',
                wallet_address: wallet_address,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Wallet information updated', 'ibetcoin'); ?>');
                    $('#wallet-status').val('Pending Verification');
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
</script>