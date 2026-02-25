/**
 * TOON Playground backend module – behaviour
 */
(function () {
  'use strict';

  var modal = null;

  function copyOutput() {
    var outputEl = document.getElementById('toon-output');
    var statusEl = document.getElementById('copy-status');
    var btnEl = document.getElementById('copy-btn');

    if (!outputEl || !outputEl.value) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(outputEl.value).then(function () {
        if (statusEl) {
          statusEl.classList.add('toon-playground__copy-status--visible');
        }
        if (btnEl) {
          btnEl.classList.add('active');
        }
        setTimeout(function () {
          if (statusEl) statusEl.classList.remove('toon-playground__copy-status--visible');
          if (btnEl) btnEl.classList.remove('active');
        }, 2000);
      });
    }
  }

  function openModal() {
    if (!modal) return;
    modal.classList.add('toon-modal--open');
    modal.setAttribute('aria-hidden', 'false');
    var confirmBtn = document.getElementById('toon-clear-confirm-btn');
    if (confirmBtn) confirmBtn.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('toon-modal--open');
    modal.setAttribute('aria-hidden', 'true');
    var clearBtn = document.getElementById('toon-clear-btn');
    if (clearBtn) clearBtn.focus();
  }

  function confirmClear() {
    var inputEl = document.getElementById('toon-input');
    var outputEl = document.getElementById('toon-output');
    if (inputEl) inputEl.value = '';
    if (outputEl) outputEl.value = '';
    closeModal();
  }

  function init() {
    var copyBtn = document.getElementById('copy-btn');
    var clearBtn = document.getElementById('toon-clear-btn');
    var confirmBtn = document.getElementById('toon-clear-confirm-btn');
    modal = document.getElementById('toon-clear-modal');

    if (copyBtn) copyBtn.addEventListener('click', copyOutput);
    if (clearBtn) clearBtn.addEventListener('click', openModal);
    if (confirmBtn) confirmBtn.addEventListener('click', confirmClear);

    // Close modal via [data-toon-modal-close] elements
    if (modal) {
      var closers = modal.querySelectorAll('[data-toon-modal-close]');
      for (var i = 0; i < closers.length; i++) {
        closers[i].addEventListener('click', closeModal);
      }

      // Close on Escape key
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('toon-modal--open')) {
          closeModal();
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
