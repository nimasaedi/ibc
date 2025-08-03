
// در فایل assets/js/frontend.js
jQuery(document).ready(function($) {
    // دریافت تنظیمات از طریق AJAX
    function getIbetcoinSettings() {
        return $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_get_settings',
                nonce: ibetcoin_ajax.nonce
            },
            async: false
        }).responseJSON;
    }

    // استفاده از تنظیمات
    var settings = getIbetcoinSettings();
    console.log('Loaded settings:', settings);
});



jQuery(document).ready(function($) {
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
    
	
	
	
	
	
// ثبت پیش‌بینی - نسخه اصلاح شده
$('.prediction-buttons button').on('click', function() {
    var direction = $(this).data('direction');
    var amount = parseFloat($('#bet-amount').val());
    
    // دریافت تنظیمات به صورت همزمان
    $.ajax({
        url: ibetcoin_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'ibetcoin_get_settings',
            nonce: ibetcoin_ajax.nonce
        },
        success: function(settingsResponse) {
            if (settingsResponse.success) {
                var min_bet = parseFloat(settingsResponse.data.min_bet);
                var max_bet = parseFloat(settingsResponse.data.max_bet);
                
                // اعتبارسنجی با مقادیر جدید
                if (!amount || amount < min_bet || amount > max_bet) {
                    alert('لطفاً مبلغی بین ' + min_bet + ' تا ' + max_bet + ' USDT وارد کنید');
                    return;
                }
                
                // ارسال درخواست پیش‌بینی
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
                                    $('.result-message').text('پیش‌بینی تکمیل شد!');
                                    
                                    // دریافت نتیجه
                                    $.ajax({
                                        url: ibetcoin_ajax.ajax_url,
                                        type: 'POST',
                                        data: {
                                            action: 'ibetcoin_get_prediction_result',
                                            prediction_id: response.data.prediction_id,
                                            nonce: ibetcoin_ajax.nonce
                                        },
                                        success: function(result) {
                                            if (result.success) {
                                                if (result.data.status == 'win') {
                                                    $('.result-message').text('شما برنده شدید!');
                                                    $('.result-details').html(
                                                        'سود: <strong>+' + result.data.profit + ' USDT</strong>'
                                                    );
                                                } else {
                                                    $('.result-message').text('شما باختید');
                                                    $('.result-details').html(
                                                        'ضرر: <strong>-' + result.data.amount + ' USDT</strong>'
                                                    );
                                                }
                                            }
                                        }
                                    });
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
            } else {
                alert('خطا در دریافت تنظیمات سیستم');
            }
        }
    });
});
    
    // ثبت واریز
    $('#ibetcoin-deposit-form').on('submit', function(e) {
        e.preventDefault();
        
        var amount = parseFloat($('#deposit-amount').val());
        var txid = $('#deposit-txid').val().trim();
        
        if (amount < ibetcoin_vars.min_deposit) {
            alert('Minimum deposit amount is ' + ibetcoin_vars.min_deposit + ' USDT');
            return;
        }
        
        if (!txid) {
            alert('Please enter transaction ID');
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
                    alert(response.data);
                    $('#ibetcoin-deposit-form')[0].reset();
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // ثبت برداشت
    $('#ibetcoin-withdraw-form').on('submit', function(e) {
        e.preventDefault();
        
        var amount = parseFloat($('#withdraw-amount').val());
        var wallet_address = $('#withdraw-address').val().trim();
        
        if (amount < ibetcoin_vars.min_withdrawal) {
            alert('Minimum withdrawal amount is ' + ibetcoin_vars.min_withdrawal + ' USDT');
            return;
        }
        
        if (!wallet_address) {
            alert('Please enter your wallet address');
            return;
        }
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_request_withdrawal',
                amount: amount,
                wallet_address: wallet_address,
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#ibetcoin-withdraw-form')[0].reset();
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // به‌روزرسانی موجودی هر 30 ثانیه
    setInterval(function() {
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ibetcoin_get_balance',
                nonce: ibetcoin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.wallet-balance .amount').text(response.data.balance + ' ' + response.data.currency);
                }
            }
        });
    }, 30000);
});