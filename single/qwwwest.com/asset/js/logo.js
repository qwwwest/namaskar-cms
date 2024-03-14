

function qwwwestLogo(id, color = "#ffffdd", bg = "#333355") {

	const canvas = document.getElementById(id);
	const cx = canvas.width / 2;
	const cy = canvas.height / 2;
	const ctx = canvas.getContext('2d');

	const RADIUS_FROM_CENTER = canvas.width / 5;
	const DISTANCE_BETWEEN_CIRCLE = canvas.width / 20;
	const ANGLE = 360 / 35;

	const jj = [0, 1, 1.5, 1.75, 2.5]
	const q = [
		15, 9, 29, 9, 15,
		7, 1, 7, 1, 7,
		7, 1, 7, 1, 7,
		7, 1, 7, 1, 7,
		7, 1, 7, 1, 7,
		7, 1, 7, 4, 7,
		1, 7, 1, 1, 7
	];

	ctx.fillStyle = bg;
	ctx.arc(cx, cy, RADIUS_FROM_CENTER * 2.5, 0, 2 * Math.PI);
	ctx.fill();

	ctx.lineWidth = 0;
	ctx.lineCap = "round";
	ctx.fillStyle = color;




	for (let i = 0; i < 35; i++) {
		for (let j = 0; j < 5; j++)
			if (q[i] & (1 << (j))) blacken(i, j);
	}

	ctx.fill();

	function blacken(i, j) {

		ctx.beginPath();
		let x, y;

		if (i % 5 === 0) i += .5;
		if (i % 5 === 4) i -= .5;

		j = jj[j];
		x = polarX(i * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cx;
		y = polarY(i * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cy;
		ctx.moveTo(x, y);

		x = polarX(i * ANGLE, RADIUS_FROM_CENTER + (j + 1) * DISTANCE_BETWEEN_CIRCLE) + cx;
		y = polarY(i * ANGLE, RADIUS_FROM_CENTER + (j + 1) * DISTANCE_BETWEEN_CIRCLE) + cy;
		ctx.lineTo(x, y);

		x = polarX((i + 1) * ANGLE, RADIUS_FROM_CENTER + (j + 1) * DISTANCE_BETWEEN_CIRCLE) + cx;
		y = polarY((i + 1) * ANGLE, RADIUS_FROM_CENTER + (j + 1) * DISTANCE_BETWEEN_CIRCLE) + cy;
		ctx.lineTo(x, y);

		x = polarX((i + 1) * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cx;
		y = polarY((i + 1) * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cy;
		ctx.lineTo(x, y);

		x = polarX(i * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cx;
		y = polarY(i * ANGLE, RADIUS_FROM_CENTER + j * DISTANCE_BETWEEN_CIRCLE) + cy;
		ctx.lineTo(x, y);

		ctx.fill();






	}


	function polarX(angle, d) { return Math.cos(0.0174532925 * (angle + ANGLE * 6)) * d; }
	function polarY(angle, d) { return Math.sin(0.0174532925 * (angle + ANGLE * 6)) * d; }
}
