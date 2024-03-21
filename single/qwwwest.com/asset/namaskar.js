

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


}

let timeoutID = null;
// first time when reaching the site.
renderPage();


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


	//


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


}



if (Ajaxify) ajaxified();



