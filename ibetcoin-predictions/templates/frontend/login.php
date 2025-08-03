<div class="ibetcoin-auth-container">
    <h2>ورود به iBetCoin</h2>
    
    <form id="ibetcoin-login-form">
        <div class="form-group">
            <label for="login-username">نام کاربری یا ایمیل</label>
            <input type="text" id="login-username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="login-password">رمز عبور</label>
            <input type="password" id="login-password" name="password" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">ورود</button>
            <label class="remember-me">
                <input type="checkbox" name="remember" checked> مرا به خاطر بسپار
            </label>
        </div>
        
        <div class="auth-links">
            حساب ندارید؟ <a href="./register">ثبت‌نام کنید</a>
            <br>
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">رمز عبور را فراموش کرده‌ام</a>
        </div>
    </form>
    
    <div id="ibetcoin-login-message"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ibetcoin-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            username: $('#login-username').val(),
            password: $('#login-password').val(),
            remember: $('input[name="remember"]').is(':checked'),
            action: 'ibetcoin_login',
            nonce: ibetcoin_ajax.nonce
        };
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo home_url('/profile'); ?>';
                } else {
                    $('#ibetcoin-login-message').html(
                        '<div class="alert alert-danger">' + response.data + '</div>'
                    );
                }
            }
        });
    });
});
</script>