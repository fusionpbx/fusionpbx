document.addEventListener("DOMContentLoaded", function () {
	// Initialize the select options
	const select = document.getElementById('phrase_detail_data[0]');
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

	// Add drag-and-drop functionality
	tableBody.addEventListener('dragstart', (e) => {
		draggedRow = e.target;
		e.target.classList.add('dragging');
	});

	tableBody.addEventListener('dragover', (e) => {
		e.preventDefault();
		const targetRow = e.target.closest('tr');
		if (targetRow && targetRow !== draggedRow) {
			const bounding = targetRow.getBoundingClientRect();
			const offset = e.clientY - bounding.top;
			if (offset > bounding.height / 2) {
				targetRow.parentNode.insertBefore(draggedRow, targetRow.nextSibling);
			} else {
				targetRow.parentNode.insertBefore(draggedRow, targetRow);
			}
		}
	});

	tableBody.addEventListener('dragend', () => {
		draggedRow.classList.remove('dragging');
		draggedRow = null;
		updateOrder();
	});

}

// Function to update the 'name' attribute based on row numbers
function updateOrder() {
	const tableBody = document.getElementById('structure');
	const rows = tableBody.querySelectorAll('tr');

	//iterate over all rows to renumber them
	rows.forEach((row, index) => {
		//current row number (1-based index)
		const row_number = index + 1;
		//set 'name' attribute
		row.setAttribute('name', 'recording_' + row_number);
	});
}

// Add a new row to the table
function add_row() {
	const tbody = document.getElementById('structure');
	const newRow = document.getElementById('recordings_row').cloneNode(true);

	//reset id
	newRow.id = 'row_' + tbody.childElementCount

	//reset 'name' attribute
	newRow.setAttribute('name', 'recording_' + tbody.childElementCount);

	//get the select boxes
	const select_list = newRow.querySelectorAll('td select'); //action and recording select dropdown boxes

	//play, pause, execute select box
	const select_action = select_list[0];

	//recording select box
	const select_recording = select_list[1];

	//set the new id and name for action
	select_action.id = 'phrase_detail_function[' + tbody.childElementCount - 1 + ']'
	select_action.setAttribute('name', 'phrase_detail_function[' + tbody.childElementCount - 1 + ']');
	//set the new id and name for recording
	select_recording.id = 'phrase_detail_data[' + tbody.childElementCount - 1 + ']'
	select_recording.setAttribute('name', 'phrase_detail_data[' + tbody.childElementCount - 1 + ']');


	//add the row to the table body
	tbody.appendChild(newRow);

	//reinitialize draggable functionality on the row
	add_draggable_rows();
}

// Remove the last row in the table
function remove_row() {
	const tbody = document.getElementById('structure');
	if (tbody && tbody.rows.length > 1) {
		tbody.lastElementChild.remove();
	}
}

function create_new_name() {
	
}
