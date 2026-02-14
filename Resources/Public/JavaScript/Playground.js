/**
 * TOON Playground backend module – minimal behaviour
 */
(function () {
  'use strict';

  function copyOutput() {
    var outputEl = document.getElementById('toon-output');
    var statusEl = document.getElementById('copy-status');
    var btnEl = document.getElementById('copy-btn');

    if (!outputEl || !outputEl.value) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(outputEl.value).then(function () {
        if (statusEl) {
          statusEl.classList.remove('opacity-0');
        }
        if (btnEl) {
          btnEl.classList.replace('btn-success', 'btn-outline-success');
        }
        setTimeout(function () {
          if (statusEl) statusEl.classList.add('opacity-0');
          if (btnEl) btnEl.classList.replace('btn-outline-success', 'btn-success');
        }, 2000);
      });
    }
  }

  function clearAll() {
    var msg = 'Clear both input and output?';
    var btn = document.getElementById('toon-clear-btn');
    if (btn && btn.getAttribute('data-confirm-message')) {
      msg = btn.getAttribute('data-confirm-message');
    }
    if (window.confirm(msg)) {
      var inputEl = document.getElementById('toon-input');
      var outputEl = document.getElementById('toon-output');
      if (inputEl) inputEl.value = '';
      if (outputEl) outputEl.value = '';
    }
  }

  function init() {
    var copyBtn = document.getElementById('copy-btn');
    var clearBtn = document.getElementById('toon-clear-btn');
    if (copyBtn) copyBtn.addEventListener('click', copyOutput);
    if (clearBtn) clearBtn.addEventListener('click', clearAll);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
