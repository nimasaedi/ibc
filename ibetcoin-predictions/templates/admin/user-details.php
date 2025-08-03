<?php
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user = get_userdata($user_id);

if (!$user) {
    wp_die('کاربر مورد نظر یافت نشد');
}

$balance = ibetcoin_get_user_balance($user_id);
$status = get_user_meta($user_id, 'ibetcoin_account_status', true) ?: 'active';
?>

<div class="wrap ibetcoin-user-details">
    <h1>جزئیات کاربر: <?php echo $user->user_login; ?></h1>
    
    <div class="user-info">
        <div class="info-card">
            <h3>اطلاعات پایه</h3>
            <p><strong>نام کاربری:</strong> <?php echo $user->user_login; ?></p>
            <p><strong>ایمیل:</strong> <?php echo $user->user_email; ?></p>
            <p><strong>تاریخ ثبت‌نام:</strong> <?php echo date('Y-m-d H:i', strtotime($user->user_registered)); ?></p>
            <p><strong>وضعیت حساب:</strong> 
                <span class="status-badge <?php echo $status; ?>">
                    <?php echo $status === 'active' ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </p>
        </div>
        
        <div class="info-card">
            <h3>وضعیت مالی</h3>
            <p><strong>موجودی فعلی:</strong> <?php echo number_format($balance, 2); ?> USDT</p>
            
            <form method="post" class="balance-form">
                <?php wp_nonce_field('ibetcoin_update_balance'); ?>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <div class="form-group">
                    <label for="balance-action">عملیات:</label>
                    <select name="balance_action" id="balance-action" required>
                        <option value="add">افزایش موجودی</option>
                        <option value="subtract">کاهش موجودی</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="balance-amount">مبلغ (USDT):</label>
                    <input type="number" name="balance_amount" id="balance-amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="balance-note">توضیحات:</label>
                    <textarea name="balance_note" id="balance-note" rows="2"></textarea>
                </div>
                
                <button type="submit" name="update_balance" class="button button-primary">اعمال تغییر</button>
            </form>
        </div>
    </div>
    
    <div class="user-transactions">
        <h3>تاریخچه تراکنش‌ها</h3>
        
        <?php
        global $wpdb;
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ibetcoin_transactions 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT 50",
            $user_id
        ));
        ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>نوع</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th>توضیحات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx) : ?>
                <tr>
                    <td><?php echo $tx->id; ?></td>
                    <td><?php echo $tx->type === 'deposit' ? 'واریز' : ($tx->type === 'withdraw' ? 'برداشت' : $tx->type); ?></td>
                    <td><?php echo number_format($tx->amount, 2); ?> USDT</td>
                    <td>
                        <span class="status-badge <?php echo $tx->status; ?>">
                            <?php echo $tx->status === 'completed' ? 'تکمیل شده' : ($tx->status === 'pending' ? 'در انتظار' : 'رد شده'); ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
                    <td><?php echo $tx->notes ?: '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// پردازش فرم تغییر موجودی
if (isset($_POST['update_balance']) && check_admin_referer('ibetcoin_update_balance')) {
    $user_id = intval($_POST['user_id']);
    $action = sanitize_text_field($_POST['balance_action']);
    $amount = floatval($_POST['balance_amount']);
    $note = sanitize_textarea_field($_POST['balance_note']);
    
    if ($amount <= 0) {
        echo '<div class="notice notice-error"><p>مبلغ باید بیشتر از صفر باشد</p></div>';
    } else {
        $tx_type = $action === 'add' ? 'admin_add' : 'admin_subtract';
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ibetcoin_transactions',
            array(
                'user_id' => $user_id,
                'type' => $tx_type,
                'amount' => $amount,
                'status' => 'completed',
                'notes' => $note,
                'admin_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
        
        echo '<div class="notice notice-success"><p>موجودی با موفقیت به‌روزرسانی شد</p></div>';
    }
}
?>