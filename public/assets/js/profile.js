/**
 * Profile page: drag-to-reorder and delete for the owner's own files.
 *
 * Only file ids are sent. The endpoint this replaces accepted whole records
 * from the client and wrote them back verbatim, so the browser could rewrite
 * any field on any row.
 */
(function () {
  'use strict';

  const list = document.getElementById('myFileList');
  if (!list) return;

  let dragged = null;

  function renumber() {
    list.querySelectorAll('.file-list-row').forEach((row, i) => {
      const position = row.querySelector('.file-list-position');
      if (position) position.textContent = String(i + 1);
    });
  }

  function currentOrder() {
    return Array.from(list.querySelectorAll('.file-list-row'))
      .map((row) => row.getAttribute('data-file-id'))
      .filter(Boolean);
  }

  async function persist() {
    try {
      await window.App.api('reorder', { order: currentOrder() });
    } catch (e) {
      window.App.toast(e.message, 'error');
    }
  }

  list.querySelectorAll('.file-list-row').forEach((row) => {
    row.draggable = true;

    row.addEventListener('dragstart', (e) => {
      dragged = row;
      row.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      // Firefox will not start a drag unless data is set.
      e.dataTransfer.setData('text/plain', row.getAttribute('data-file-id') || '');
    });

    row.addEventListener('dragend', () => {
      row.classList.remove('dragging');
      dragged = null;
      renumber();
      persist();
    });

    row.addEventListener('dragover', (e) => {
      if (!dragged || dragged === row) return;
      e.preventDefault();

      // Insert before or after depending on which half of the row we are over,
      // so the drop target matches what the cursor is pointing at.
      const box = row.getBoundingClientRect();
      const after = e.clientY > box.top + box.height / 2;
      row.parentNode.insertBefore(dragged, after ? row.nextSibling : row);
    });
  });

  list.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-delete');
    if (!button) return;

    const row = button.closest('[data-file-id]');
    if (!row) return;

    const title = button.getAttribute('data-title') || '';
    if (!window.confirm('Delete “' + title + '”? This cannot be undone.')) return;

    try {
      await window.App.api('delete', { file_id: row.getAttribute('data-file-id') });
      row.remove();
      renumber();
      window.App.toast('Deleted.', 'success');
    } catch (e) {
      window.App.toast(e.message, 'error');
    }
  });
})();
