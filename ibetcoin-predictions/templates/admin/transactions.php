<?php
defined('ABSPATH') or die('No script kiddies please!');

function ibetcoin_get_blockchain_link($txid) {
    return "https://tronscan.org/#/transaction/{$txid}";
}

// فرض می‌کنیم $transactions قبلاً از دیتابیس گرفته شده، مثلاً:
global $wpdb;
$table = $wpdb->prefix . 'ibetcoin_transactions';

// برای دریافت تراکنش‌ها همراه با اطلاعات کاربر:
$transactions = $wpdb->get_results("
    SELECT t.*, u.user_login, u.user_email
    FROM $table t
    LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
    ORDER BY t.created_at DESC
");
?>

<div class="wrap">
    <h1>لیست تراکنش‌ها</h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>آیدی و کد پیگیری</th>
                <th>کاربر</th>
                <th>TXID</th>
                <th>نوع</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
                <th>آدرس کیف پول</th> <!-- ستون جدید -->
                <th>تاریخ ایجاد</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transactions)) : ?>
                <?php foreach ($transactions as $tx) : ?>
                    <tr>
                        <td>
                            <div><?php echo esc_html($tx->id); ?></div>
                            <div class="label-code"><?php echo !empty($tx->tracking_code) ? esc_html($tx->tracking_code) : '-'; ?></div>
                        </td>
                        <td>
                            <?php echo esc_html($tx->user_login); ?><br />
                            <a href="mailto:<?php echo esc_attr($tx->user_email); ?>"><?php echo esc_html($tx->user_email); ?></a>
                        </td>
                        <td>
                            <?php if (!empty($tx->txid)) : ?>
                                <a href="<?php echo esc_url(ibetcoin_get_blockchain_link($tx->txid)); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($tx->txid); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tx->type === 'deposit') : ?>
                                <span style="color: green; font-weight: bold;">واریز</span>
                            <?php elseif ($tx->type === 'withdraw') : ?>
                                <span style="color: red; font-weight: bold;">برداشت</span>
                            <?php else: ?>
                                <?php echo esc_html(ucfirst($tx->type)); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(number_format(floatval($tx->amount), 2)); ?></td>
                        <td>
                            <?php
                            if ($tx->status === 'pending') {
                                echo '<span style="color: orange; font-weight: bold;">در انتظار</span>';
                            } elseif ($tx->status === 'completed') {
                                echo '<span style="color: green; font-weight: bold;">تکمیل شده</span>';
                            } elseif ($tx->status === 'rejected') {
                                echo '<span style="color: red; font-weight: bold;">رد شده</span>';
                            } else {
                                echo esc_html(ucfirst($tx->status));
                            }
                            ?>

                            <form method="post" style="margin-top:8px;">
                                <?php wp_nonce_field('ibetcoin_update_transaction'); ?>
                                <input type="hidden" name="action" value="update_status" />
                                <input type="hidden" name="tx_id" value="<?php echo esc_attr($tx->id); ?>" />

                                <select name="status" required>
                                    <option value="pending" <?php selected($tx->status, 'pending'); ?>>در انتظار</option>
                                    <option value="completed" <?php selected($tx->status, 'completed'); ?>>تکمیل شده</option>
                                    <option value="rejected" <?php selected($tx->status, 'rejected'); ?>>رد شده</option>
                                </select>
                                <br/>
                                <textarea name="notes" placeholder="دلیل رد (اختیاری)" rows="2"><?php echo esc_textarea($tx->notes); ?></textarea>
                                <br/>
                                <button type="submit" class="button button-small">بروزرسانی</button>
                            </form>
                        </td>
                        <td><?php echo esc_html($tx->wallet_address ?: '-'); ?></td> <!-- ستون کیف پول -->
                        <td><?php echo esc_html($tx->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="8">تراکنشی یافت نشد.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
