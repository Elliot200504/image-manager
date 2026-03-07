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