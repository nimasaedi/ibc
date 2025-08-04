<!-- ClipboardJS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>

<div class="ibetcoin-deposit-container">
    <h2>Deposit to Account</h2>

    <div class="deposit-info">
        <p>Please send the desired amount to the wallet address below:</p>



        <div class="wallet-address-card">
            <div class="wallet-address">
                <span id="wallet-address"><?php echo esc_html(get_option('ibetcoin_settings')['main_wallet_address']); ?></span>
                <button class="btn-copy" data-clipboard-text="<?php echo esc_attr(get_option('ibetcoin_settings')['main_wallet_address']); ?>">
                    <i class="dashicons dashicons-clipboard"></i>
                </button>
            </div>

            <div class="wallet-qr">
                <?php
                $wallet_address = get_option('ibetcoin_settings')['main_wallet_address'];
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($wallet_address);
                ?>
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code" class="qr-code">
                <!-- <div><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/usdt-trc20.png'; ?>" alt="USDT TRC20" class="usdt-logo" style="width:80px"></div> -->
                <p>Scan here</p>
            </div>
        </div>

        <div class="deposit-notice">
            <span><i class="dashicons dashicons-warning"></i> Minimum deposit amount: <?php echo esc_html(get_option('ibetcoin_settings')['min_deposit']); ?> USDT</span>
            <span><i class="dashicons dashicons-warning"></i> <strong>Attention:</strong> Please only send <strong>USDT on TRC20 network</strong> to this wallet.</span>
            <span><i class="dashicons dashicons-warning"></i> If you send via ERC20, BEP20 or any other network, your funds <strong>will not be recoverable</strong>.</span>
            <span><i class="dashicons dashicons-clock"></i> Deposits are usually confirmed within 15 minutes</span>
        </div>
    </div>

    <div class="deposit-form">
        <h3>Submit Deposit Request</h3>

        <form id="ibetcoin-deposit-form">
            <div class="form-group">
                <label for="deposit-amount">Amount (USDT)</label>
                <input type="number" id="deposit-amount" name="amount"
                       min="<?php echo esc_attr(get_option('ibetcoin_settings')['min_deposit']); ?>"
                       step="0.01" required>
            </div>

            <div class="form-group">
                <label for="deposit-txid">Transaction ID (TXID)</label>
                <input type="text" id="deposit-txid" name="txid" required>
                <p class="description">Copy the transaction ID from your wallet transaction history</p>
            </div>

            <button type="submit" class="btn-submit">Submit Deposit Request</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Enable ClipboardJS
    new ClipboardJS('.btn-copy').on('success', function(e) {
        alert('Wallet address copied to clipboard!');
        e.clearSelection();
    });

    $('#ibetcoin-deposit-form').on('submit', function(e) {
        e.preventDefault();

        var amount = parseFloat($('#deposit-amount').val());
        var txid = $('#deposit-txid').val().trim();
        var min_deposit = parseFloat('<?php echo get_option('ibetcoin_settings')['min_deposit']; ?>');

        if (amount < min_deposit) {
            alert('Minimum deposit amount is ' + min_deposit + ' USDT');
            return;
        }

        if (!txid) {
            alert('Please enter the transaction ID');
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
                    alert('Deposit request submitted successfully. The amount will be credited to your account after confirmation.');
                    $('#ibetcoin-deposit-form')[0].reset();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Server communication error');
            }
        });
    });
});
</script>












<style>
    .ibetcoin-deposit-container {
    max-width: 700px;
    margin: 40px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px #d9dbdf;
    color: #f1f5f9;
    font-family: 'Vazirmatn', Tahoma, sans-serif;
    padding: 30px 28px;
}

.ibetcoin-deposit-container h2,
.ibetcoin-deposit-container h3 {
  color: #38bdf8; /* آبی روشن برای تیتر */
  margin-bottom: 24px;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-align: center;
  text-shadow: 0 0 5px #38bdf8aa;
}

.deposit-info p {
  font-size: 1rem;
  line-height: 1.5;
  margin-bottom: 20px;
  text-align: center;
  color: #222;
}

.wallet-address-card {
  background: #eef2f9; /* پس‌زمینه کارت */
  border-radius: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 20px;
  margin-bottom: 22px;
  gap: 15px;
  flex-wrap: wrap;
}

.wallet-address {
  flex: 1 1 auto;
  background: #0f172a;
  border-radius: 8px;
  padding: 12px 16px;
  font-family: monospace;
  font-size: 1.1rem;
  color: #a5b4fc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  user-select: all;
  box-shadow: inset 0 0 6px #3b82f6aa;
}

.btn-copy {
  background-color: #3b82f6;
  border: none;
  color: white;
  cursor: pointer;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.3s ease;
}

.btn-copy:hover {
  background-color: #60a5fa;
}

.wallet-qr {
  text-align: center;
  flex-shrink: 0;
}

.qr-code {
  width: 150px;
  height: 150px;
  border-radius: 12px;
  box-shadow: 0 0 10px #38bdf8aa;
  margin-bottom: 8px;
  background: white;
}

.wallet-qr p {
  color: #94a3b8;
  font-size: 0.9rem;
  user-select: none;
}

.deposit-notice {
    background: #eef2f9;
    border-left: 7px solid #facc15;
    padding: 12px 20px;
    border-radius: 5px;
    margin-bottom: 30px;
    color: #4e442b;
    font-weight: 400;
    display: flex
;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
    text-align: left;
}

.deposit-notice i.dashicons {
  margin-left: 6px;
  font-size: 1.2rem;
  vertical-align: middle;
}

.deposit-form h3 {
  font-size: 1.3rem;
  margin-bottom: 20px;
  color: #60a5fa;
  text-align: center;
}

form#ibetcoin-deposit-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 700;
  color: #333;
}

.form-group input[type="number"],
.form-group input[type="text"] {
      width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 2px solid #d3d9e2;
    background: #ffffff;
    color: #555;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    font-family: 'Vazirmatn', monospace;
}

.form-group input[type="number"]:focus,
.form-group input[type="text"]:focus {
  outline: none;
    border-color: #97acb5;
    box-shadow: 0 0 6px #abc3cdaa;
}

.form-group .description {
  font-size: 0.8rem;
  color: #94a3b8;
  margin-top: 4px;
  user-select: none;
}

.btn-submit {
  background: linear-gradient(90deg, #2563eb, #3b82f6);
  color: white;
  border: none;
  border-radius: 12px;
  padding: 14px 0;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  text-align: center;
  box-shadow: 0 8px 15px rgba(59, 130, 246, 0.4);
  transition: all 0.3s ease;
}

.btn-submit:hover {
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  box-shadow: 0 12px 24px rgba(59, 130, 246, 0.6);
  transform: translateY(-3px);
}

@media (max-width: 500px) {
  .wallet-address-card {
    flex-direction: column;
    align-items: center;
  }
  .wallet-address {
    width: 100%;
    margin-bottom: 12px;
  }
  .wallet-qr {
    width: 100%;
  }
}

</style>