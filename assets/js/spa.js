// spa.js - SPA logic for home/types

document.addEventListener('DOMContentLoaded', function() {
    // SPA navigation
    const mainContainer = document.querySelector('.ui.container');
    if (!mainContainer) return;

    // State
    let imagesByType = window.imagesByType || {};
    let typeCounts = window.typeCounts || {};
    let types = window.types || [];
    let top3types = window.top3types || [];

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function typeCardHtml(type) {
        const imgName = type.toLowerCase().replace(/[^a-z0-9]/g, '') + '.png';
        const imgSrc = `assets/images/${imgName}`;
        const count = typeCounts[type] || 0;
        return `
            <div class="type-card" data-type="${escapeHtml(type)}" role="button" tabindex="0" aria-label="${escapeHtml(type)}, ${count} posts">
                <img src="${imgSrc}" alt="${escapeHtml(type)}" onerror="this.src='assets/images/placeholder.png'">
                <div class="type-card-overlay"></div>
                <div class="type-card-info">
                    <span class="type-card-name">${escapeHtml(type)}</span>
                    <span class="type-card-count">${count} post${count === 1 ? '' : 's'}</span>
                </div>
            </div>
        `;
    }

    // Render Home (Top 3 Types + All Types)
    function renderHome() {
        const totalPosts = Object.values(typeCounts).reduce((a, b) => a + b, 0);
        mainContainer.innerHTML = `
            <section class="page-hero">
                <h1>Welcome${window.username ? ', ' + escapeHtml(window.username) : ''}!</h1>
                <p>${totalPosts} image${totalPosts === 1 ? '' : 's'} across ${types.length} ${types.length === 1 ? 'category' : 'categories'}</p>
            </section>
            <section class="home-section">
                <div class="section-heading">
                    <h2>Top 3 Types</h2>
                    <span class="section-sub">most posted categories</span>
                </div>
                ${top3types.length === 0
                    ? '<div class="empty-state"><i class="images outline icon"></i>No types found yet — upload your first image!</div>'
                    : `<div class="type-grid type-grid-featured">${top3types.map(typeCardHtml).join('')}</div>`
                }
            </section>
            <section class="home-section">
                <div class="section-heading">
                    <h2>All Types</h2>
                </div>
                <div class="type-grid">${types.map(typeCardHtml).join('')}</div>
            </section>
        `;
        // Add click + keyboard handlers
        mainContainer.querySelectorAll('.type-card').forEach(card => {
            const open = () => goToType(card.getAttribute('data-type'));
            card.addEventListener('click', open);
            card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
            });
        });
    }

    // Render Type Page
    function renderTypePage(type) {
        const imgs = imagesByType[type] || [];
        mainContainer.innerHTML = `
            <div class="type-page-header">
                <button class="back-btn" id="backToTypesBtn"><i class="arrow left icon"></i>All types</button>
                <h2>${escapeHtml(type)}</h2>
                <span class="count-chip">${imgs.length} post${imgs.length === 1 ? '' : 's'}</span>
            </div>
            ${imgs.length === 0
                ? '<div class="empty-state"><i class="image outline icon"></i>No posts made for this type yet.</div>'
                : `<div class="photo-grid">
                    ${imgs.map((img, idx) => {
                        // Each card carries its true aspect ratio; the justified
                        // grid derives the card's width from it, so rows share a
                        // height and images are never cropped or distorted.
                        const ratio = (img.width > 0 && img.height > 0) ? img.width / img.height : null;
                        return `
                        <figure class="photo-card${ratio ? '' : ' no-dims'}" data-idx="${idx}"${ratio ? ` style="--ratio:${ratio.toFixed(4)}"` : ''}>
                            <img src="uploads/${img.source}" alt="${escapeHtml(img.title || img.filename)}" class="post-img" data-idx="${idx}" loading="lazy"${ratio ? ` width="${img.width}" height="${img.height}"` : ''}>
                            <figcaption class="photo-card-caption">
                                <div class="photo-card-title">${escapeHtml(img.title || img.filename)}</div>
                                <div class="photo-card-author">by ${escapeHtml(img.username || 'Unknown')}</div>
                            </figcaption>
                        </figure>
                        `;
                    }).join('')}
                </div>`
            }
        `;
        // Attach gallery click handlers after rendering
        const metaArr = imgs.map(img => ({
            title: img.title,
            username: img.username,
            type: img.type,
            download: 'uploads/' + img.source,
            filename: img.filename
        }));
        mainContainer.querySelectorAll('.photo-card').forEach(cardEl => {
            cardEl.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'), 10);
                window.imageGallery.open(imgs.map(i => 'uploads/' + i.source), idx, metaArr);
            });
        });
        // Images without stored dimensions: measure once loaded so the
        // justified grid can size them correctly too.
        mainContainer.querySelectorAll('.photo-card.no-dims .post-img').forEach(imgEl => {
            const applyRatio = () => {
                if (imgEl.naturalWidth && imgEl.naturalHeight) {
                    imgEl.closest('.photo-card').style.setProperty(
                        '--ratio', (imgEl.naturalWidth / imgEl.naturalHeight).toFixed(4)
                    );
                }
            };
            imgEl.complete ? applyRatio() : imgEl.addEventListener('load', applyRatio);
        });
        mainContainer.querySelector('#backToTypesBtn').addEventListener('click', goHome);
    }

    // Hash routing: '#type/<name>' renders a type page, anything else renders
    // home. Keeps the browser back button working and makes views linkable.
    function goToType(type) {
        location.hash = 'type/' + encodeURIComponent(type);
    }

    function goHome() {
        location.hash = '';
    }

    function route() {
        const match = location.hash.match(/^#type\/(.+)$/);
        let type = null;
        if (match) {
            // A hand-edited or truncated URL can contain a malformed escape
            // ('#type/%'), which makes decodeURIComponent throw. Falling back to
            // home beats leaving the page blank on an uncaught URIError.
            try {
                type = decodeURIComponent(match[1]);
            } catch (e) {
                type = null;
            }
        }
        // Gate on the type being known, not on it having images, so a shared
        // link to a now-empty type still renders its empty state.
        if (type && types.includes(type)) {
            renderTypePage(type);
        } else {
            renderHome();
        }
        window.scrollTo(0, 0);
    }

    window.addEventListener('hashchange', route);

    // Initial render
    route();
});
