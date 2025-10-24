document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('imageGrid');
    if (!grid) return;

    new Sortable(grid, {
        animation: 150,
        ghostClass: 'dragging',
        onEnd: function () {
            updateOrder();
        }
    });

    function updateOrder() {
        const items = grid.querySelectorAll('.image-item');
        let data = [];
        items.forEach((item, idx) => {
            item.querySelector('.order-badge').textContent = idx + 1;
            item.setAttribute('data-order', idx + 1);
            data.push({
                Bild_ID: item.getAttribute('data-bild-id'),
                order: idx + 1,
                filename: item.querySelector('img').getAttribute('src').replace('uploads/', '')
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