/**
 * Simple FocusTrap utility
 * - Usage:
 *   const trap = new FocusTrap(modalEl, { onDeactivate: () => { /* optional */ } });
 *   trap.activate();
 *   trap.deactivate();
 *
 * This file intentionally avoids modern module syntax so it can be included via
 * a normal <script> tag together with other frontend scripts in this project.
 */
(function () {
  'use strict';

  function isFocusable(el) {
    if (!el || el.disabled) return false;
    const style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden') return false;
    const tabIndex = el.getAttribute('tabindex');
    if (tabIndex !== null && parseInt(tabIndex, 10) < 0) return false;
    const focusableTags = ['A', 'AREA', 'INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'IFRAME'];
    if (focusableTags.indexOf(el.tagName) !== -1) {
      if (el.tagName === 'A' || el.tagName === 'AREA') return !!el.href && el.getAttribute('href') !== '#';
      return true;
    }
    return el.hasAttribute('tabindex');
  }

  function getFocusableElements(container) {
    if (!container) return [];
    const elements = Array.prototype.slice.call(
      container.querySelectorAll(
        'a[href], area[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, [tabindex]'
      )
    );
    return elements.filter(isFocusable);
  }

  function FocusTrap(container, options) {
    if (!(this instanceof FocusTrap)) return new FocusTrap(container, options);
    this.container = container;
    this.options = options || {};
    this.active = false;
    this.previouslyFocused = null;
    this._keydownHandler = this._keydownHandler.bind(this);
    this._focusinHandler = this._focusinHandler.bind(this);
  }

  FocusTrap.prototype._keydownHandler = function (e) {
    if (!this.active) return;
    if (e.key === 'Escape' || e.key === 'Esc') {
      e.preventDefault();
      this.deactivate();
      if (typeof this.options.onDeactivate === 'function') {
        try { this.options.onDeactivate(); } catch (err) { console.error(err); }
      }
      return;
    }

    if (e.key !== 'Tab') return;

    const focusables = getFocusableElements(this.container);
    if (focusables.length === 0) {
      // nothing focusable; prevent leaving
      e.preventDefault();
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (e.shiftKey) {
      // Shift + Tab
      if (document.activeElement === first || this.container === document.activeElement) {
        e.preventDefault();
        last.focus();
      }
    } else {
      // Tab
      if (document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  };

  FocusTrap.prototype._focusinHandler = function (e) {
    if (!this.active) return;
    if (!this.container.contains(e.target)) {
      // If focus moved outside (programmatically), bring it back to first focusable
      const focusables = getFocusableElements(this.container);
      if (focusables.length) focusables[0].focus();
      else this.container.focus();
    }
  };

  FocusTrap.prototype.activate = function () {
    if (!this.container) return;
    if (this.active) return;
    this.previouslyFocused = document.activeElement;
    // Ensure container is focusable
    if (!this.container.hasAttribute('tabindex')) this.container.setAttribute('tabindex', '-1');
    // Move focus into the modal
    const focusables = getFocusableElements(this.container);
    if (focusables.length) focusables[0].focus();
    else this.container.focus();

    document.addEventListener('keydown', this._keydownHandler, true);
    document.addEventListener('focusin', this._focusinHandler, true);
    this.active = true;
  };

  FocusTrap.prototype.deactivate = function () {
    if (!this.active) return;
    document.removeEventListener('keydown', this._keydownHandler, true);
    document.removeEventListener('focusin', this._focusinHandler, true);
    this.active = false;
    // restore focus
    try {
      if (this.previouslyFocused && typeof this.previouslyFocused.focus === 'function') this.previouslyFocused.focus();
    } catch (e) {
      // ignore
    }
    if (typeof this.options.onDeactivate === 'function') {
      try { this.options.onDeactivate(); } catch (err) { console.error(err); }
    }
  };

  // Expose globally
  window.FocusTrap = FocusTrap;
})();
