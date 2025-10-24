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

    // Listen for filename changes
    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('filename-input')) {
            const bildId = e.target.getAttribute('data-bild-id');
            const newFilename = e.target.value.trim();
            fetch('documentplacer.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'rename', Bild_ID: bildId, filename: newFilename })
            })
            .then(res => res.json())
            .then(json => {
                if (!json.success) alert('Kunde inte byta namn!');
            });
        }
    });

    function updateOrder() {
        const rows = tbody.querySelectorAll('.image-row');
        let data = [];
        rows.forEach((row, idx) => {
            row.querySelector('.order-square').textContent = idx + 1;
            row.setAttribute('data-order', idx + 1);
            data.push({
                Bild_ID: row.getAttribute('data-bild-id'),
                order: idx + 1,
                filename: row.querySelector('.filename-input').value
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