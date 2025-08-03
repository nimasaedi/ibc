jQuery(document).ready(function($) {
    // مدیریت تب‌ها
    $('.ibetcoin-tabs ul li a').click(function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href');
        
        $('.ibetcoin-tabs ul li').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $(this).parent().addClass('active');
        $(tab_id).addClass('active');
    });
    
    // تایید سریع تراکنش‌ها
    $(document).on('click', '.quick-action', function(e) {
        e.preventDefault();
        
        var action = $(this).data('action');
        var tx_id = $(this).data('txid');
        var nonce = $(this).data('nonce');
        
        if (!confirm('Are you sure you want to ' + action + ' this transaction?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ibetcoin_process_transaction',
                tx_id: tx_id,
                status: action,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // جستجوی کاربران
    $('#user-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var search_term = $('#user-search').val().trim();
        
        if (search_term.length < 3) {
            alert('Please enter at least 3 characters');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ibetcoin_search_users',
                search: search_term,
                nonce: ibetcoin_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#user-results').html(response.data);
                } else {
                    alert(response.data);
                }
            }
        });
    });
});