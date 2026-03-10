const tbody = document.getElementById('imageTableBody');
if (tbody) {
    new Sortable(tbody, {
        animation: 150,
        handle: '.order-tab, .table-thumb',
        onEnd: function () {
            updateOrder();
        }
    });

    function updateOrder() {
        const rows = tbody.querySelectorAll('.image-row');
        let data = [];
        rows.forEach((row, idx) => {
            // Update badge number
            const badge = row.querySelector('.order-tab');
            if (badge) badge.textContent = idx + 1;

            row.setAttribute('data-order', idx + 1);
            data.push({
                Bild_ID: row.getAttribute('data-bild-id'),
                order: idx + 1,
                filename: row.querySelector('.filename-input').value,
                source: row.querySelector('.table-thumb').getAttribute('src').replace('uploads/', '')
            });
        });
        fetch('documentplacer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).then(res => res.json())
          .then(json => {
              if (!json.success) alert('Order update failed');
          });
    }
<<<<<<< HEAD
});

(() => {
  const $ = window.jQuery;

  $(function () {
    // Accordion
    $('#imageAccordion').accordion({ exclusive: false });

    const tbody = document.getElementById('imageTableBody');
    if (!tbody) return;

    let sortable = null;
    const $btn = $('.enable-sort');
    const $msg = $('#sortModeMessage');

    // Toggle sorting mode
    $btn.on('click', () => setSort(!$('body').hasClass('sorting-active')));

    function setSort(active) {
      if (active) {
        if (!sortable && window.Sortable) {
          sortable = new Sortable(tbody, {
            animation: 150,
            draggable: 'tr.image-row',
            handle: '.thumb-with-order, .order-tab, .table-thumb',
            onEnd: saveOrder
          });
        }
        $msg.stop(true, true).slideDown(120);
        $('body').addClass('sorting-active');
        $btn.addClass('active').attr('title', 'Avsluta sortering');
      } else {
        if (sortable) { sortable.destroy(); sortable = null; }
        $msg.stop(true, true).slideUp(120);
        $('body').removeClass('sorting-active');
        $btn.removeClass('active').attr('title', 'Sortera');
      }
    }

    function saveOrder() {
      const rows = tbody.querySelectorAll('tr.image-row');
      const payload = [];
      rows.forEach((row, i) => {
        // Update visible number
        const tab = row.querySelector('.order-tab');
        if (tab) tab.textContent = i + 1;

        // Read filename from text span
        const nameSpan = row.querySelector('.filename-text');
        payload.push({
          Bild_ID: row.getAttribute('data-bild-id'),
          order: i + 1,
          filename: nameSpan ? nameSpan.textContent.trim() : ''
        });
      });

      fetch('documentplacer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(j => { if (!j.success) console.warn('Order update failed', j); })
      .catch(console.error);
    }
  });
})();
=======
}

// Make toggleGalleryMenu globally available
window.toggleGalleryMenu = function(titleElem) {
    // Close all other menus
    document.querySelectorAll('.gallery-card-menu.active').forEach(function(menu) {
        if (menu !== titleElem.nextElementSibling) menu.classList.remove('active');
    });
    // Toggle this menu
    const menu = titleElem.nextElementSibling;
    if (!menu) return;
    menu.classList.toggle('active');
    console.log('Toggled menu:', menu, 'Active:', menu.classList.contains('active'));
};

// Clean menu toggle handler for gallery-card-title
function handleGalleryCardTitleClick(e) {
    e.stopPropagation();
    var $card = $(this).closest('.ui.card');
    var $menu = $card.find('.gallery-card-menu').first();
    // Close all other menus
    $('.gallery-card-menu.active').not($menu).removeClass('active');
    // Toggle only this menu
    $menu.toggleClass('active');
    // Debug output
    console.log('Gallery menu toggled:', $menu.get(0), 'Active:', $menu.hasClass('active'));
}

$(document).on('click', '.gallery-card-title', handleGalleryCardTitleClick);

// Close menu when clicking outside
$(window).on('click', function(e) {
    if (!$(e.target).hasClass('gallery-card-title')) {
        $('.gallery-card-menu.active').removeClass('active');
    }
});
>>>>>>> db58316dbdf323f399ce1a7e559857d97cc6cd06
