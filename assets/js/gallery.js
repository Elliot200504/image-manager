/**
 * Simple Image Gallery Component
 * Allows browsing through images with navigation
 */
class ImageGallery {
  constructor() {
    this.images = [];
    this.currentIndex = 0;
    this.$modal = null;
    this.init();
  }

  init() {
    this.createModal();
    this.bindEvents();
  }

  createModal() {
    // Create modal HTML
    this.$modal = $(`
      <div id="imageGalleryModal" class="ui fullscreen basic modal">
        <div class="image content">
          <div id="galleryImageContainer" style="text-align:center;">
            <img id="galleryImage" src="" style="max-width:90vw;max-height:80vh;border-radius:12px;" />
          </div>
          <div id="galleryCounter" style="margin-top:1em;"></div>
          <button id="galleryPrev" class="ui icon button"><i class="angle left icon"></i></button>
          <button id="galleryNext" class="ui icon button"><i class="angle right icon"></i></button>
          <button id="galleryClose" class="ui icon button" style="position:absolute;top:1em;right:1em;"><i class="close icon"></i></button>
        </div>
      </div>
    `);
    $('body').append(this.$modal);
  }

  bindEvents() {
    this.$modal.find('#galleryPrev').on('click', () => this.previousImage());
    this.$modal.find('#galleryNext').on('click', () => this.nextImage());
    this.$modal.find('#galleryClose').on('click', () => this.close());
    $(document).on('keydown', (e) => {
      if (!this.$modal.hasClass('active')) return;
      if (e.key === 'ArrowLeft') this.previousImage();
      if (e.key === 'ArrowRight') this.nextImage();
      if (e.key === 'Escape') this.close();
    });
  }

  open(images, startIndex = 0, meta = null) {
    this.images = images;
    this.currentIndex = startIndex;
    this.meta = meta || [];
    this.updateDisplay();
    this.$modal.modal('show');
  }

  close() {
    this.$modal.modal('hide');
  }

  previousImage() {
    if (this.currentIndex > 0) {
      this.currentIndex--;
      this.updateDisplay();
    }
  }

  nextImage() {
    if (this.currentIndex < this.images.length - 1) {
      this.currentIndex++;
      this.updateDisplay();
    }
  }

  updateDisplay() {
    const src = this.images[this.currentIndex];
    this.$modal.find('#galleryImage').attr('src', src);
    this.$modal.find('#galleryCounter').text(
      `${this.currentIndex + 1} / ${this.images.length}`
    );
        // Custom overlay
        let overlay = '';
        if (this.meta && this.meta[this.currentIndex]) {
          const m = this.meta[this.currentIndex];
          overlay = `
            <div id="galleryOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;">
              <div class="gallery-info-top">
                <div class="gallery-title">${m.title || '<span style=\"color:#eee;\">No title</span>'}</div>
                <div class="gallery-author">by ${m.username || 'Unknown'}</div>
              </div>
              <div class="gallery-info-bottom">
                <a href="${m.download}" download="${m.filename}" class="ui button gallery-download">Download <i class="download icon"></i></a>
                <div class="gallery-type">Type: ${m.type || 'Unknown'}</div>
              </div>
            </div>
          `;
    }
    this.$modal.find('#galleryOverlay').remove();
    this.$modal.find('#galleryImageContainer').css('position', 'relative');
    this.$modal.find('#galleryImageContainer').append(overlay);
  }
}

// Initialize gallery globally
$(document).ready(() => {
  window.imageGallery = new ImageGallery();
});

function initiateImageGallery() {
  $('#btnGallery').on('click', function (e) {
    e.preventDefault();

    // ...existing code...

    if (window.imageGallery) {
      window.imageGallery.open(images, 0);
    }
  });

  // Show/hide gallery button based on image count
  function updateGalleryButtonVisibility() {
    const imageCount = $(
      '#imglist img, #imglist a[href*=".jpg"], #imglist a[href*=".jpeg"], #imglist a[href*=".png"], #imglist a[href*=".gif"]'
    ).length;

    if (imageCount > 0) {
      $('#btnGallery').show();
    } else {
      $('#btnGallery').hide();
    }
  }

  updateGalleryButtonVisibility();

  const observer = new MutationObserver(function (mutations) {
    updateGalleryButtonVisibility();
  });

  const imgList = document.getElementById('imglist');
  if (imgList) {
    observer.observe(imgList, {
      childList: true,
      subtree: true,
    });
  }
}
