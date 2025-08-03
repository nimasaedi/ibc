<div class="ibetcoin-deposit-container">
    <h2>واریز به حساب</h2>
    
    <div class="deposit-info">
        <p>لطفاً مبلغ مورد نظر را به آدرس کیف پول زیر واریز نمایید:</p>
        
        <div class="wallet-address-card">
            <div class="wallet-address">
                <span id="wallet-address"><?php echo esc_html(get_option('ibetcoin_settings')['main_wallet_address']); ?></span>
                <button class="btn-copy" data-clipboard-target="#wallet-address">
                    <i class="dashicons dashicons-clipboard"></i>
                </button>
            </div>
            
            <div class="wallet-qr">
                <?php
                $wallet_address = get_option('ibetcoin_settings')['main_wallet_address'];
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($wallet_address);
                ?>
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code" class="qr-code">
                <p>اسکن کنید</p>
            </div>
        </div>
        
        <div class="deposit-notice">
            <p><i class="dashicons dashicons-warning"></i> حداقل مبلغ واریزی: <?php echo esc_html(get_option('ibetcoin_settings')['min_deposit']); ?> USDT</p>
            <p><i class="dashicons dashicons-clock"></i> واریزها معمولاً طی 15 دقیقه تأیید می‌شوند</p>
        </div>
    </div>
    
    <div class="deposit-form">
        <h3>ثبت درخواست واریز</h3>
        
        <form id="ibetcoin-deposit-form">
            <div class="form-group">
                <label for="deposit-amount">مبلغ (USDT)</label>
                <input type="number" id="deposit-amount" name="amount" 
                       min="<?php echo esc_attr(get_option('ibetcoin_settings')['min_deposit']); ?>" 
                       step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="deposit-txid">شناسه تراکنش (TXID)</label>
                <input type="text" id="deposit-txid" name="txid" required>
                <p class="description">شناسه تراکنش را از تاریخچه تراکنش‌های کیف پول خود کپی کنید</p>
            </div>
            
            <button type="submit" class="btn-submit">ثبت درخواست واریز</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // فعال کردن کپی آدرس کیف پول
    new ClipboardJS('.btn-copy');
    
    $('#ibetcoin-deposit-form').on('submit', function(e) {
        e.preventDefault();
        
        var amount = parseFloat($('#deposit-amount').val());
        var txid = $('#deposit-txid').val().trim();
        var min_deposit = parseFloat('<?php echo get_option('ibetcoin_settings')['min_deposit']; ?>');
        
        if (amount < min_deposit) {
            alert('حداقل مبلغ واریز ' + min_deposit + ' USDT می‌باشد');
            return;
        }
        
        if (!txid) {
            alert('لطفاً شناسه تراکنش را وارد نمایید');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_submit_deposit',
                amount: amount,
                txid: txid,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('درخواست واریز با موفقیت ثبت شد. پس از تأیید، مبلغ به حساب شما واریز می‌شود.');
                    $('#ibetcoin-deposit-form')[0].reset();
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    });
});
</script>