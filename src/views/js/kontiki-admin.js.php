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
?>$(document).ready(function () {

    /**
     * localize skip link
     */
    $(".skip-links a[href='#main']").text("<?= $skip_to_main_content ?>");
    $(".skip-links a[href='#navigation']").text("<?= $skip_to_navigation ?>");

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

    $(function () {
        const $body = $(document.body);
        const $toggles = $('[data-lte-toggle="sidebar"]');

        if ($toggles.length === 0) return;

        // Determine if sidebar is visually open right now.
        // AdminLTE v4 toggles classes on <body>. On large screens it may stay open by layout.
        function isSidebarOpen() {
            // On small screens AdminLTE adds/removes .sidebar-open
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                return $body.hasClass('sidebar-open');
            }
            // On lg+ screens, absence of "sidebar-collapse" usually means expanded
            return !$body.hasClass('sidebar-collapse');
        }

            // Sync ARIA to current state
            function syncAria() {
                const open = isSidebarOpen();
                $toggles.attr('aria-expanded', String(open));
                $toggles.attr('aria-label', open ? LABEL_CLOSE : LABEL_OPEN);
            }

            // Initial sync
            syncAria();

        // Click updates (AdminLTE toggling happens on click; run after it)
        $toggles.on('click', function () {
            // Next tick is enough; if you see race, increase delay slightly
            setTimeout(syncAria, 0);
        });

        // Keyboard: make Space work on <a role="button"> for better a11y
        $toggles.on('keydown', function (e) {
            if (e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Keep in sync when layout changes (resize / responsive behavior)
        $(window).on('resize', debounce(syncAria, 100));

        // Also observe <body> class changes from AdminLTE (most reliable)
        const mo = new MutationObserver(syncAria);
        mo.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    });

    /**
     * move create post button
     */
    $("#create_button_in_index").insertAfter("#content-header h1");

    /**
     * open sidebar menu items
     */
    var currentUrl = window.location.pathname;
    $(".sidebar .nav-item").each(function () {
        var menuPath = $(this).attr("data-path");

        if (menuPath && currentUrl.startsWith(menuPath)) {
            $(this).addClass("menu-is-opening menu-open");
            $(this).find(".nav-treeview").first().css("display", "block");
            $(this).find('> a.nav-link').attr('aria-expanded', 'true');
        } else {
            $(this).find('> a.nav-link').attr('aria-expanded', 'false');
        }
    });

    /**
     * notice message updated
     */
    let $flashMessage = $('[role="status"]');
    if ($flashMessage.length) {
        $flashMessage.hide();
        setTimeout(function () {
            $flashMessage.slideDown(500);
        }, 100);
    }

    /**
     * 1. Click on the label of the collapsed section
     * 2. Details will open and you can enter them immediately.
     */
    $('details > summary > label').on('click', function (e) {
      e.preventDefault();

      const $label = $(this);
      const $details = $label.closest('details');

      const isOpen = $details.prop('open');

      if (isOpen) {
        $details.prop('open', false);
      } else {
        $details.prop('open', true);

        const $input = $details.find('input, textarea, select').first();
        if ($input.length) {
          setTimeout(() => $input.trigger('focus'), 50);
        }
      }
    });

    /**
     * If there is an input or textarea inside <details> and
     * the value is not empty, the open attribute is automatically added.
     */
    $(function () {
      $('details').each(function () {
        const $details = $(this);

        const hasValue = $details.find('input, textarea').toArray().some(el => {
          return $(el).val().trim() !== '';
        });

        if (hasValue) {
          $details.prop('open', true);
        }
      });
    });

    /**
     * update status (place bottom of this file)
     */
    let publishedAtInput = $("input[name=published_at]");
    let expiredAtInput = $("input[name=expired_at]");
    let mainSubmitBtn = $("button#mainSubmitBtn");
    let publishedDetails = publishedAtInput.closest("details");
    let expiredDetails = expiredAtInput.closest("details");
    let statusSelect = $("select[name=status]");

    if (publishedAtInput.length === 0 && expiredAtInput.length === 0) {
        return;
    }

    function updateStatusOption() {
        let publishedAt = publishedAtInput.val();
        let expiredAt = expiredAtInput.val();
        let statusOptionPublished = $("select[name=status] option[value=published]");
        let currentStatus = statusSelect.val();
        let publishedAtLabel = $("label[for='published_at']");

        let publishedDate = publishedAt ? new Date(publishedAt) : null;
        let expiredDate = expiredAt ? new Date(expiredAt) : null;
        let now = new Date();

        // butons and status options
        if (currentStatus === "draft") {
            mainSubmitBtn.text("<?= $do_save_as_draft ?>");
        } else if (currentStatus === "pending") {
            mainSubmitBtn.text("<?= $do_save_as_pending ?>");
        } else if (expiredDate && !isNaN(expiredDate.getTime()) && expiredDate <= now) {
            statusOptionPublished.text("<?= $expired ?>");
            mainSubmitBtn.text("<?= $do_save_as_pending ?>");
        } else if (publishedDate && !isNaN(publishedDate.getTime()) && publishedDate > now) {
            statusOptionPublished.text("<?= $reserved ?>");
            mainSubmitBtn.text("<?= $do_reserve ?>");
        } else {
            statusOptionPublished.text("<?= $publishing ?>");
            mainSubmitBtn.text("<?= $do_publish ?>");
            publishedAtLabel.text("<?= $published_at ?>");
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
                anchor.innerHTML = `${url} <span class="fa-solid fa-arrow-up-right-from-square" aria-label="<?= $open_in_new_window ?>"></span>`;
                urlTextElement.replaceWith(anchor);
            }
        }
    }

    function updateDetailsState() {
        let publishedAt = publishedAtInput.val();
        let expiredAt = expiredAtInput.val();
        let publishedDate = publishedAt ? new Date(publishedAt) : null;
        let now = new Date();
        let currentStatus = statusSelect.val();

        if (currentStatus === "draft") {
            mainSubmitBtn.text("<?= $do_save_as_draft ?>");
            return;
        } else if (currentStatus === "pending") {
            mainSubmitBtn.text("<?= $do_save_as_pending ?>");
            return;
        }

        if (expiredAt) {
            expiredDetails.attr("open", true);
            mainSubmitBtn.text("<?= $do_save_as_pending ?>");
        } else {
            expiredDetails.removeAttr("open");
        }

        if (publishedDate && !isNaN(publishedDate.getTime()) && publishedDate > now) {
            publishedDetails.attr("open", true);
            mainSubmitBtn.text("<?= $do_reserve ?>");
        } else {
            publishedDetails.removeAttr("open");
        }
    }

    updateStatusOption();
    updateDetailsState();

    $("input[name=published_at], input[name=expired_at]").on("input change", function () {
        updateStatusOption();
        updateDetailsState();
    });

    statusSelect.on("change", function () {
        updateStatusOption();
    });
});
