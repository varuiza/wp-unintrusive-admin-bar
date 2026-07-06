'use strict'
jQuery(document).ready(function ($) {
    var PREFERS_REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    var ANIMATION_DURATION = PREFERS_REDUCED_MOTION ? 0 : 300
    // Must stay in sync with the "hidden" #uab-btn-show-admin-bar top value in css/style.css
    var SHOW_BTN_HIDDEN_TOP = '-50px'
    var SHOW_BTN_VISIBLE_TOP = '10px'

    // Add the "hide admin bar" icon to the admin bar before proceeding.
    // #wp-admin-bar-root-default is a core WP admin bar group, unlike
    // #wp-admin-bar-wp-logo, which other plugins/themes can remove.
    // The "ab-item" class is what makes WP apply the active admin color
    // scheme to this link, same as any other native admin bar icon; the
    // nested "ab-icon" span reuses core's own icon markup (see admin-bar.php)
    // so this button gets the same sizing rules as every other toolbar icon,
    // including the responsive touch-target scaling below 782px.
    $('<li id="uab-admin-bar-toggle-li">')
        .append(
            $('<a>', {
                href: '#',
                id: 'uab-btn-hide-admin-bar',
                class: 'ab-item',
                title: uabL10n.hideLabel,
                'aria-label': uabL10n.hideLabel,
                'aria-controls': 'wpadminbar',
                'aria-expanded': 'true',
            }).append($('<span>', { class: 'ab-icon', 'aria-hidden': 'true' }))
        )
        .prependTo('#wp-admin-bar-root-default')

    // Announces show/hide state changes for screen reader users, since moving
    // focus is the only other signal they get once a toggle button disappears.
    var announcer = $('<div>', {
        role: 'status',
        'aria-live': 'polite',
        class: 'uab-visually-hidden',
    }).appendTo('body')

    var btnShow = $('#uab-btn-show-admin-bar')
    var btnHide = $('#uab-btn-hide-admin-bar')
    var adminBar = $('#wpadminbar')

    // Show the button
    btnShow
        .animate({ top: SHOW_BTN_VISIBLE_TOP }, ANIMATION_DURATION)
        .delay(1000)
        .queue(function (next) {
            $(this).addClass('uab-opaque')
            next()
        })

    // Show the admin bar
    btnShow.on('click', function (event) {
        event.preventDefault()
        $(this)
            .stop(true, true)
            .animate({ top: SHOW_BTN_HIDDEN_TOP }, ANIMATION_DURATION, function () {
                adminBar.stop(true, true).slideDown(ANIMATION_DURATION, function () {
                    btnHide.trigger('focus')
                    // btnShow stays in the DOM (off-screen) while the bar is
                    // shown, unlike btnHide, which is hidden from AT
                    // automatically because it lives inside #wpadminbar.
                    // Without this it would remain a focusable, invisible
                    // control in the tab order for as long as the bar stays
                    // open. Applied only after moving focus away from it, since
                    // aria-hidden must never land on a currently focused element.
                    btnShow.attr({ tabindex: '-1', 'aria-hidden': 'true' })
                    announcer.text(uabL10n.shownAnnouncement)
                })
            })
    })

    // Hide the admin bar
    btnHide.on('click', function (event) {
        event.preventDefault()
        adminBar.stop(true, true).slideUp(ANIMATION_DURATION, function () {
            btnShow.attr({ tabindex: null, 'aria-hidden': null })
            btnShow.trigger('focus')
            announcer.text(uabL10n.hiddenAnnouncement)
        })
        btnShow.stop(true, true).animate({ top: SHOW_BTN_VISIBLE_TOP }, ANIMATION_DURATION)
    })
})
