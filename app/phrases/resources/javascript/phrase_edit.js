// use an async function so page works without delays
document.addEventListener("DOMContentLoaded", async function () {
	// Initialize the select options
	const select = document.getElementById('phrase_detail_data_empty');
	const grp_rec = document.createElement('optgroup');
	const grp_snd = document.createElement('optgroup');

	// Add a blank entry
	select.appendChild(new Option('', ''));

	// Add recordings
	grp_rec.label = window.phrase_label_recordings;
	try {
		const phrase_recordings = await fetch_data({request: 'recordings', data: ''});
		for (let i = 0; i < phrase_recordings.length; i++) {
			grp_rec.appendChild(new Option(phrase_recordings[i].recording_name, phrase_recordings[i].recording_uuid));
		}
		select.appendChild(grp_rec);
	} catch (error) {
		console.error("Error fetching recordings:", error);
	}

	// Add sounds
	grp_snd.label = window.phrase_label_sounds;
	try {
		const phrase_sounds = await fetch_data({request: 'sound_files', data: ''});
		for (let i = 0; i < phrase_sounds.length; i++) {
			grp_snd.appendChild(new Option(phrase_sounds[i], phrase_sounds[i]));
		}
		select.appendChild(grp_snd);
	} catch (error) {
		console.error("Error fetching recordings:", error);
	}

	// add the existing data
	add_existing();

	// add empty row
	add_row();

	// Initialize draggable rows
	add_draggable_rows();
});

function remove_loading() {
	//remove loading
	const loading = document.getElementById('loading');
	if (loading) {
		loading.remove();
	}
}

async function fetch_data(command) {
    try {
        const response = await fetch('resources/phrase_responder.php', {
            method: 'POST', // or 'GET' depending on your requirement
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(command) // If sending data with POST
        });

        const data = await response.json();

        return data.message;

    } catch (error) {
        console.error('Error:', error);
    }
}

//
// Inserts all existing records before the empty one
//
async function add_existing() {
	//get the body structure
	const tbody = document.getElementById('structure');
	//get the phrase id
	const urlParams = new URLSearchParams(window.location.search);
	const phrase_uuid = urlParams.get('id');
	//fetch the phrase details from the database
	const phrase_details = await fetch_data({request: 'phrase_details', data: phrase_uuid});
	//display the phrase details
	for (let index=0; index < phrase_details.length; index++) {
		//add an empty row
		add_row();
		//get the action select box
		const select_action = document.getElementById('phrase_detail_function[' + index + ']');
		//set the chosen option
		select_by_value(select_action, phrase_details[index].phrase_detail_function);
		//get the data select box
		const select_data = document.getElementById('phrase_detail_data[' + index + ']');
		//set the chosen option
		select_by_text(select_data, phrase_details[index]['display_name']);
		const uuid_field = document.getElementById('phrase_detail_uuid[' +index+']');
		uuid_field.value = phrase_details[index]['phrase_detail_uuid'];
		//set the slider value
		const slider = document.getElementById('slider['+index+']');
		const sleep = document.getElementById('sleep['+index+']');
		const phrase_detail_text = document.getElementById('phrase_detail_text['+index+']');
		//update the sleep data
		if (phrase_details[index].phrase_detail_function === 'pause') {
			sleep.value = phrase_details[index].phrase_detail_data;
			slider.value = phrase_details[index].phrase_detail_data;
		}
		//update the execute text
		if (phrase_details[index].phrase_detail_function === 'execute') {
			phrase_detail_text.value = phrase_details[index].phrase_detail_data;
		}
		// Manually trigger the change event to select the proper display
		if (phrase_details[index].phrase_detail_function !== 'play-file') {
			const changeEvent = new Event('change', { bubbles: true });
			select_action.dispatchEvent(changeEvent);
		}
	}

	remove_loading();
}

//
// Set the selected index on a dropdown box based on the value (key)
//
function select_by_value(selectElement, valueToFind) {
	// Loop through the options of the select element
	for (let i = 0; i < selectElement.options.length; i++) {
		if (selectElement.options[i].value === valueToFind) {
			selectElement.selectedIndex = i; // Set the selected index
			return; // Exit the loop once found
		}
	}
	console.warn('Value not found in select options');
}

//
// Set the selected index on a dropdown box based on the text
//
function select_by_text(selectElement, textToFind) {
	for (let i = 0; i < selectElement.options.length; i++) {
		if (selectElement.options[i].text === textToFind) {
			selectElement.selectedIndex = i;
			return;
		}
	}
	console.warn('Text not found in select options');
}

//
// Add draggable functionality to rows
//
function add_draggable_rows() {
	const tableBody = document.getElementById('structure');
	let draggedRow = null;

	// Add drag listeners only to the leftmost cell on the row
	tableBody.querySelectorAll('tr').forEach(row => {
		const dragHandleCell = row.cells[0]; // Assuming the first cell is the one left to the dropdown

		if (!dragHandleCell) return;

		// Enable dragging from this cell
		dragHandleCell.setAttribute('draggable', 'true');

		dragHandleCell.addEventListener('dragstart', (e) => {
			draggedRow = row;
			row.classList.add('dragging');
		});

		dragHandleCell.addEventListener('dragend', () => {
			if (draggedRow) {
				draggedRow.classList.remove('dragging');
				draggedRow = null;
			}
		});

		dragHandleCell.addEventListener('dragover', (e) => {
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
		dragHandleCell.addEventListener('dragend', () => {
			if (draggedRow) {
				draggedRow.classList.remove('dragging');
				draggedRow = null;
				update_order();
			}
		});
	});

}

//
// Function to update the 'name' attribute based on row numbers
//
function update_order() {
	const tableBody = document.getElementById('structure');
	const rows = tableBody.querySelectorAll('tr');

	//iterate over all rows to renumber them
	rows.forEach((row, index) => {
		//set 'name' attribute and id
		row.setAttribute('name', 'row_' + index);
		row.id = 'row_' + index;

		//get the select boxes
		const select_list = row.querySelectorAll('td select'); //action and recording select dropdown boxes

		//get the input boxes
		const input_boxes = row.querySelectorAll('td input');
		//uuid
		const phrase_detail_uuid = input_boxes[0];
		phrase_detail_uuid.removeAttribute('id');
		phrase_detail_uuid.id = 'phrase_detail_uuid[' + index + ']';
		phrase_detail_uuid.name = phrase_detail_uuid.id;
		//execute action
		const phrase_detail_text = input_boxes[1];
		temp_value = phrase_detail_text.value;
		phrase_detail_text.removeAttribute('id');
		phrase_detail_text.id = 'phrase_detail_text[' + index + ']';
		phrase_detail_text.name = phrase_detail_text.id;
		phrase_detail_text.value = temp_value;
		//slider
		const slider = input_boxes[2];
		slider.removeAttribute('id');
		slider.id = 'slider[' + index + ']';
		slider.name = slider.id;
		//sleep value
		const sleep = input_boxes[3];
		temp_value = sleep.value;
		sleep.removeAttribute('id');
		sleep.id = 'sleep[' + index + ']';
		sleep.name = sleep.id;
		sleep.value = temp_value;

		//play, pause, execute select box
		const select_function = select_list[0];
		select_function.removeAttribute('id');
		select_function.id   = 'phrase_detail_function[' + index + ']'
		select_function.name = select_function.id;

		//recording select box
		const select_data = select_list[1];
		select_data.removeAttribute('id');
		select_data.id = 'phrase_detail_data[' + index + ']'
		select_data.name = select_data.id;

	});
}

//
// Ensure the order is updated when submitting the form
//
function submit_phrase() {
	//ensure order is updated before submitting form
	update_order();
	//submit form
	const form = document.getElementById('frm').submit();
}

//
// Add a new row to the table
//
function add_row() {
	const tbody = document.getElementById('structure');

	// current index is the count subtract the hidden row
	const index = tbody.childElementCount;

	const newRow = document.getElementById('empty_row').cloneNode(true);
	//reset id
	newRow.removeAttribute('id');
	newRow.id = 'row_' + index;

	//un-hide row
	newRow.style.display = '';

	//reset 'name' attribute
	newRow.setAttribute('name', 'recording_' + index);

	//get the select boxes
	const select_list = newRow.querySelectorAll('td select'); //action and recording select dropdown boxes
	//play, pause, execute select box
	const select_action = select_list[0];
	select_action.removeAttribute('id');
	select_action.id = 'phrase_detail_function[' + index + ']';
	select_action.name = 'phrase_detail_function[' + index + ']';
	//recording select box
	const select_data = select_list[1];
	select_data.removeAttribute('id');
	select_data.id = 'phrase_detail_data[' + index + ']';
	select_data.name = 'phrase_detail_data[' + index + ']';
	//uuid field
	const uuid_field = newRow.querySelector('input[name="empty_uuid"]');
	uuid_field.removeAttribute('id');
	uuid_field.id = 'phrase_detail_uuid[' + index +']';
	uuid_field.name = 'phrase_detail_uuid[' + index +']';
	const phrase_detail_text = newRow.querySelector('input[name="empty_phrase_detail_text"]');
	phrase_detail_text.removeAttribute('id');
	phrase_detail_text.id = 'phrase_detail_text[' + index + ']';
	phrase_detail_text.name = 'phrase_detail_text[' + index + ']';
	//slider
	const slider = newRow.querySelector('input[name="range"]');
	slider.removeAttribute('id');
	slider.id = 'slider[' + index + ']';
	slider.name = 'slider[' + index + ']';
	//sleep
	const sleep = newRow.querySelector('input[name="sleep"]');
	sleep.removeAttribute('id');
	sleep.id = 'sleep[' + index + ']';
	sleep.name = 'sleep[' + index + ']';
	sleep.value = slider.value;

	let changing = false;

	slider.addEventListener('mousemove', function () {
		changing = true;
		sleep.value = slider.value;
		changing = false;
	});

	sleep.addEventListener('keyup', function() {
		if (!changing) {
			if (sleep.value.length > 0)
				slider.value = sleep.value;
			else {
				slider.value = 0;
			}
		}
	})

	//add switchable select box to text input box
	select_action.addEventListener('change', function () {
		if (select_action.value === 'execute') {
			//show text box
			select_data.style.display = 'none';
			slider.style.display = 'none';
			sleep.style.display = 'none';
			phrase_detail_text.style.display = 'block';
		} else if (select_action.value === 'pause') {
			//show the range bar
			select_data.style.display = 'none';
			phrase_detail_text.style.display = 'none';
			slider.style.display = 'block';
			sleep.style.display = 'block';
		} else {
			//show drop down
			phrase_detail_text.style.display = 'none';
			slider.style.display = 'none';
			sleep.style.display = 'none';
			select_data.style.display = 'block';
		}
	});

	//add the row to the table body
	tbody.appendChild(newRow);

	//reinitialize draggable functionality on the row
	add_draggable_rows();
	return index;
}

//
// Remove the last row in the table
//
function remove_row() {
	const tbody = document.getElementById('structure');
	if (tbody && tbody.rows.length > 1) {
		tbody.lastElementChild.remove();
	}
}
