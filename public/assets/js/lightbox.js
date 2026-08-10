/**
 * Fullscreen image viewer.
 *
 * The version this replaces built its overlay by interpolating titles and
 * filenames into innerHTML, which made any upload title a stored-XSS vector.
 * This one builds nodes and assigns textContent, so markup in a title is shown
 * as text and can never execute.
 */
(function () {
  'use strict';

  const dataEl = document.getElementById('lightboxData');
  if (!dataEl) return;

  let items = [];
  try {
    items = JSON.parse(dataEl.textContent) || [];
  } catch (e) {
    return;
  }
  if (!items.length) return;

  let index = 0;
  let root = null;
  let imgEl = null;
  let titleEl = null;
  let metaEl = null;
  let counterEl = null;
  let prevBtn = null;
  let nextBtn = null;
  let lastFocused = null;

  function build() {
    root = document.createElement('div');
    root.className = 'lightbox';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.hidden = true;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'lightbox-close';
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.textContent = '×';

    counterEl = document.createElement('div');
    counterEl.className = 'lightbox-counter';

    prevBtn = document.createElement('button');
    prevBtn.className = 'lightbox-nav lightbox-prev';
    prevBtn.type = 'button';
    prevBtn.setAttribute('aria-label', 'Previous image');
    prevBtn.textContent = '‹';

    nextBtn = document.createElement('button');
    nextBtn.className = 'lightbox-nav lightbox-next';
    nextBtn.type = 'button';
    nextBtn.setAttribute('aria-label', 'Next image');
    nextBtn.textContent = '›';

    const figure = document.createElement('figure');
    figure.className = 'lightbox-figure';

    imgEl = document.createElement('img');
    imgEl.className = 'lightbox-image';
    imgEl.alt = '';

    const caption = document.createElement('figcaption');
    caption.className = 'lightbox-caption';

    titleEl = document.createElement('div');
    titleEl.className = 'lightbox-title';

    metaEl = document.createElement('div');
    metaEl.className = 'lightbox-meta';

    caption.append(titleEl, metaEl);
    figure.append(imgEl, caption);
    root.append(closeBtn, counterEl, prevBtn, nextBtn, figure);
    document.body.appendChild(root);

    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', () => show(index - 1));
    nextBtn.addEventListener('click', () => show(index + 1));

    // Clicking the backdrop closes; clicking the image itself must not.
    root.addEventListener('click', (e) => {
      if (e.target === root || e.target === figure) close();
    });

    document.addEventListener('keydown', onKeydown);
  }

  function onKeydown(e) {
    if (root.hidden) return;

    if (e.key === 'Escape') {
      close();
    } else if (e.key === 'ArrowLeft') {
      show(index - 1);
    } else if (e.key === 'ArrowRight') {
      show(index + 1);
    } else if (e.key === 'Tab') {
      // Keep focus inside the dialog while it is open.
      e.preventDefault();
    }
  }

  function show(next) {
    if (next < 0 || next >= items.length) return;
    index = next;

    const item = items[index];
    imgEl.src = item.src;
    imgEl.alt = item.title || '';

    // textContent, not innerHTML — titles are user input.
    titleEl.textContent = item.title || 'Untitled';
    metaEl.textContent = [item.owner, item.size].filter(Boolean).join(' · ');
    counterEl.textContent = index + 1 + ' / ' + items.length;

    prevBtn.disabled = index === 0;
    nextBtn.disabled = index === items.length - 1;
  }

  function open(at) {
    lastFocused = document.activeElement;
    root.hidden = false;
    document.body.classList.add('lightbox-open');
    show(at);
    root.querySelector('.lightbox-close').focus();
  }

  function close() {
    root.hidden = true;
    document.body.classList.remove('lightbox-open');
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  build();

  document.querySelectorAll('[data-lightbox-index]').forEach((el) => {
    el.addEventListener('click', () => {
      const at = parseInt(el.getAttribute('data-lightbox-index'), 10);
      if (!Number.isNaN(at)) open(at);
    });
  });
})();
