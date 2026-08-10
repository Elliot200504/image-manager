/**
 * Rename and delete controls on the browse page.
 *
 * These only appear for files the viewer owns, but that is presentation only —
 * api.php re-checks ownership on every action.
 */
(function () {
  'use strict';

  const grid = document.querySelector('.page-main');
  if (!grid) return;

  grid.addEventListener('click', async (event) => {
    const renameBtn = event.target.closest('.js-rename');
    const deleteBtn = event.target.closest('.js-delete');
    if (!renameBtn && !deleteBtn) return;

    const card = event.target.closest('[data-file-id]');
    if (!card) return;

    const fileId = card.getAttribute('data-file-id');
    const button = renameBtn || deleteBtn;
    const currentTitle = button.getAttribute('data-title') || '';

    if (renameBtn) {
      const next = window.prompt('Rename file', currentTitle);
      // null means cancelled; an unchanged value means there is nothing to do.
      if (next === null) return;

      const trimmed = next.trim();
      if (trimmed === '' || trimmed === currentTitle) return;

      try {
        await window.App.api('rename', { file_id: fileId, title: trimmed });

        const titleEl = card.querySelector('.file-card-title');
        if (titleEl) titleEl.textContent = trimmed;
        button.setAttribute('data-title', trimmed);

        const deleteSibling = card.querySelector('.js-delete');
        if (deleteSibling) deleteSibling.setAttribute('data-title', trimmed);

        window.App.toast('Renamed.', 'success');
      } catch (e) {
        window.App.toast(e.message, 'error');
      }
      return;
    }

    if (!window.confirm('Delete “' + currentTitle + '”? This cannot be undone.')) {
      return;
    }

    try {
      await window.App.api('delete', { file_id: fileId });
      card.remove();
      window.App.toast('Deleted.', 'success');
    } catch (e) {
      window.App.toast(e.message, 'error');
    }
  });
})();
