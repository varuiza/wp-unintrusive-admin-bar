'use strict'

function uabReady(callback) {
    if (document.readyState !== 'loading') {
        callback()
    } else {
        document.addEventListener('DOMContentLoaded', callback)
    }
}

/**
 * Converts a CSS <time> custom property value (e.g. "300ms" or "0s") into
 * a plain number of milliseconds.
 */
function uabParseCssDuration(value) {
    value = value.trim()
    var milliseconds = parseFloat(value)
    return value.endsWith('ms') ? milliseconds : milliseconds * 1000
}

uabReady(function () {
    var adminBar = document.getElementById('wpadminbar')
    var btnShow = document.getElementById('uab-btn-show-admin-bar')
    // Rendered server-side by uab_add_admin_bar_hide_toggle() in
    // unintrusive-admin-bar.php as a native Toolbar API node, so it's
    // already part of the page instead of popping in once this script
    // runs. The Toolbar API only gives the wrapping <li> a predictable id
    // (id="wp-admin-bar-{$node_id}"); its <a> child is plain class="ab-item".
    var btnHide = document.querySelector('#wp-admin-bar-uab-hide-admin-bar > a')

    if (!adminBar || !btnShow || !btnHide) {
        return
    }

    // Single source of truth for this duration lives in css/style.css as
    // the --uab-animation-duration custom property (including its
    // prefers-reduced-motion override), so this can never drift out of
    // sync with the CSS.
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
     * Reveals the admin bar, mirroring jQuery's slideDown(): grows it from
     * 0 to its natural height (WP core's own --wp-admin--admin-bar--height
     * custom property, so this always matches the current responsive
     * breakpoint) and clips the overflow only while animating, so dropdown
     * submenus aren't cut off once it's fully open.
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
                // btnShow stays in the DOM (off-screen) while the bar is
                // shown, unlike btnHide, which is hidden from AT
                // automatically because it lives inside #wpadminbar.
                // Without this it would remain a focusable, invisible
                // control in the tab order for as long as the bar stays
                // open. Applied only after moving focus away from it, since
                // aria-hidden must never land on a currently focused element.
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
