jQuery(document).ready(function ($) {
    // Add the "hide admin bar" icon to the admin bar before proceeding
    $('#wp-admin-bar-wp-logo').before(
        $('<li id="uab-admin-bar-toggle-li">').html(
            '<a href="#" id="uab-btn-hide-admin-bar" title="' + uabL10n.hideLabel + '" aria-label="' + uabL10n.hideLabel + '"></a>'
        )
    )

    var btnShow = $('a#uab-btn-show-admin-bar')
    var btnHide = $('a#uab-btn-hide-admin-bar')
    var adminBar = $('#wpadminbar')

    // Show the button
    btnShow
        .animate({ top: '10px' }, 300)
        .delay(1000)
        .queue(function (next) {
            $(this).addClass('opaque')
            next()
        })

    // Show the admin bar
    btnShow.on('click', function () {
        $(this).animate({ top: '-50px' }, 300, function () {
            adminBar.stop(true, true).slideDown(300)
        })
        return false
    })

    // Hide the admin bar
    btnHide.on('click', function () {
        adminBar.stop(true, true).slideUp(300)
        btnShow.animate({ top: '10px' }, 300)
        return false
    })
})
