jQuery(document).ready(function($) {
    $('.cloakmaker-toggle').on('change', function() {
        const postId = $(this).data('post-id');
        const enabled = $(this).is(':checked') ? '1' : '0';

        $.post(ajaxurl, {
            action: 'cloakmaker_toggle_enabled',
            post_id: postId,
            enabled: enabled
        });
    });
});
