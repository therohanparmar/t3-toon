/**
 * TOON Logs backend module — selection + delete confirmation behaviour.
 */
(function () {
  'use strict';

  var modal = null;
  var pendingAction = null; // 'bulk' | 'single'
  var pendingSingleUid = null;
  var bulkLabelTemplate = '';

  function selectedCheckboxes() {
    return Array.prototype.slice.call(document.querySelectorAll('.toon-logs__row-select:checked'));
  }

  function updateBulkBar() {
    var bar = document.getElementById('toon-logs-bulkbar');
    var count = selectedCheckboxes().length;
    var countEl = document.getElementById('toon-logs-selected-count');
    var labelSpan = bar ? bar.querySelector('[data-bulk-label]') : null;

    if (countEl) countEl.textContent = String(count);
    if (labelSpan && bulkLabelTemplate) {
      labelSpan.textContent = bulkLabelTemplate.replace('%d', String(count));
    }
    if (bar) {
      if (count > 0) {
        bar.removeAttribute('hidden');
      } else {
        bar.setAttribute('hidden', '');
      }
    }
  }

  function syncSelectAll() {
    var selectAll = document.getElementById('toon-logs-select-all');
    var boxes = Array.prototype.slice.call(document.querySelectorAll('.toon-logs__row-select'));
    if (!selectAll || boxes.length === 0) return;
    var checkedCount = boxes.filter(function (b) { return b.checked; }).length;
    selectAll.checked = checkedCount === boxes.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
  }

  function openModal(mode, count) {
    if (!modal) return;
    pendingAction = mode;
    var body = document.querySelector('#toon-logs-modal-body span');
    if (body) {
      var key = count > 1 ? body.getAttribute('data-body-bulk') : body.getAttribute('data-body-single');
      if (key) body.textContent = key;
    }
    modal.classList.add('toon-modal--open');
    modal.setAttribute('aria-hidden', 'false');
    var confirmBtn = document.getElementById('toon-logs-confirm-delete');
    if (confirmBtn) confirmBtn.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('toon-modal--open');
    modal.setAttribute('aria-hidden', 'true');
    pendingAction = null;
    pendingSingleUid = null;
  }

  function submitPending() {
    if (pendingAction === 'bulk') {
      var bulkForm = document.getElementById('toon-logs-bulk-form');
      if (bulkForm) bulkForm.submit();
    } else if (pendingAction === 'single') {
      var singleInput = document.getElementById('toon-logs-single-uid');
      var singleForm = document.getElementById('toon-logs-single-form');
      if (singleInput && singleForm && pendingSingleUid != null) {
        singleInput.value = String(pendingSingleUid);
        singleForm.submit();
      }
    }
  }

  function init() {
    modal = document.getElementById('toon-logs-delete-modal');

    var bulkLabelEl = document.querySelector('#toon-logs-bulk-delete [data-bulk-label]');
    if (bulkLabelEl) {
      bulkLabelTemplate = bulkLabelEl.getAttribute('data-bulk-label') || '';
    }

    var selectAll = document.getElementById('toon-logs-select-all');
    if (selectAll) {
      selectAll.addEventListener('change', function () {
        var boxes = document.querySelectorAll('.toon-logs__row-select');
        for (var i = 0; i < boxes.length; i++) {
          boxes[i].checked = selectAll.checked;
        }
        updateBulkBar();
      });
    }

    var rowBoxes = document.querySelectorAll('.toon-logs__row-select');
    for (var i = 0; i < rowBoxes.length; i++) {
      rowBoxes[i].addEventListener('change', function () {
        syncSelectAll();
        updateBulkBar();
      });
    }

    var bulkBtn = document.getElementById('toon-logs-bulk-delete');
    if (bulkBtn) {
      bulkBtn.addEventListener('click', function () {
        var count = selectedCheckboxes().length;
        if (count === 0) return;
        openModal('bulk', count);
      });
    }

    var deleteButtons = document.querySelectorAll('.toon-logs__delete-row');
    for (var j = 0; j < deleteButtons.length; j++) {
      deleteButtons[j].addEventListener('click', function (e) {
        var uid = e.currentTarget.getAttribute('data-uid');
        if (!uid) return;
        pendingSingleUid = uid;
        openModal('single', 1);
      });
    }

    var confirmBtn = document.getElementById('toon-logs-confirm-delete');
    if (confirmBtn) confirmBtn.addEventListener('click', submitPending);

    // Dismissible optimization note. Hidden only for the current view;
    // not persisted, so it reappears on reload.
    var noteCloseBtn = document.getElementById('toon-logs-note-close');
    if (noteCloseBtn) {
      noteCloseBtn.addEventListener('click', function () {
        // d-none instead of [hidden]: .callout sets display:flex, which
        // would override the UA's [hidden] { display: none } rule.
        var note = document.getElementById('toon-logs-note');
        if (note) note.classList.add('d-none');
      });
    }

    if (modal) {
      var closers = modal.querySelectorAll('[data-toon-modal-close]');
      for (var k = 0; k < closers.length; k++) {
        closers[k].addEventListener('click', closeModal);
      }
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('toon-modal--open')) {
          closeModal();
        }
      });
    }

    updateBulkBar();
    syncSelectAll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
