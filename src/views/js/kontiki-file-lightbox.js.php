// Reusable lightbox for File Manager previews (no nested modals).
// Requirements:
//  - <body> contains a single lightbox root #KontikiLightbox (outside any modal)
//  - Firefox: include WICG/inert polyfill if full support is required
class KontikiFileLightbox {
  /**
   * @param {Object} opts
   * @param {string} [opts.lightboxId='KontikiLightbox']
   * @param {string} [opts.rootSelector='body']
   * @param {string} [opts.closeSelector='.kontiki-lightbox-close']
   * @param {string} [opts.imgSelector='img']
   * @param {string|null} [opts.captionSelector='#KontikiLightboxCaption']
   */
  constructor(opts = {}) {
    this.id = opts.lightboxId || 'KontikiLightbox';
    this.rootSelector = opts.rootSelector || 'body';
    this.rootEl = document.querySelector(this.rootSelector);
    if (!this.rootEl) throw new Error(`rootSelector not found: ${this.rootSelector}`);

    this.lbEl = document.getElementById(this.id);
    if (!this.lbEl) {
        // Lightbox markup not found, disable this instance
        return;
    }
    // if (!this.lbEl) throw new Error(`#${this.id} not found in DOM`);
    // if (!this.lbEl.hasAttribute('tabindex')) this.lbEl.setAttribute('tabindex', '-1');

    this.closeSel = opts.closeSelector || '.kontiki-lightbox-close';
    this.imgEl = this.lbEl.querySelector(opts.imgSelector || 'img');
    if (!this.imgEl) throw new Error('Lightbox <img> element not found');

    this.captionSel = opts.captionSelector ?? '#KontikiLightboxCaption';
    this.captionEl = this.captionSel ? this.lbEl.querySelector(this.captionSel) : null;

    this._keydownCapture = this._keydownCapture.bind(this);
    this._focusinGuard = this._focusinGuard.bind(this);

    $(document)
      .off('click.KontikiLb', `#${this.id}`)
      .on('click.KontikiLb', `#${this.id}`, (e) => {
        const isBackdrop = e.target === e.currentTarget;
        const isCloseBtn = !!e.target.closest(this.closeSel);
        if (isBackdrop || isCloseBtn) this.close();
      });
  }

  bindTriggers(containerSelector, itemSelector='[data-action="preview"]') {
    $(document)
      .off('click.KontikiLbTrig', `${containerSelector} ${itemSelector}`)
      .on('click.KontikiLbTrig', `${containerSelector} ${itemSelector}`, (e) => {
        e.preventDefault();
        const a = e.currentTarget;
        const url = a.dataset.url || a.getAttribute('href');
        const alt = a.dataset.alt || a.getAttribute('title') || '';
        this.open(url, alt);
      });
  }

  open(url, alt='') {
    if (!url) return;
    this._lastFocus = document.activeElement;

    this.imgEl.setAttribute('src', url);
    this.imgEl.setAttribute('alt', alt || '');
    if (this.captionEl) this.captionEl.textContent = alt || '';

    this._setInertForSiblingsExcept(this.rootEl, this.lbEl, true);

    document.documentElement.classList.add('kontiki-no-scroll');
    document.body.classList.add('kontiki-no-scroll');

    this.lbEl.hidden = false;
    this.lbEl.classList.add('show');
    this.lbEl.setAttribute('aria-hidden', 'false');

    (this.lbEl.querySelector(this.closeSel) || this.lbEl).focus({ preventScroll: true });

    document.addEventListener('keydown', this._keydownCapture, true);
    document.addEventListener('focusin', this._focusinGuard);
  }

  close() {
    this.lbEl.classList.remove('show');
    this.lbEl.setAttribute('aria-hidden', 'true');
    this.lbEl.hidden = true;

    // ★ inert解除も root 基準で
    this._setInertForSiblingsExcept(this.rootEl, this.lbEl, false);

    document.removeEventListener('keydown', this._keydownCapture, true);
    document.removeEventListener('focusin', this._focusinGuard);

    document.documentElement.classList.remove('kontiki-no-scroll');
    document.body.classList.remove('kontiki-no-scroll');

    if (this._lastFocus && document.contains(this._lastFocus)) {
      try { this._lastFocus.focus({ preventScroll: true }); } catch {}
    }
    this._lastFocus = null;

    this.imgEl.setAttribute('src', '');
    this.imgEl.setAttribute('alt', '');
    if (this.captionEl) this.captionEl.textContent = '';
  }

  _keydownCapture(e) {
    if (!this.lbEl.classList.contains('show')) return;
    if (e.key === 'Escape') {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation?.();
      this.close();
    }
  }
  _focusinGuard(e) {
    if (!this.lbEl.classList.contains('show')) return;
    if (!this.lbEl.contains(e.target)) {
      (this.lbEl.querySelector(this.closeSel) || this.lbEl).focus({ preventScroll: true });
    }
  }

  // ★ root 直下の子要素を inert 切替（keepEl だけ除外）
  _setInertForSiblingsExcept(rootEl, keepEl, enabled) {
    const kids = Array.from(rootEl.children);
    for (const el of kids) {
      if (el === keepEl) continue;
      if (enabled) el.setAttribute('inert', '');
      else el.removeAttribute('inert');
    }
  }
}
