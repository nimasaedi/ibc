<div class="ibetcoin-auth-container">
    <h2>ثبت‌نام در iBetCoin</h2>
    
    <form id="ibetcoin-register-form">
        <div class="form-group">
            <label for="reg-username">نام کاربری</label>
            <input type="text" id="reg-username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="reg-email">ایمیل</label>
            <input type="email" id="reg-email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="reg-password">رمز عبور (حداقل 6 کاراکتر)</label>
            <input type="password" id="reg-password" name="password" minlength="6" required>
        </div>
        
        <div class="form-group">
            <label for="reg-confirm-password">تکرار رمز عبور</label>
            <input type="password" id="reg-confirm-password" name="confirm_password" minlength="6" required>
        </div>
        
        <button type="submit" class="btn-submit">ثبت‌نام</button>
        
        <div class="auth-links">
            قبلاً حساب دارید؟ <a href="../login">وارد شوید</a>
        </div>
    </form>
    
    <div id="ibetcoin-register-message"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ibetcoin-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            username: $('#reg-username').val(),
            email: $('#reg-email').val(),
            password: $('#reg-password').val(),
            confirm_password: $('#reg-confirm-password').val(),
            action: 'ibetcoin_register',
            nonce: ibetcoin_ajax.nonce
        };
        
        $.ajax({
            url: ibetcoin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#ibetcoin-register-message').html(
                        '<div class="alert alert-success">' + response.data + '</div>'
                    );
                    window.location.href = '<?php echo home_url('/profile'); ?>';
                } else {
                    $('#ibetcoin-register-message').html(
                        '<div class="alert alert-danger">' + response.data + '</div>'
                    );
                }
            }
        });
    });
});
</script>