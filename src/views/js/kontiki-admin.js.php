<?php
/**
  * @var string $skip_to_main_content
  * @var string $skip_to_navigation
  * @var string $open_sidebar
  * @var string $close_sidebar
  * @var string $publishing
  * @var string $reserved
  * @var string $expired
  * @var string $do_publish
  * @var string $do_reserve
  * @var string $do_save_as_pending
  * @var string $do_save_as_draft
  * @var string $banned_url
  * @var string $reserved_url
  * @var string $published_url
  * @var string $published_at
  * @var string $open_in_new_window
  */
?>document.addEventListener('DOMContentLoaded', () => {

    /**
     * localize skip link
     */
    const mainSkipLink = document.querySelector(".skip-links a[href='#main']");
    const navigationSkipLink = document.querySelector(".skip-links a[href='#navigation']");
    if (mainSkipLink) mainSkipLink.textContent = "<?= $skip_to_main_content ?>";
    if (navigationSkipLink) navigationSkipLink.textContent = "<?= $skip_to_navigation ?>";

    /**
     * add aria-label to sidebar button
     */
    // i18n: tweak if you prefer different wording
    const LABEL_OPEN  = '<?= $open_sidebar ?>';
    const LABEL_CLOSE = '<?= $close_sidebar ?>';

    // Small debounce helper
    function debounce(fn, wait) {
        let t; return function () { clearTimeout(t); t = setTimeout(() => fn.apply(this, arguments), wait); };
    }

    {
        const toggles = document.querySelectorAll('[data-lte-toggle="sidebar"]');

        if (toggles.length > 0) {
            // Determine if sidebar is visually open right now.
            // AdminLTE v4 toggles classes on <body>. On large screens it may stay open by layout.
            function isSidebarOpen() {
                // On small screens AdminLTE adds/removes .sidebar-open
                if (window.matchMedia('(max-width: 991.98px)').matches) {
                    return document.body.classList.contains('sidebar-open');
                }
                // On lg+ screens, absence of "sidebar-collapse" usually means expanded
                return !document.body.classList.contains('sidebar-collapse');
            }

            // Sync ARIA to current state
            function syncAria() {
                const open = isSidebarOpen();
                toggles.forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', String(open));
                    toggle.setAttribute('aria-label', open ? LABEL_CLOSE : LABEL_OPEN);
                });
            }

            // Initial sync
            syncAria();

            // Click updates (AdminLTE toggling happens on click; run after it)
            toggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    // Next tick is enough; if you see race, increase delay slightly
                    setTimeout(syncAria, 0);
                });

                // Keyboard: make Space work on <a role="button"> for better a11y
                toggle.addEventListener('keydown', (event) => {
                    if (event.key === ' ') {
                        event.preventDefault();
                        toggle.click();
                    }
                });
            });

            // Keep in sync when layout changes (resize / responsive behavior)
            window.addEventListener('resize', debounce(syncAria, 100));

            // Also observe <body> class changes from AdminLTE (most reliable)
            const observer = new MutationObserver(syncAria);
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        }
    }

    /**
     * move create post button
     */
    const createButton = document.getElementById('create_button_in_index');
    const contentHeading = document.querySelector('#content-header h1');
    if (createButton && contentHeading) contentHeading.after(createButton);

    /**
     * open sidebar menu items
     */
    var currentUrl = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-item').forEach((menuItem) => {
        const menuPath = menuItem.getAttribute('data-path');
        const menuLink = menuItem.querySelector(':scope > a.nav-link');

        if (menuPath && currentUrl.startsWith(menuPath)) {
            menuItem.classList.add('menu-is-opening', 'menu-open');
            const treeView = menuItem.querySelector('.nav-treeview');
            if (treeView) treeView.style.display = 'block';
            if (menuLink) menuLink.setAttribute('aria-expanded', 'true');
        } else if (menuLink) {
            menuLink.setAttribute('aria-expanded', 'false');
        }
    });

    /**
     * notice message updated
     */
    const flashMessages = document.querySelectorAll('[role="status"]');
    if (flashMessages.length > 0) {
        flashMessages.forEach((message) => {
            message.hidden = true;
        });
        setTimeout(() => {
            flashMessages.forEach((message) => {
                message.hidden = false;
            });
        }, 100);
    }

    /**
     * 1. Click on the label of the collapsed section
     * 2. Details will open and you can enter them immediately.
     */
    document.querySelectorAll('details > summary > label').forEach((label) => {
      label.addEventListener('click', (event) => {
        event.preventDefault();

        const details = label.closest('details');
        if (!details) return;

        details.open = !details.open;
        if (details.open) {
          const input = details.querySelector('input, textarea, select');
          if (input) setTimeout(() => input.focus(), 50);
        }
      });
    });

    /**
     * If there is an input or textarea inside <details> and
     * the value is not empty, the open attribute is automatically added.
     */
    document.querySelectorAll('details').forEach((details) => {
        const hasValue = Array.from(details.querySelectorAll('input, textarea')).some((element) => {
          return element.value.trim() !== '';
        });

        if (hasValue) details.open = true;
    });

    /**
     * update status (place bottom of this file)
     */
    const publishedAtInput = document.querySelector('input[name=published_at]');
    const expiredAtInput = document.querySelector('input[name=expired_at]');
    const mainSubmitBtn = document.getElementById('mainSubmitBtn');
    const publishedDetails = publishedAtInput?.closest('details');
    const expiredDetails = expiredAtInput?.closest('details');
    const statusSelect = document.querySelector('select[name=status]');

    if ((!publishedAtInput && !expiredAtInput) || !mainSubmitBtn || !statusSelect) {
        return;
    }

    function updateStatusOption() {
        const publishedAt = publishedAtInput?.value || '';
        const expiredAt = expiredAtInput?.value || '';
        const statusOptionPublished = statusSelect.querySelector('option[value=published]');
        const currentStatus = statusSelect.value;
        const publishedAtLabel = document.querySelector("label[for='published_at']");

        let publishedDate = publishedAt ? new Date(publishedAt) : null;
        let expiredDate = expiredAt ? new Date(expiredAt) : null;
        let now = new Date();

        // butons and status options
        if (currentStatus === "draft") {
            mainSubmitBtn.textContent = "<?= $do_save_as_draft ?>";
        } else if (currentStatus === "pending") {
            mainSubmitBtn.textContent = "<?= $do_save_as_pending ?>";
        } else if (expiredDate && !isNaN(expiredDate.getTime()) && expiredDate <= now) {
            if (statusOptionPublished) statusOptionPublished.textContent = "<?= $expired ?>";
            mainSubmitBtn.textContent = "<?= $do_save_as_pending ?>";
        } else if (publishedDate && !isNaN(publishedDate.getTime()) && publishedDate > now) {
            if (statusOptionPublished) statusOptionPublished.textContent = "<?= $reserved ?>";
            mainSubmitBtn.textContent = "<?= $do_reserve ?>";
        } else {
            if (statusOptionPublished) statusOptionPublished.textContent = "<?= $publishing ?>";
            mainSubmitBtn.textContent = "<?= $do_publish ?>";
            if (publishedAtLabel) publishedAtLabel.textContent = "<?= $published_at ?>";
        }

        // update URL and its label
        let urlLabel = document.getElementById("publishedUrlLabel");
        let urlTextElement = document.getElementById("publishedUrlText");

        if (!urlTextElement || !urlLabel) {
            return;
        }

        let url = urlTextElement.textContent.trim();

        if (
            currentStatus === "pending" ||
            (expiredDate && !isNaN(expiredDate.getTime()) && expiredDate <= now)
        ) {
            urlLabel.textContent = "<?= $banned_url ?>";
            if (urlTextElement.tagName.toLowerCase() === "a") {
                let span = document.createElement("span");
                span.id = "publishedUrlText";
                span.textContent = url;
                urlTextElement.replaceWith(span);
            }
        } else if (
            currentStatus === "draft" ||
            (
                currentStatus === "published" &&
                publishedDate &&
                !isNaN(publishedDate.getTime()) &&
                publishedDate > now
            )
        ) {
            urlLabel.textContent = "<?= $reserved_url ?>";
            if (urlTextElement.tagName.toLowerCase() === "a") {
                let span = document.createElement("span");
                span.id = "publishedUrlText";
                span.textContent = url;
                urlTextElement.replaceWith(span);
            }
        } else {
            urlLabel.textContent = "<?= $published_url ?>";
            if (urlTextElement.tagName.toLowerCase() !== "a") {
                let anchor = document.createElement("a");
                anchor.id = "publishedUrlText";
                anchor.href = url;
                anchor.target = "publishedPage";
                anchor.textContent = url;
                anchor.insertAdjacentHTML(
                    "beforeend",
                    <?= json_encode(
                        ' ' . icon('box-arrow-up-right')
                        . '<span class="visually-hidden">('
                        . e($open_in_new_window) . ')</span>',
                        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    ) ?>
                );
                urlTextElement.replaceWith(anchor);
            }
        }
    }

    function updateDetailsState() {
        const publishedAt = publishedAtInput?.value || '';
        const expiredAt = expiredAtInput?.value || '';
        let publishedDate = publishedAt ? new Date(publishedAt) : null;
        let now = new Date();
        const currentStatus = statusSelect.value;

        if (currentStatus === "draft") {
            mainSubmitBtn.textContent = "<?= $do_save_as_draft ?>";
            return;
        } else if (currentStatus === "pending") {
            mainSubmitBtn.textContent = "<?= $do_save_as_pending ?>";
            return;
        }

        if (expiredAt) {
            if (expiredDetails) expiredDetails.open = true;
            mainSubmitBtn.textContent = "<?= $do_save_as_pending ?>";
        } else if (expiredDetails) {
            expiredDetails.open = false;
        }

        if (publishedDate && !isNaN(publishedDate.getTime()) && publishedDate > now) {
            if (publishedDetails) publishedDetails.open = true;
            mainSubmitBtn.textContent = "<?= $do_reserve ?>";
        } else if (publishedDetails) {
            publishedDetails.open = false;
        }
    }

    updateStatusOption();
    updateDetailsState();

    [publishedAtInput, expiredAtInput].forEach((input) => {
        if (!input) return;
        ['input', 'change'].forEach((eventName) => {
            input.addEventListener(eventName, () => {
                updateStatusOption();
                updateDetailsState();
            });
        });
    });

    statusSelect.addEventListener('change', () => {
        updateStatusOption();
    });
});
