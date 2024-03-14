document.addEventListener("DOMContentLoaded", function () {
  function ajaxified() {
    let ajaxify = new Ajaxify({
      elements: "#background, #content, #mainnavbar",
      requestDelay: 500,
      bodyClasses: true,
    });

    window.addEventListener("pronto.request", leavePage);
    window.addEventListener("pronto.render", renderPage);
  }

  // if (Ajaxify)
  if (typeof Ajaxify === "function") ajaxified();

  slugify = (text) => {

  };

  //TOC
  (function TOC() {

    let elt = document.getElementById("table-of-contents");
    if (elt === null) return;
    let toc = "";
    headers = document.querySelectorAll("main > h2,main > h3,main > h4");
    if (headers.length < 2) elt.outerHTML = "";
    for (let i = 0; i < headers.length; i++) {
      const h = headers[i];
      const tag = h.tagName.toLowerCase();
      h.id = "toc" + i;



      toc += `<a href="#${h.id}" class="${tag}">${h.innerHTML}</a>\n`;
    }
    elt.innerHTML += toc;
    elt.classList.add("show")
    if (headers.length === 0) document.querySelector(".bd-toc").classList.add("d-none");
  })();

  //Scroll indicator

  let scrolly = document.getElementById("scrolly");
  if (scrolly)
    window.addEventListener("scroll", function (e) {
      const b = document.body; //.querySelector("main");
      const d = document.documentElement;
      let st = d.scrollTop || b.scrollTop;
      let p =
        (((d.scrollTop || b.scrollTop) * 100) /
          (d.scrollHeight - window.innerHeight)) |
        0;

      scrolly.style.width = p + "vw";
    });

  // let elts = document.querySelectorAll("table");
  // if (elts)
  //   elts.forEach((elt) => {
  //     elt.classList.add("table", "table-striped", "table-bordered");
  //   });

  // elts = document.querySelectorAll("thead");
  // if (elts)
  //   elts.forEach((elt) => {
  //     elt.classList.add("table-dark");
  //   });

  const modal = document.getElementById("lightboxModal");
  if (modal) {
    modal.addEventListener("show.bs.modal", (event) => {
      let t = event.relatedTarget; // what triggered the modal

      modal.querySelector(".lightboxContent").innerHTML = t.innerHTML;
    });

    modal.addEventListener("hide.bs.modal", (event) => {
      modal.querySelector(".lightboxContent").innerHTML = "";
    });
    const galleries = document.querySelectorAll("[data-namaskar-gallery]");
    if (modal && galleries)
      galleries.forEach((gallery) => {
        modal.classList.add("gallery");
        console.log;
        let items = gallery.querySelectorAll("[data-namaskar-gallery-item]");
        Namaskar.items = [...items];
        Namaskar.index = 0;
        // items.forEach((item) => {});
      });
    ///// moved from here if(modal)

    modal.querySelector(".arrow.left").addEventListener("click", (e) => {
      e.preventDefault();
      if (Namaskar.index === 0) Namaskar.index = Namaskar.items.length;
      Namaskar.index--;
      modal.querySelector(".lightboxContent").innerHTML =
        Namaskar.items[Namaskar.index].innerHTML;
    });
    modal.querySelector(".arrow.right").addEventListener("click", (e) => {
      e.preventDefault();
      Namaskar.index++;
      if (Namaskar.index === Namaskar.items.length) Namaskar.index = 0;

      modal.querySelector(".lightboxContent").innerHTML =
        Namaskar.items[Namaskar.index].innerHTML;
    });
  }
});


let sidebar = document.getElementById("region-sidebar-first");
let submenus = document.getElementById("submenus");

if (sidebar && submenus) {
  submenus.innerHTML = sidebar.innerHTML;
}

const navSlide = () => {
  const burger = document.getElementById('burger')

  burger.addEventListener('click', () => {
    document.body.classList.toggle('mobile-menu-show')

  })

}

navSlide()

  (function () {


    const html = document.querySelector('html');
    html.setAttribute('data-bs-theme', 'dark');

    const galleryGrid = document.querySelector(".gallery-grid");
    const links = galleryGrid.querySelectorAll("a");
    const imgs = galleryGrid.querySelectorAll("img");
    const lightboxModal = document.getElementById("lightbox-modal");
    const bsModal = new bootstrap.Modal(lightboxModal);
    const modalBody = lightboxModal.querySelector(".lightbox-content");

    function createCaption(caption) {
      return `<div class="carousel-caption d-none d-md-block">
      <h4 class="m-0">${caption}</h4>
    </div>`;
    }

    function createIndicators(img) {
      let markup = "", i, len;

      const countSlides = links.length;
      const parentCol = img.closest('.col');
      const curIndex = [...parentCol.parentElement.children].indexOf(parentCol);

      for (i = 0, len = countSlides; i < len; i++) {
        markup += `
      <button type="button" data-bs-target="#lightboxCarousel"
        data-bs-slide-to="${i}"
        ${i === curIndex ? 'class="active" aria-current="true"' : ''}
        aria-label="Slide ${i + 1}">
      </button>`;
      }

      return markup;
    }

    function createSlides(img) {
      let markup = "";
      const currentImgSrc = img.closest('.gallery-item').getAttribute("href");

      for (const img of imgs) {
        const imgSrc = img.closest('.gallery-item').getAttribute("href");
        const imgAlt = img.getAttribute("alt");

        markup += `
      <div class="carousel-item${currentImgSrc === imgSrc ? " active" : ""}">
        <img class="d-block img-fluid w-100" src=${imgSrc} alt="${imgAlt}">
        ${imgAlt ? createCaption(imgAlt) : ""}
      </div>`;
      }

      return markup;
    }

    function createCarousel(img) {
      const markup = `
    <!-- Lightbox Carousel -->
    <div id="lightboxCarousel" class="carousel slide carousel-fade" data-bs-ride="true">
      <!-- Indicators/dots -->
      <div class="carousel-indicators">
        ${createIndicators(img)}
      </div>
      <!-- Wrapper for Slides -->
      <div class="carousel-inner justify-content-center mx-auto">
        ${createSlides(img)}
      </div>
      <!-- Controls/icons -->
      <button class="carousel-control-prev" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
    `;

      modalBody.innerHTML = markup;
    }

    for (const link of links) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const currentImg = link.querySelector("img");
        const lightboxCarousel = document.getElementById("lightboxCarousel");

        if (lightboxCarousel) {
          const parentCol = link.closest('.col');
          const index = [...parentCol.parentElement.children].indexOf(parentCol);

          const bsCarousel = new bootstrap.Carousel(lightboxCarousel);
          bsCarousel.to(index);
        } else {
          createCarousel(currentImg);
        }

        bsModal.show();
      });
    }

  }());


let Namaskar = { items: [], index: 0 };



