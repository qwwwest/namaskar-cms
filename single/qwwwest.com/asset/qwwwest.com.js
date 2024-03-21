

function initSkills(init) {
	if (!init) return;
	puzzle(init);

	setTimeout(matrix, 0);

}

function initHome(init) {

	if (!init) return;

	new Vivus('qwwwest-svg', {
		duration: 150, file: absroot + "/media/img/qwwwest.svg",
		onReady: function (myVivus) {
			// `el` property is the SVG element
			//	myVivus.el.setAttribute('height', '600px');
			//myVivus.el.setAttribute('width', '600px');
		}
	});



	setTimeout(function () {

		new Vivus('hello-svg', {
			duration: 120, file: absroot + "/media/img/hello1.svg",
			onReady: function (myVivus) {
				// `el` property is the SVG element
				//	myVivus.el.setAttribute('height', '400px');
				//	myVivus.el.setAttribute('width', '600px');


			}
		})
	}, 2000);

	setTimeout(starfield, 100);

}

function puzzle(init) {

	if (!init) {

	}
	let pos = []; //0, 1, 2, ... 32;
	for (let i = 0; i < 25; i++) {
		pos[i] = i;
	}
	// 0 UP, 1 RIGHT, 2 DOWN, 3 LEFT
	let move = [{ 'top': '-=20%' }, { 'left': '+=20%' }, { 'top': '+=20%' }, { 'left': '-=20%' },
	{ 'left': '+=0px' }];

	let elt = pos.length - 1;
	let omv = -1;
	for (i = 0; i < pos.length; i++) {
		$('#skills .thumbskill:eq(' + i + ')')
			.delay(500)
			.css({ opacity: 0 })
			.delay(i * 50)
			.attr('id', 'skill_' + i)
			.animate({ opacity: 1 }, 500);


	}

	setTimeout(loopskills, 2000);

	function loopskills() {

		let mv = Math.floor(Math.random() * 4);

		let oelt = elt;
		let x = elt % 5;
		let y = Math.floor(elt / 5);

		// 0 UP, 1 LEFT, 2 DOWN, 3 RIGHT
		if (mv == 0 && omv != 2 && y < 3) elt += 5; //up
		if (mv == 1 && omv != 3 && x > 0) elt -= 1; // left
		if (mv == 2 && omv != 0 && y > 0) elt -= 5; // down
		if (mv == 3 && omv != 1 && x < 4) elt += 1; // right
		if (oelt == elt) { loopskills(); return; } // no move... we start again.
		omv = mv;
		pos[oelt] = pos[elt];


		$('#skills .thumbskill:eq(' + pos[elt] + ')')
			.delay(50).animate(move[mv], 200, loopskills);

	}
}



function matrix() {

	let c = document.getElementById("matrix");
	if (c === null) return;
	let ctx = c.getContext("2d");

	c.height = window.innerHeight;
	c.width = window.innerWidth;
	let txts = "qwwwestQWWWEST";
	txts = txts.split("");
	let font_size = 16;
	let columns = c.width / font_size;
	let drops = [];
	let colors = ['#FFF', '#eee', '#ddd', '#fff', '#aaa', '#bbb',];
	for (let x = 0; x < columns; x++) drops[x] = 1;


	let interval = setInterval(draw, 30);
	function draw() {

		// self removing on page change.
		if (document.getElementById("matrix") === null) {
			clearInterval(interval);
		}
		// 	OPACITY
		ctx.globalAlpha = .4;
		ctx.fillStyle = "rgba(0, 0, 0, 0.05)";
		ctx.fillRect(0, 0, c.width, c.height);


		ctx.fillStyle = "#0F3";
		ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)];
		ctx.font = font_size + "px arial";
		for (let i = 0; i < drops.length; i++) {
			let text = txts[Math.floor(Math.random() * txts.length)];
			ctx.fillText(text, i * font_size, drops[i] * font_size);
			if (drops[i] * font_size > c.height || Math.random() > 0.98) drops[i] = 0;
			drops[i]++;
		}
	}

}


function qwwwestLogoSmall(id = "qwwwestLogo2", color = "#ffffdd", bg = "#00000000") {

	const canvas = document.getElementById(id);

	//	const ctx = canvas.getContext('2d');
	var ctx = new C2S(100, 100);
	const cx = 50; //canvas.width / 2;
	const cy = cx;


	const R = cx / 2;
	const XR = R / 5;
	const D = 360 / 42;
	const DR = 2 * Math.PI / 42;


	const q = [4, 2, 1, 1, - 1, 2, -4, -5];
	const q2 = [2, 1, -1, 1, 1, 1, -2, -3];
	const w = [3, 1, -2, 1, 2, 1, -2, 1, 2, 1, -3, -5];
	const s = [3, 1, -2, 1, 2, 3, -3, -1, 2, -1, -2, -3];
	const t = [1, 2, 2, 1, -2, 1, 1, 1, -2, -5];


	let step = 8;

	draw(q, color, 0, -1);
	draw(q2, color, 1, 0);
	step += 6;

	while (step < 33) {
		draw(w, color);
		step += 6;
	}

	draw(s, color);
	step += 6;
	draw(t, color);

	document.getElementById("qLogo").innerHTML = ctx.getSerializedSvg(true);

	console.log(ctx.getSerializedSvg(true));

	function draw(a, color, xx = 0, yy = 0) {
		let x, y;
		ctx.fillStyle = color;
		ctx.strokeStyle = color;
		ctx.lineWidth = 2;
		ctx.lineCap = "round";
		ctx.beginPath();
		x = polarX((step + xx) * D, R + yy * XR) + cx;
		y = polarY((step + xx) * D, R + yy * XR) + cy;
		ctx.moveTo(x, y);
		for (let i = 0; i < a.length; i++) {
			if (i % 2) {
				ctx.arc(cx, cy, R + yy * XR, (step + xx) * DR, (step + xx + a[i]) * DR, a[i] < 0);
				xx += a[i];

			}
			else {
				yy += a[i];
				x = polarX((step + xx) * D, R + yy * XR) + cx;
				y = polarY((step + xx) * D, R + yy * XR) + cy;
				ctx.lineTo(x, y);
			}

		}

		ctx.stroke();

	}

	function polarX(angle, d) { return Math.cos(0.0174532925 * (angle)) * d; }
	function polarY(angle, d) { return Math.sin(0.0174532925 * (angle)) * d; }
}


function qwwwestLogo(id = "qwwwestLogo", color = "#ffffdd", bg = "#00000000") {

	const canvas = document.getElementById(id);

	//	const ctx = canvas.getContext('2d');
	var ctx = new C2S(400, 400);
	const cx = 200; //canvas.width / 2;
	const cy = cx;


	const R = cx / 2;
	const XR = R / 5;
	const D = 360 / 42;
	const DR = 2 * Math.PI / 42;


	const q = [4, 2, 1, 1, - 1, 2, -4, -5];
	const q2 = [2, 1, -1, 1, 1, 1, -2, -3];
	const w = [3, 1, -2, 1, 2, 1, -2, 1, 2, 1, -3, -5];
	const s = [3, 1, -2, 1, 2, 3, -3, -1, 2, -1, -2, -3];
	const t = [1, 2, 2, 1, -2, 1, 1, 1, -2, -5];

	// ctx.fillStyle = bg;
	// ctx.arc(cx, cy, cx, 0, 42 * DR);
	// ctx.fill();

	let step = 8;

	draw(q, color, 0, -1);
	draw(q2, color, 1, 0);
	step += 6;

	while (step < 33) {
		draw(w, color);
		step += 6;
	}

	draw(s, color);
	step += 6;
	draw(t, color);

	document.getElementById("qLogo").innerHTML = ctx.getSerializedSvg(true);



	function draw(a, color, xx = 0, yy = 0) {
		let x, y;
		ctx.fillStyle = color;
		ctx.strokeStyle = color;
		ctx.lineWidth = 2;
		ctx.lineCap = "round";
		ctx.beginPath();
		x = polarX((step + xx) * D, R + yy * XR) + cx;
		y = polarY((step + xx) * D, R + yy * XR) + cy;
		ctx.moveTo(x, y);
		for (let i = 0; i < a.length; i++) {
			if (i % 2) {
				ctx.arc(cx, cy, R + yy * XR, (step + xx) * DR, (step + xx + a[i]) * DR, a[i] < 0);
				xx += a[i];

			}
			else {
				yy += a[i];
				x = polarX((step + xx) * D, R + yy * XR) + cx;
				y = polarY((step + xx) * D, R + yy * XR) + cy;
				ctx.lineTo(x, y);
			}

		}

		ctx.stroke();

	}

	function polarX(angle, d) { return Math.cos(0.0174532925 * (angle)) * d; }
	function polarY(angle, d) { return Math.sin(0.0174532925 * (angle)) * d; }
}


function initPortfolio() {

	var entries = [
	];


	var settings = {

		entries: entries,
		width: 600,
		height: 600,
		radius: '10%',
		radiusMin: 75,
		bgDraw: true,
		bgColor: 'transparent',
		opacityOver: 1.00,
		opacityOut: 0.05,
		opacitySpeed: 6,
		fov: 800,
		speed: 1,
		fontFamily: 'Audiowide',
		fontSize: '32',
		fontColor: '#fff',
		fontWeight: 'normal',//bold
		fontStyle: 'normal',//italic 
		fontStretch: 'normal',//wider, narrower, ultra-condensed, extra-condensed, condensed, semi-condensed, semi-expanded, expanded, extra-expanded, ultra-expanded
		fontToUpperCase: true,
		tooltipFontFamily: 'Oswald, Arial, sans-serif',
		tooltipFontSize: '11',
		tooltipFontColor: '#fff',
		tooltipFontWeight: 'normal',//bold
		tooltipFontStyle: 'normal',//italic 
		tooltipFontStretch: 'normal',//wider, narrower, ultra-condensed, extra-condensed, condensed, semi-condensed, semi-expanded, expanded, extra-expanded, ultra-expanded
		tooltipFontToUpperCase: false,
		tooltipTextAnchor: 'left',
		tooltipDiffX: 0,
		tooltipDiffY: 10

	};

	$('#pfGallery').lightGallery({
		mode: 'lg-zoom-in-out',
		thumbnail: true,
		animateThumb: true,
		loop: false,
		download: false,
		counter: false,
		autoplayControls: false,
		zoom: false,
		share: false,
		fullScreen: false,
	})

};

let stars = [];

function starfield() {

	const COLOR_SPACE = "#00000000";
	const COLOR_STARS = "white";
	const STAR_NUM = 300; // number of stars 
	const STAR_SIZE = 0.002; // max star size as a fraction of screen width
	const STAR_SPEED = 0.01; // fraction of screen width per second


	// set up the canvas and context
	//var canvas = document.createElement("canvas");

	let canvas = document.getElementById("stars");
	if (canvas === null) alert('nope'); //return;
	let ctx = canvas.getContext("2d");
	canvas.height = document.documentElement.clientHeight;
	canvas.width = document.documentElement.clientWidth;
	//document.body.appendChild(canvas);


	// set up the stars

	let starSpeed = STAR_SPEED * canvas.width;
	let xv = starSpeed * randomSign() * Math.random();
	// Using Pythagoras' theorem, yv = sqrt(starSpeed^2 - xv^2)
	let yv = Math.sqrt(Math.pow(starSpeed, 2)
		- Math.pow(xv, 2)) * randomSign();

	//change direction onClick
	function plop() {
		xv = starSpeed * randomSign() * Math.random();
		yv = Math.sqrt(Math.pow(starSpeed, 2)
			- Math.pow(xv, 2)) * randomSign();
		for (let i = 0; i < STAR_NUM; i++) {
			let speedMult = Math.random() * 1.5 + 0.5;
			stars[i].xv = xv * speedMult;
			stars[i].yv = yv * speedMult;


		}
	}
	//window.addEventListener("click", plop);
	//first time
	if (stars.length === 0)
		for (let i = 0; i < STAR_NUM; i++) {
			let speedMult = Math.random() * 1.5 + 0.5;
			stars[i] = {
				r: Math.random() * STAR_SIZE * canvas.width / 2,
				x: Math.floor(Math.random() * canvas.width),
				y: Math.floor(Math.random() * canvas.height),
				xv: xv * speedMult,
				yv: yv * speedMult
			}
		}

	// set up the animation loop
	let timeDelta = 0, timeLast = 0, counter = 0;
	let globalID = requestAnimationFrame(loop);

	function loop(timeNow) {


		if (document.getElementById("stars") === null) {

			cancelAnimationFrame(globalID);
			timeDelta = 0;
			stars = [];

			return;
		}

		timeDelta = timeNow - timeLast;
		if (timeDelta > 100) {
			timeLast = timeNow;
			timeDelta = 0;
		}



		timeLast = timeNow;


		counter++;
		//	if (counter % 100 === 0) plop();

		// space background
		ctx.fillStyle = COLOR_SPACE;
		ctx.fillRect(0, 0, canvas.width, canvas.height);

		// draw the stars
		ctx.fillStyle = COLOR_STARS;
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		for (let i = 0; i < STAR_NUM; i++) {
			ctx.beginPath();
			ctx.arc(stars[i].x, stars[i].y, stars[i].r, 0, Math.PI * 2);
			ctx.fill();

			// update the star's x position
			stars[i].x += stars[i].xv * timeDelta * 0.001;

			// reposition the star to the other side if it goes off screen
			if (stars[i].x < 0 - stars[i].r) {
				stars[i].x = canvas.width + stars[i].r;
			} else if (stars[i].x > canvas.width + stars[i].r) {
				stars[i].x = 0 - stars[i].r;
			}

			// update the star's y position
			stars[i].y += stars[i].yv * timeDelta * 0.001;

			// reposition the star to the other side if it goes off screen
			if (stars[i].y < 0 - stars[i].r) {
				stars[i].y = canvas.height + stars[i].r;
			} else if (stars[i].y > canvas.height + stars[i].r) {
				stars[i].y = 0 - stars[i].r;
			}
		}

		// call the next frame
		globalID = requestAnimationFrame(loop);
	}

	function randomSign() {
		return Math.random() >= 0.5 ? 1 : -1;
	}

}

