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

  function loadPreset(scriptId) {
    var scriptEl = document.getElementById(scriptId);
    var inputEl = document.getElementById('toon-input');
    var outputEl = document.getElementById('toon-output');
    if (!scriptEl || !inputEl) return;

    try {
      var data = JSON.parse(scriptEl.textContent || '');
      inputEl.value = JSON.stringify(data, null, 2);
      if (outputEl) outputEl.value = '';
    } catch (e) {
      // ignore invalid preset JSON
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
    var presetHikes = document.getElementById('preset-hikes');
    var presetTypo3 = document.getElementById('preset-typo3');
    modal = document.getElementById('toon-clear-modal');

    if (copyBtn) copyBtn.addEventListener('click', copyOutput);
    if (clearBtn) clearBtn.addEventListener('click', openModal);
    if (confirmBtn) confirmBtn.addEventListener('click', confirmClear);
    if (presetHikes) {
      presetHikes.addEventListener('click', function () {
        loadPreset('toon-preset-hikes');
      });
    }
    if (presetTypo3) {
      presetTypo3.addEventListener('click', function () {
        loadPreset('toon-preset-typo3');
      });
    }

    if (modal) {
      var closers = modal.querySelectorAll('[data-toon-modal-close]');
      for (var i = 0; i < closers.length; i++) {
        closers[i].addEventListener('click', closeModal);
      }

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
