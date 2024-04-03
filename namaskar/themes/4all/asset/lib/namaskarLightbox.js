/*!
 * Lightbox for Bootstrap 5  
*/

class namaskarLightbox {


  links;
  imgs;
  galleryGrid;
  indicators;
  id;
  currentImg;
  currentImgIndex;
  useThumb;

  carousel;
  thumbs;
  total;
  indicators;
  bsCarousel;

  static lightboxModal;
  static bsModal;
  static modalBody;

  static _galleries;

  constructor(galleryGrid, i) {
    this.galleryGrid = galleryGrid;
    this.links = galleryGrid.querySelectorAll("a");
    this.imgs = galleryGrid.querySelectorAll("img");
    this.id = "lightboxCarousel" + i;
    this.currentImg = null;
    this.currentImgIndex = null;
    this.useThumb = true;


    this.links.forEach((link, key) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        this.currentImg = link.querySelector("img");
        this.currentImgIndex = key;
        // const lightboxCarousel = document.getElementById("lightboxCarousel" + i);

        // if (lightboxCarousel) {
        //     const parentCol = link.closest('.col');
        //     const index = [...parentCol.parentElement.children].indexOf(parentCol);

        //     const bsCarousel = new bootstrap.Carousel(lightboxCarousel);
        //     bsCarousel.to(index);
        //     bsCarousel.pause();
        // } else {
        //     createCarousel(currentImg, imgs, links, i);
        // }

        this.createCarousel();
        namaskarLightbox.bsModal.show();


      });

    });

  }

  static createAll() {

    // $('#pfGallery').lightGallery({
    //   mode: 'lg-zoom-in-out',
    //   thumbnail: true,
    //   animateThumb: true,
    //   loop: false,
    //   download: false,
    //   counter: false,
    //   autoplayControls: false,
    //   zoom: false,
    //   share: false,
    //   fullScreen: false,
    // })

    namaskarLightbox._galleries = [];
    namaskarLightbox.lightboxModal = document.getElementById("lightbox-modal");
    namaskarLightbox.bsModal = new bootstrap.Modal(namaskarLightbox.lightboxModal);
    namaskarLightbox.modalBody = namaskarLightbox.lightboxModal.querySelector(".lightbox-content");

    const galleryGrids = document.querySelectorAll(".gallery-grid");
    galleryGrids.forEach((galleryGrid, key) => {
      let nl = new namaskarLightbox(galleryGrid, key);
      namaskarLightbox._galleries.push(nl);

    });

  }

  createCaption(img) {
    let title = img.dataset.title;
    let desc = img.dataset.desc;
    let link = img.dataset.link;
    if (link) title = `<a href="${link}">${title} 🔗 </a>`;
    if (desc) desc = `<p>${desc}</p>`;

    return `<div class="carousel-caption">
          <h4 class="m-0">${title}</h4>${desc}</div>`;
  }

  createIndicators() {
    let markup = "";

    //const parentCol = img.closest('.col');
    //const curIndex = [...parentCol.parentElement.children].indexOf(parentCol);


    if (this.useThumb) for (let i = 0; i < this.links.length; i++) {
      markup += `
            <div class="thumb${i === this.currentImgIndex ? ' active" aria-current="true' : ''}" 
            data-bs-target="#${this.id}" data-bs-slide-to="${i}">
            <img src="${this.links[i].href}" />
            </div>
         `;

    }
    if (!this.useThumb) for (let i = 0; i < this.links.length; i++) {
      markup += `
          <button type="button" data-bs-target="#${this.id}"
            data-bs-slide-to="${i}"
            ${i === this.currentImgIndex ? 'class="active" aria-current="true"' : ''}
            aria-label="Slide ${i + 1}">
          </button>`;
    }

    return markup;
  }

  createSlides() {
    let markup = "";
    //const currentImgSrc = img.closest('.gallery-item').getAttribute("href");
    const currentImgSrc = this.currentImg.closest('.gallery-item').getAttribute("href");

    for (const img of this.imgs) {
      const imgSrc = img.closest('.gallery-item').getAttribute("href");
      const imgAlt = img.getAttribute("alt");

      markup += `
          <div class="carousel-item${currentImgSrc === imgSrc ? " active" : ""}">
            <img class="d-block img-fluid w-100" src=${imgSrc} alt="${imgAlt}">
            ${this.createCaption(img)}
          </div>`;
    }

    return markup;
  }

  // img, imgs, links, i, useThumb = true
  createCarousel() {

    const markup = `
        <!-- Lightbox Carousel -->
        <div id="${this.id}" class="carousel slide carousel-fade" data-bs-ride="false"  data-bs-touch="false" data-bs-interval="false">
          <!-- Indicators/dots -->
          <div class="carousel-indicators${this.useThumb ? ' images' : ''}">
            ${this.createIndicators()}
          </div>
          <!-- Wrapper for Slides -->
          <div class="carousel-inner justify-content-center mx-auto">
            ${this.createSlides()}
          </div>
          <!-- Controls/icons -->
          <button class="carousel-control-prev" type="button" data-bs-target="#${this.id}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#${this.id}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
        `;

    namaskarLightbox.modalBody.innerHTML = markup;
    this.carousel = document.getElementById(this.id);
    this.thumbs = this.carousel.querySelectorAll(".carousel-indicators div.thumb");
    this.total = this.thumbs.length;
    this.indicators = this.carousel.querySelector(".carousel-indicators");
    this.bsCarousel = new bootstrap.Carousel(this.carousel);
    this.bsCarousel.pause();

    this.indicators.style.left = "0px";
    //single image
    if (this.total < 2) { this.indicators.innerHTML = ''; return; }

    if (120 * this.total < document.body.clientWidth) {
      this.indicators.style.left = "unset";
      this.indicators.classList.add('center');
      return;
    }


    this.carousel.addEventListener('slide.bs.carousel', event => {


      this.setIndicators(event.to);

    });

    setTimeout(() => { this.setIndicators() }, 500);



  }


  setIndicators(i = null) {

    if (i === null) i = this.currentImgIndex;
    const imgw = this.indicators.querySelector('div.thumb').getBoundingClientRect().width;
    const indw = imgw * this.total;
    let left = 0;
    console.log(imgw * this.total, document.body.clientWidth);
    if (120 * this.total < document.body.clientWidth) {
      this.indicators.style.left = "unset";
      this.indicators.classList.add('center');
      return;
    }
    this.indicators.classList.remove('center');
    console.log(imgw, indw);
    if (i * imgw + imgw / 2 > document.body.clientWidth / 2) {
      left = document.body.clientWidth / 2 - i * imgw - imgw / 2;
    }
    if (i * imgw + imgw / 2 > indw - document.body.clientWidth / 2) {
      left = document.body.clientWidth - indw;
    }

    this.indicators.style.left = left + "px";


  }


};

