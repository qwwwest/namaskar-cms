document.addEventListener("DOMContentLoaded", function () {

  function renderPage() {
    document.getElementById("backgrounds").classList.remove('zoomOut');
    document.getElementById("content").classList.remove('fadeOut');

    document.getElementById("backgrounds").classList.add('ZoomIn');
    document.getElementById("content").classList.add('fadeIn');
    setTimeout(removeClasses, 900);
    console.log("absroot=" + absroot);


    let init = document.querySelectorAll('[data-namaskar-init]');

    for (let i = 0; i < init.length; i++) {
      const element = init[i];
      let fn = window[element.dataset.namaskarInit];
      if (typeof fn === 'function') fn(true);
      else { console.log(element.dataset.namaskarInit + " function not found.") }

    }

    namaskarLightbox.createAll();


  }

  function leavePage() {
    document.getElementById("backgrounds").classList.add('zoomOut');
    document.getElementById("content").classList.add('fadeOut');

    let init = document.querySelectorAll('[data-namaskar-init]');

    for (let i = 0; i < init.length; i++) {
      const element = init[i];
      let fn = window[element.dataset.namaskarInit];
      if (typeof fn === 'function') fn(false);
      else { console.log(element.dataset.namaskarInit + " function not found.") }

    }

  }

  function removeClasses() {

    document.getElementById("backgrounds").classList.remove('ZoomIn');
    document.getElementById("content").classList.remove('fadeIn');

  }

  window.onresize = () => {
    if (document.body.classList.contains("sideMenuOpen")) toggleSideMenu();
  };

  function ajaxified() {

    let ajaxify = new Ajaxify({
      elements: '#background, #content, #navbarCollapse, #language-menu',
      requestDelay: 500,
      bodyClasses: true
    });

    window.addEventListener("pronto.request", leavePage);
    window.addEventListener("pronto.render", renderPage);
    window.addEventListener("pronto.request", function (e) {
      //close menu programatically...

      bootstrap.Collapse.getOrCreateInstance(
        document.getElementById('navbarCollapse'), {
        toggle: false
      });


      document.getElementById('burger').classList.add('collapsed');
      document.getElementById('burger').attributes.item('aria-expanded', 'false');
    });

    let timeoutID = null;
    // first time when reaching the site.
    renderPage();
  }


  // if (Ajaxify)
  if (typeof Ajaxify === "function") ajaxified();
  else {
    namaskarLightbox.createAll();
  }

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

//navSlide();


let Namaskar = { items: [], index: 0 };


if (document.querySelector('.hero .bgcover')) {
  document.body.onscroll = function myFunction() {
    const factor = .5;
    const scrolltotop = document.scrollingElement.scrollTop;
    const bgcover = document.getElementsByClassName('bgcover')

    const scollY = scrolltotop * factor | 0;
    const nav = document.querySelector('nav.navbar');

    for (let i = 0; i < bgcover.length; i++) {
      bgcover[i].style.backgroundPosition = "center " + scollY + "px";

    }

    if (document.body.scrollTop >= 200 || document.documentElement.scrollTop >= 200) {
      nav.classList.add("nav-colored");
      console.log(document.documentElement.scrollTop)

    }
    else {

      nav.classList.remove("nav-colored");
    }
  }


}




