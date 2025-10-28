document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('imageTableBody');
    if (!tbody) return;

    new Sortable(tbody, {
        animation: 150,
        handle: '.order-square, .table-thumb',
        onEnd: function () {
            updateOrder();
        }
    });

    function updateOrder() {
        const rows = tbody.querySelectorAll('.image-row');
        let data = [];
        rows.forEach((row, idx) => {
            // Update badge number
            const badge = row.querySelector('.order-square');
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
});