function toggleSideMenu() {
	// document.querySelector(" .hamburger").classList.toggle('active');
	document.body.classList.toggle("sideMenuOpen"); // //
	//    document.getElementById('aside').style.width = '250px'; // document.getElementById('main').style.marginLeft='250px' ;
}
window.onresize = () => {
	if (document.body.classList.contains("sideMenuOpen")) toggleSideMenu();
};

function ajaxified() {

	let ajaxify = new Ajaxify({
		elements: '#background, #content, #mainnavbar, #language-menu',
		requestDelay: 500,
		bodyClasses: true
	});

	window.addEventListener("pronto.request", leavePage);
	window.addEventListener("pronto.render", renderPage);

}


if (Ajaxify) ajaxified();

