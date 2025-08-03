

<div class="ibetcoin-prediction-container">
    <h2><?php _e('Bitcoin Price Prediction', 'ibetcoin'); ?></h2>
    
    <div class="price-display">
        <div class="current-price">
            <span class="label"><?php _e('Current Price:', 'ibetcoin'); ?></span>
            <span class="price">--</span> USDT
        </div>
        <div class="price-change">
            <span class="label"><?php _e('Change:', 'ibetcoin'); ?></span>
            <span class="change">--</span>
        </div>
    </div>
    
    <div class="prediction-form">
    <div class="form-group">
        <label for="bet-amount"><?php _e('Bet Amount (USDT):', 'ibetcoin'); ?></label>
        <?php 
        $settings = get_option('ibetcoin_settings');
        $min_bet = isset($settings['min_bet']) ? $settings['min_bet'] : 5;
        $max_bet = isset($settings['max_bet']) ? $settings['max_bet'] : 10000;
        ?>
        <input type="number" id="bet-amount" 
               min="<?php echo esc_attr($min_bet); ?>" 
               max="<?php echo esc_attr($max_bet); ?>" 
               step="0.01"
               placeholder="<?php echo esc_attr($min_bet); ?>">
        <div class="hint">
            <?php printf(__('Min: %s, Max: %s', 'ibetcoin'), $min_bet, $max_bet); ?>
        </div>
    </div>
        
        <div class="odds-display">
            <span class="label"><?php _e('Your Odds:', 'ibetcoin'); ?></span>
            <span class="odds">--</span>
        </div>
        
        <div class="prediction-buttons">
            <button class="btn-up" data-direction="up"><?php _e('UP', 'ibetcoin'); ?></button>
            <button class="btn-down" data-direction="down"><?php _e('DOWN', 'ibetcoin'); ?></button>
        </div>
    </div>
    
    <div class="prediction-timer hidden">
        <div class="timer-display">05:00</div>
        <div class="timer-message"><?php _e('Prediction in progress...', 'ibetcoin'); ?></div>
    </div>
    
    <div class="prediction-result hidden">
        <div class="result-message"></div>
        <div class="result-details"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // بارگذاری قیمت اولیه
    ibetcoinUpdatePrice();
    
    // به‌روزرسانی قیمت هر 10 ثانیه
    setInterval(ibetcoinUpdatePrice, 10000);
    
    // محاسبه ضرایب
    function ibetcoinUpdateOdds() {
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_get_odds',
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.odds-display .odds').text(response.data.odds);
                }
            }
        });
    }
    
    // به‌روزرسانی قیمت
    function ibetcoinUpdatePrice() {
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_get_price',
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.current-price .price').text(response.data.price);
                }
            }
        });
    }
    
    // ثبت پیش‌بینی
    $('.prediction-buttons button').on('click', function() {
        var direction = $(this).data('direction');
        var amount = parseFloat($('#bet-amount').val());
        
        if (!amount || amount < <?php echo IBETCOIN_MIN_BET; ?> || amount > <?php echo IBETCOIN_MAX_BET; ?>) {
            alert('<?php printf(__('Please enter a valid amount between %s and %s', 'ibetcoin'), IBETCOIN_MIN_BET, IBETCOIN_MAX_BET); ?>');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_make_prediction',
                direction: direction,
                amount: amount,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.prediction-form').addClass('hidden');
                    $('.prediction-timer').removeClass('hidden');
                    
                    // شروع تایمر
                    var endTime = response.data.end_time;
                    var timer = setInterval(function() {
                        var now = Math.floor(Date.now() / 1000);
                        var remaining = endTime - now;
                        
                        if (remaining <= 0) {
                            clearInterval(timer);
                            $('.prediction-timer').addClass('hidden');
                            $('.prediction-result').removeClass('hidden');
                            $('.result-message').text('<?php _e('Prediction completed!', 'ibetcoin'); ?>');
                            // در اینجا می‌توانید نتیجه را از سرور دریافت کنید
                        } else {
                            var minutes = Math.floor(remaining / 60);
                            var seconds = remaining % 60;
                            $('.timer-display').text(
                                (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                                (seconds < 10 ? '0' + seconds : seconds)
                            );
                        }
                    }, 1000);
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
</script>