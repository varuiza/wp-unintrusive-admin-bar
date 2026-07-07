'use strict'

function uabReady(callback) {
    if (document.readyState !== 'loading') {
        callback()
    } else {
        document.addEventListener('DOMContentLoaded', callback)
    }
}

/** Converts a CSS <time> value (e.g. "300ms" or "0s") to milliseconds. */
function uabParseCssDuration(value) {
    value = value.trim()
    var milliseconds = parseFloat(value)
    return value.endsWith('ms') ? milliseconds : milliseconds * 1000
}

uabReady(function () {
    var adminBar = document.getElementById('wpadminbar')
    var btnShow = document.getElementById('uab-btn-show-admin-bar')
    // Rendered server-side by uab_add_admin_bar_hide_toggle() (PHP) as a
    // native Toolbar API node. The API only gives the wrapping <li> a
    // predictable id ("wp-admin-bar-{$node_id}"); its <a> child is plain.
    var btnHide = document.querySelector('#wp-admin-bar-uab-hide-admin-bar > a')

    if (!adminBar || !btnShow || !btnHide) {
        return
    }

    // Reads the duration from CSS's --uab-animation-duration (including
    // its prefers-reduced-motion override) so the two never drift apart.
    var ANIMATION_DURATION = uabParseCssDuration(
        getComputedStyle(document.documentElement).getPropertyValue('--uab-animation-duration')
    )

    btnHide.setAttribute('aria-controls', 'wpadminbar')
    btnHide.setAttribute('aria-expanded', 'true')

    var cancelPendingTransition = null

    /**
     * Runs `callback` once `el`'s `property` transition finishes, or
     * immediately when animations are switched off (a 0-duration
     * transition never fires `transitionend`). Cancels whatever completion
     * callback was still waiting on a previous, interrupted transition, so
     * rapid clicks can't fire two completions for the same element.
     */
    function afterTransition(el, property, callback) {
        if (cancelPendingTransition) {
            cancelPendingTransition()
            cancelPendingTransition = null
        }

        if (ANIMATION_DURATION <= 0) {
            callback()
            return
        }

        var onEnd = function (event) {
            if (event.target !== el || event.propertyName !== property) {
                return
            }
            el.removeEventListener('transitionend', onEnd)
            cancelPendingTransition = null
            callback()
        }
        el.addEventListener('transitionend', onEnd)
        cancelPendingTransition = function () {
            el.removeEventListener('transitionend', onEnd)
        }
    }

    /**
     * Reveals the admin bar, mirroring jQuery's slideDown(): grows it to
     * its natural height and clips overflow only while animating, so
     * dropdown submenus aren't cut off once fully open.
     */
    function showAdminBar(done) {
        adminBar.style.overflow = 'hidden'
        adminBar.style.display = 'block'
        // Forces a reflow so the collapsed state above is committed before
        // the class below starts the transition to the open height.
        void adminBar.offsetHeight
        adminBar.classList.add('uab-shown')
        afterTransition(adminBar, 'max-height', function () {
            adminBar.style.overflow = ''
            done()
        })
    }

    /**
     * Collapses the admin bar, mirroring jQuery's slideUp().
     */
    function hideAdminBar(done) {
        adminBar.style.overflow = 'hidden'
        adminBar.classList.remove('uab-shown')
        afterTransition(adminBar, 'max-height', function () {
            adminBar.style.display = 'none'
            done()
        })
    }

    // Show the button shortly after page load, then fade it to reduced
    // opacity once it's been visible for a while. Two rAFs (rather than
    // one) guarantee the browser has painted the initial, off-screen
    // position before the class below starts the transition away from it.
    window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
            btnShow.classList.add('uab-visible')
        })
    })
    window.setTimeout(function () {
        btnShow.classList.add('uab-opaque')
    }, 1000)

    btnShow.addEventListener('click', function (event) {
        event.preventDefault()
        btnShow.classList.remove('uab-visible')
        afterTransition(btnShow, 'top', function () {
            showAdminBar(function () {
                btnHide.focus()
                // btnShow stays in the DOM (off-screen) while shown, unlike
                // btnHide (hidden from AT via #wpadminbar automatically), so
                // it needs aria-hidden explicitly - applied only after
                // moving focus away, since aria-hidden must never land on
                // the focused element.
                btnShow.setAttribute('tabindex', '-1')
                btnShow.setAttribute('aria-hidden', 'true')
                wp.a11y.speak(uabL10n.shownAnnouncement)
            })
        })
    })

    btnHide.addEventListener('click', function (event) {
        event.preventDefault()
        hideAdminBar(function () {
            btnShow.removeAttribute('tabindex')
            btnShow.removeAttribute('aria-hidden')
            btnShow.focus()
            wp.a11y.speak(uabL10n.hiddenAnnouncement)
        })
        btnShow.classList.add('uab-visible')
    })
})
