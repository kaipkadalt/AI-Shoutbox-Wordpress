// Failas: js/admin.js
jQuery(document).on('click', '.clear-shoutbox-chat', function(e) {
    e.preventDefault();
    
    // Naudojame verčiamą tekstą iš PHP
    if (!confirm(shoutbox_admin.text.confirm_clear)) {
        return;
    }

    const button = jQuery(this);
    const originalText = button.text();
    button.text(shoutbox_admin.text.clearing).prop('disabled', true);

    jQuery.post(shoutbox_admin.ajax_url, {
        action: 'clear_shoutbox_chat',
        _ajax_nonce: shoutbox_admin.nonce
    }, function(response) {
        if (response.success) {
            alert(shoutbox_admin.text.clear_success);
        } else {
            alert(shoutbox_admin.text.clear_error + ' ' + (response.data.message || shoutbox_admin.text.unknown_error));
        }
        button.text(originalText).prop('disabled', false);
    });
});