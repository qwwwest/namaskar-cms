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



let Namaskar = { items: [], index: 0 };



