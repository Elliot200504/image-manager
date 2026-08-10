/**
 * Upload form: drop-zone states, a local preview, and a client-side size check.
 *
 * The size check is a courtesy so a large file fails instantly instead of after
 * a long POST. The server enforces the real limit — this is trivially bypassed.
 */
(function () {
  'use strict';

  const input = document.getElementById('fileInput');
  const drop = document.getElementById('fileDrop');
  const preview = document.getElementById('uploadPreview');
  const previewImage = document.getElementById('previewImage');
  const previewName = document.getElementById('previewName');
  const previewSize = document.getElementById('previewSize');
  const submit = document.getElementById('uploadSubmit');

  if (!input || !drop) return;

  const maxBytes = parseInt(input.getAttribute('data-max-bytes'), 10) || Infinity;
  let objectUrl = null;

  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) {
      value /= 1024;
      unit++;
    }
    return (value >= 10 ? Math.round(value) : value.toFixed(1)) + ' ' + units[unit];
  }

  function releasePreview() {
    if (objectUrl) {
      // Without this the blob stays in memory for the life of the document.
      URL.revokeObjectURL(objectUrl);
      objectUrl = null;
    }
  }

  function clearPreview() {
    releasePreview();
    preview.hidden = true;
    previewImage.hidden = true;
    previewImage.removeAttribute('src');
    previewName.textContent = '';
    previewSize.textContent = '';
    submit.disabled = false;
  }

  function showFile(file) {
    releasePreview();

    previewName.textContent = file.name;
    previewSize.textContent = formatSize(file.size);
    preview.hidden = false;

    if (file.size > maxBytes) {
      previewSize.textContent = formatSize(file.size) + ' — over the limit';
      preview.classList.add('too-large');
      submit.disabled = true;
    } else {
      preview.classList.remove('too-large');
      submit.disabled = false;
    }

    if (file.type.startsWith('image/')) {
      objectUrl = URL.createObjectURL(file);
      previewImage.src = objectUrl;
      previewImage.hidden = false;
    } else {
      previewImage.hidden = true;
      previewImage.removeAttribute('src');
    }
  }

  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (file) {
      showFile(file);
    } else {
      clearPreview();
    }
  });

  ['dragenter', 'dragover'].forEach((evt) =>
    drop.addEventListener(evt, (e) => {
      e.preventDefault();
      drop.classList.add('dragover');
    })
  );

  ['dragleave', 'dragend'].forEach((evt) =>
    drop.addEventListener(evt, () => drop.classList.remove('dragover'))
  );

  drop.addEventListener('drop', (e) => {
    e.preventDefault();
    drop.classList.remove('dragover');

    const dropped = e.dataTransfer && e.dataTransfer.files;
    if (!dropped || !dropped.length) return;

    // Assign to the input so the dropped file is part of the normal form POST
    // rather than needing a separate upload path.
    input.files = dropped;
    showFile(dropped[0]);
  });

  window.addEventListener('pagehide', releasePreview);
})();
