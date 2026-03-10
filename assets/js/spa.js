// spa.js - SPA logic for home/types

document.addEventListener('DOMContentLoaded', function() {
    // SPA navigation
    const mainContainer = document.querySelector('.ui.container');
    if (!mainContainer) return;

    // State
    let currentView = 'home';
    let imagesByType = window.imagesByType || {};
    let typeCounts = window.typeCounts || {};
    let types = window.types || [];
    let top3types = window.top3types || [];

    // Render Home (Top 3 Types + All Types)
    function renderHome() {
        mainContainer.innerHTML = `
            <h2 class="ui header">Welcome${window.username ? ', ' + window.username : ''}!</h2>
            <div class="ui segment">
                <h3 class="ui header">Top 3 Types</h3>
                <div class="ui three stackable cards" id="typeCards">
                    ${top3types.length === 0 ? '<div class="ui message">No types found.</div>' :
                        top3types.map(type => {
                            const imgName = type.toLowerCase().replace(/[^a-z0-9]/g, '') + '.png';
                            const imgSrc = `assets/images/${imgName}`;
                            return `
                                <div class="ui card type-card modern-card" data-type="${type}" style="cursor:pointer;">
                                    <div class="image card-image-bg">
                                        <img src="${imgSrc}" alt="Type ${type}" class="card-type-img" onerror="this.src='assets/images/placeholder.png'">
                                        <div class="gallery-card-title card-title">${type}</div>
                                    </div>
                                    <div class="extra content card-content">
                                        <div class="card-post-count">Posts: ${typeCounts[type] || 0}</div>
                                    </div>
                                </div>
                            `;
                        }).join('')
                    }
                </div>
                <h3 class="ui header" style="margin-top:2em;">All Types</h3>
                <div class="ui four stackable cards" id="allTypeCards">
                    ${types.map(type => {
                        const imgName = type.toLowerCase().replace(/[^a-z0-9]/g, '') + '.png';
                        const imgSrc = `assets/images/${imgName}`;
                        return `
                            <div class="ui card type-card modern-card all-type-card" data-type="${type}" style="cursor:pointer;">
                                <div class="image card-image-bg">
                                    <img src="${imgSrc}" alt="Type ${type}" class="card-type-img" onerror="this.src='assets/images/placeholder.png'">
                                    <div class="gallery-card-title card-title">${type}</div>
                                </div>
                                <div class="extra content card-content">
                                    <div class="card-post-count">Posts: ${typeCounts[type] || 0}</div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
        // Add click handlers
        mainContainer.querySelectorAll('.type-card').forEach(card => {
            card.addEventListener('click', function() {
                renderTypePage(this.getAttribute('data-type'));
            });
        });
    }

    // Render Type Page
    function renderTypePage(type) {
        const imgs = imagesByType[type] || [];
        mainContainer.innerHTML = `
            <div class="ui segment">
                <h3 class="ui header">Images for type: ${type}</h3>
                <div class="image-grid">
                    ${imgs.length === 0 ? '<div class="ui message">No posts made for this type.</div>' :
                        imgs.map((img, idx) => `
                            <div class="image-grid-item">
                                <img src="uploads/${img.source}" alt="${img.title || img.filename}" class="post-img" data-idx="${idx}">
                            </div>
                        `).join('')
                    }
                </div>
                <button class="ui button" id="backToTypesBtn" style="margin-top:1em;">Back to Types</button>
            </div>
        `;
        // Attach gallery click handlers after rendering
        const metaArr = imgs.map(img => ({
            title: img.title,
            username: img.username,
            download: 'uploads/' + img.source,
            filename: img.filename
        }));
        mainContainer.querySelectorAll('.post-img').forEach(imgEl => {
            imgEl.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'), 10);
                window.imageGallery.open(imgs.map(i => 'uploads/' + i.source), idx, metaArr);
            });
        });
        mainContainer.querySelector('#backToTypesBtn').addEventListener('click', renderHome);
    }

    // Initial render
    renderHome();
});
