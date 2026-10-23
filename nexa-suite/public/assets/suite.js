(function () {
    'use strict';

    var $ = function (selector, root) {
        return (root || document).querySelector(selector);
    };

    var $$ = function (selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    };

    /* ---------------------------------------------------------------- nav */

    var navToggle = $('[data-nav-toggle]');
    var siteLinks = $('#site-links');

    var closeNav = function () {
        if (!navToggle || !siteLinks) return;
        siteLinks.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
    };

    if (navToggle && siteLinks) {
        navToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = siteLinks.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!siteLinks.classList.contains('is-open')) return;
            if (siteLinks.contains(event.target) || navToggle.contains(event.target)) return;
            closeNav();
        });

        $$('a', siteLinks).forEach(function (link) {
            link.addEventListener('click', closeNav);
        });
    }

    /* ------------------------------------------------------------ sidebar */

    var sidebar = $('#sidebar');
    var scrim = $('.sidebar-scrim');
    var sidebarOpen = $('[data-sidebar-open]');

    var setSidebar = function (open) {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', open);
        if (scrim) scrim.classList.toggle('is-visible', open);
        document.body.classList.toggle('has-drawer', open);
        if (sidebarOpen) sidebarOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    if (sidebarOpen) {
        sidebarOpen.addEventListener('click', function () {
            setSidebar(true);
        });
    }

    $$('[data-sidebar-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            setSidebar(false);
        });
    });

    /* ------------------------------------------------------------- copy */

    var legacyCopy = function (text) {
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.top = '-1000px';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        area.setSelectionRange(0, area.value.length);
        var copied = false;

        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(area);
        return copied;
    };

    var copyText = function (text) {
        if (!text) return Promise.resolve(false);

        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(function () {
                return true;
            }).catch(function () {
                return legacyCopy(text);
            });
        }

        return Promise.resolve(legacyCopy(text));
    };

    var flashCopied = function (button) {
        if (!button) return;

        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.innerHTML;
        }

        button.classList.add('is-copied');
        button.innerHTML = button.dataset.copiedLabel || 'Copied';

        window.clearTimeout(button._copyTimer);
        button._copyTimer = window.setTimeout(function () {
            button.classList.remove('is-copied');
            button.innerHTML = button.dataset.originalLabel;
        }, 1800);
    };

    $$('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-copy-target'));
            if (!target) return;

            var text = ('value' in target && /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))
                ? target.value
                : target.textContent;

            copyText((text || '').trim()).then(function (ok) {
                if (ok) flashCopied(button);
            });
        });
    });

    $$('[data-copy-value]').forEach(function (button) {
        button.addEventListener('click', function () {
            copyText(button.getAttribute('data-copy-value') || '').then(function (ok) {
                if (ok) flashCopied(button);
            });
        });
    });

    /* ---------------------------------------------------------- confirms */

    $$('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    /* ------------------------------------------------- nest / egg filter */

    var nestSelect = $('[data-nest-select]');
    var eggSelect = $('[data-egg-select]');

    if (nestSelect && eggSelect) {
        var eggOptions = $$('option[data-nest]', eggSelect);

        var filterEggs = function () {
            var nest = nestSelect.value;
            var firstMatch = null;

            eggOptions.forEach(function (option) {
                var match = option.getAttribute('data-nest') === nest;
                option.hidden = !match;
                option.disabled = !match;

                if (match && firstMatch === null) {
                    firstMatch = option;
                }
            });

            if (eggSelect.selectedOptions.length === 0 || eggSelect.selectedOptions[0].hidden) {
                if (firstMatch) eggSelect.value = firstMatch.value;
            }
        };

        nestSelect.addEventListener('change', filterEggs);
        filterEggs();
    }

    /* --------------------------------------------------------- keyboard */

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        closeNav();

        if (sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
        }
    });

    /* ------------------------------------------------------ close drawer */

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            setSidebar(false);
            closeNav();
        }
    });
})();
