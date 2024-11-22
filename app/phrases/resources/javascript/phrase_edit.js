document.addEventListener("DOMContentLoaded", function () {
	// Initialize the select options
	const select = document.getElementById('phrase_detail_data');
	const grp_rec = document.createElement('optgroup');
	const grp_snd = document.createElement('optgroup');

	// Add a blank entry
	select.appendChild(new Option('', ''));

	// Add recordings
	grp_rec.label = 'Recordings';
	for (let i = 0; i < window.phrase_recordings.length; i++) {
		grp_rec.appendChild(new Option('    ' + window.phrase_recordings[i].recording_name, window.phrase_recordings[i].recording_uuid));
	}
	select.appendChild(grp_rec);

	// Add sounds
	grp_snd.label = 'Sounds';
	for (let i = 0; i < window.phrase_sounds.length; i++) {
		grp_snd.appendChild(new Option('    ' + window.phrase_sounds[i], i));
	}
	select.appendChild(grp_snd);

	// Initialize draggable rows
	add_draggable_rows();
});

// Add draggable functionality to rows
function add_draggable_rows() {
	const tableBody = document.getElementById('structure');
	let draggedRow = null;

	tableBody.querySelectorAll('tr').forEach((row) => {
		// Make rows draggable
		row.setAttribute('draggable', 'true');

		// When dragging starts
		row.addEventListener('dragstart', (e) => {
			draggedRow = row;
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', row.rowIndex);

			// Highlight the dragged row
			row.style.backgroundColor = '#d1e7fd'; // Light blue color
			row.style.opacity = '0.8'; // Slightly transparent for visual feedback
		});

		// When dragging ends
		row.addEventListener('dragend', () => {
			draggedRow = null;

			// Clear the background color
			row.style.backgroundColor = '';
			row.style.opacity = '1'; // Reset opacity
		});

		// Allow drop (prevent default behavior)
		row.addEventListener('dragover', (e) => {
			e.preventDefault();
		});

		// Handle drop
		row.addEventListener('drop', (e) => {
			e.preventDefault();

			if (draggedRow && draggedRow !== row) {
				// Insert the dragged row before the row it is dropped onto
				tableBody.insertBefore(draggedRow, row.nextSibling || row);
			}
		});

		// Optional: Highlight drop target
		row.addEventListener('dragenter', () => {
			row.style.backgroundColor = '#f0f0f0'; // Light gray for drop target
		});

		row.addEventListener('dragleave', () => {
			row.style.backgroundColor = ''; // Clear drop target highlight
		});
	});
}


// Add a new row to the table
function add_row() {
	const tbody = document.getElementById('structure');
	const newRow = document.getElementById('recordings_row').cloneNode(true);
	newRow.id = `row_${tbody.childElementCount}`;
	newRow.name = `recordings[${tbody.childElementCount}]`;

	tbody.appendChild(newRow);

	// Reinitialize draggable functionality
	add_draggable_rows();
}

// Remove the last row in the table
function remove_row() {
	const tbody = document.getElementById('structure');
	if (tbody && tbody.rows.length > 1) {
		tbody.lastElementChild.remove();
	}
}
