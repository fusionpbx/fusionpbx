/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

function create_arrow(direction, color) {
	switch (direction) {
		case 'inbound':
			arrow = create_arrow_inbound(color);
			break;
		case 'outbound':
			arrow = create_arrow_outbound(color);
			break;
		case 'local':
			arrow = create_arrow_local(color);
			break;
		case 'voicemail':
			arrow = create_voicemail_icon(color);
			break;
		case 'missed':
			arrow = create_inbound_missed(color);
			break;
	}
	return arrow;
}

function create_arrow_outbound(color, gridSize = 25) {
	// Create SVG from SVG Namespace
	const SVG_NS = "http://www.w3.org/2000/svg";
	const svg = document.createElementNS(SVG_NS, "svg");

	// compute how much to scale the original 24-unit grid
	const scale = gridSize / 25;

	// Set color
	svg.setAttribute("stroke", color);
	// Set brush width
	svg.setAttribute("width", gridSize);
	svg.setAttribute("height", gridSize);
	svg.setAttribute("viewBox", `0 0 ${gridSize} ${gridSize}`);
	svg.setAttribute("fill", "none");
	svg.setAttribute("stroke-width", 2 * scale);
	svg.setAttribute("stroke-linecap", "round");

	// Create line
	const line = document.createElementNS(SVG_NS, "line");
	line.setAttribute("x1", (4 * scale).toString());
	line.setAttribute("y1", (20 * scale).toString());
	line.setAttribute("x2", (20 * scale).toString());
	line.setAttribute("y2", (4 * scale).toString());
	svg.appendChild(line);

	// Create the arrow head (a right-angle triangle)
	const head = document.createElementNS(SVG_NS, "polygon");
//		head.setAttribute("points", "20,4 10,9 15,14");
	head.setAttribute("points", [[20, 4], [10, 9], [15, 14]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
			);
	head.setAttribute("fill", color);
	svg.appendChild(head);
	return svg;
}

function create_arrow_inbound(color, gridSize = 25) {
	const SVG_NS = "http://www.w3.org/2000/svg";
	const svg = document.createElementNS(SVG_NS, "svg");

	// compute how much to scale the original 24-unit grid
	const scale = gridSize / 25;

	// size and viewport
	svg.setAttribute("width", gridSize);
	svg.setAttribute("height", gridSize);
	svg.setAttribute("viewBox", `0 0 ${gridSize} ${gridSize}`);
	svg.setAttribute("fill", "none");
	svg.setAttribute("stroke", color);
	svg.setAttribute("stroke-width", 2 * scale);
	svg.setAttribute("stroke-linecap", "round");

	// scaled line from (4,4) → (20,20)
	const line = document.createElementNS(SVG_NS, "line");
	line.setAttribute("x1", (4 * scale).toString());
	line.setAttribute("y1", (4 * scale).toString());
	line.setAttribute("x2", (20 * scale).toString());
	line.setAttribute("y2", (20 * scale).toString());
	svg.appendChild(line);

	// scaled triangle head: (20,20), (10,15), (15,10)
	const head = document.createElementNS(SVG_NS, "polygon");
	head.setAttribute("points", [[20, 20], [10, 15], [15, 10]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
	);
	head.setAttribute("fill", color);
	svg.appendChild(head);

	return svg;
}

function create_arrow_local(color, gridSize = 25) {
	const SVG_NS = "http://www.w3.org/2000/svg";
	const svg = document.createElementNS(SVG_NS, "svg");

	// compute how much to scale the original 25-unit grid
	const scale = gridSize / 25;

	// sizing & styling
	svg.setAttribute("width", gridSize);
	svg.setAttribute("height", gridSize);
	svg.setAttribute("viewBox", `0 0 ${gridSize} ${gridSize}`);
	svg.setAttribute("fill", "none");
	svg.setAttribute("stroke", color);
	svg.setAttribute("stroke-width", 2 * scale);
	svg.setAttribute("stroke-linecap", "round");

	// shaft
	const line = document.createElementNS(SVG_NS, "line");
	line.setAttribute("x1", 6 * scale);
	line.setAttribute("y1", 12 * scale);
	line.setAttribute("x2", 18 * scale);
	line.setAttribute("y2", 12 * scale);
	svg.appendChild(line);

	// left arrow head
	const leftHead = document.createElementNS(SVG_NS, "polygon");
	leftHead.setAttribute("points", [[6,8], [2,12], [6,16]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
	);
	leftHead.setAttribute("fill", color);
	svg.appendChild(leftHead);

	// right arrow head
	const rightHead = document.createElementNS(SVG_NS, "polygon");
	rightHead.setAttribute("points", [[18,8], [22,12], [18,16]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
	);
	rightHead.setAttribute("fill", color);
	svg.appendChild(rightHead);

	return svg;
}

function create_inbound_missed(color, gridSize = 25) {
	const SVG_NS = "http://www.w3.org/2000/svg";
	const svg = document.createElementNS(SVG_NS, "svg");

	// compute how much to scale the original 25-unit grid
	const scale = gridSize / 25;

	// size and viewport
	svg.setAttribute("width", gridSize);
	svg.setAttribute("height", gridSize);
	svg.setAttribute("viewBox", `0 0 ${gridSize} ${gridSize}`);
	svg.setAttribute("fill", "none");
	svg.setAttribute("stroke", color);
	svg.setAttribute("stroke-width", 2 * scale);
	svg.setAttribute("stroke-linecap", "round");

	// 5. Reflective bounce polyline
	const bounce = document.createElementNS(SVG_NS, 'polyline');
	bounce.setAttribute('points', [[4, 4], [12, 12], [20, 4]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
			);
	bounce.setAttribute('stroke', color);
	bounce.setAttribute('stroke-width', 2 * scale);
	bounce.setAttribute('fill', 'none');
	bounce.setAttribute('stroke-linecap', 'round');
	bounce.setAttribute('marker-end', 'url(#arrowhead)');
	svg.appendChild(bounce);

	// scaled triangle head: tip[20,4], left wing[17,5], right wing[19, 7]
	const head = document.createElementNS(SVG_NS, "polygon");
	head.setAttribute("points", [[20, 4], [17, 5], [19, 7]]
			.map(([x, y]) => `${x * scale},${y * scale}`).join(" ")
			);
	head.setAttribute("fill", color);
	svg.appendChild(head);

	// Left earpiece
	const left = document.createElementNS(SVG_NS, 'ellipse');
	left.setAttribute("cx", 4 * scale);
	left.setAttribute("cy", 17 * scale);
	left.setAttribute("rx", 2 * scale);
	left.setAttribute("ry", 1 * scale);
	left.setAttribute("fill", color);
	svg.appendChild(left);

	// Right earpiece
	const right = document.createElementNS(SVG_NS, 'ellipse');
	right.setAttribute("cx", 18 * scale);
	right.setAttribute("cy", 17 * scale);
	right.setAttribute("rx", 2 * scale);
	right.setAttribute("ry", 1 * scale);
	right.setAttribute("fill", color);
	svg.appendChild(right);

	// Arc to join left and right
	const startX = 3 * scale; // left cx + rx
	const startY = 17 * scale; // cy - ry
	const endX = 19 * scale; // right cx - rx
	const endY = startY;

	// choose radii so the handle bows upwards
	const rx = (endX - startX) / 2;  // half the distance
	const ry = 2.2 * scale;            // controls how tall the arc is

	const arc = document.createElementNS(SVG_NS, 'path');
	// Move to the left‐earpiece top, then arc to the right‐earpiece top
	const d = `M${startX},${startY} A${rx},${ry} 0 0,1 ${endX},${endY}`;
	arc.setAttribute('d', d);
	arc.setAttribute('stroke', color);
	arc.setAttribute('stroke-width', 2 * scale);
	arc.setAttribute('stroke-linecap', 'round');

	svg.appendChild(arc);
	return svg;
}

function create_voicemail_icon(fillColor, gridSize = 25) {
	// SVG namespace
	const SVG_NS = 'http://www.w3.org/2000/svg';

	const scale = gridSize / 25;
	const width = scale * 25;
	const height = scale * 25;

	// Create the root SVG element
	const svg = document.createElementNS(SVG_NS, 'svg');
	svg.setAttribute('width', width);
	svg.setAttribute('height', height);
	svg.setAttribute('viewBox', '0 0 25 25');
	svg.setAttribute('aria-hidden', 'true');

	// Border rectangle (inserted first so it's underneath)
	const border = document.createElementNS(SVG_NS, 'rect');
	y = 7;
	border.setAttribute('x', 1);
	border.setAttribute('y', y);
	border.setAttribute('width', 23);
	border.setAttribute('height', 21 - y);
	border.setAttribute('fill', 'none');
	border.setAttribute('stroke', fillColor);
	border.setAttribute('stroke-width', '2');
	svg.appendChild(border);

	// Left circle
	const left_circle = document.createElementNS(SVG_NS, 'circle');
	left_circle.setAttribute('cx', 7);
	left_circle.setAttribute('cy', 14);
	left_circle.setAttribute('r', 3);
	left_circle.setAttribute('fill', 'none');
	left_circle.setAttribute('stroke', fillColor);
	left_circle.setAttribute('stroke-width', '2');
	svg.appendChild(left_circle);

	// Right circle
	const right_circle = document.createElementNS(SVG_NS, 'circle');
	right_circle.setAttribute('cx', 18);
	right_circle.setAttribute('cy', 14);
	right_circle.setAttribute('r', 3);
	right_circle.setAttribute('fill', 'none');
	right_circle.setAttribute('stroke', fillColor);
	right_circle.setAttribute('stroke-width', '2');
	svg.appendChild(right_circle);

	// Connecting line
	const bar = document.createElementNS(SVG_NS, 'line');
	bar.setAttribute('x1', 6);
	bar.setAttribute('y1', 11);
	bar.setAttribute('x2', 19);
	bar.setAttribute('y2', 11);
	bar.setAttribute('stroke', fillColor);
	bar.setAttribute('stroke-width', '2');
	svg.appendChild(bar);

	return svg;
}
