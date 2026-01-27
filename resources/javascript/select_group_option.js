
function select_group_option(original_select_id, group_select_id, option_select_id) {
	const original_select = document.getElementById(original_select_id);
	const group_select = document.getElementById(group_select_id);
	const option_select = document.getElementById(option_select_id);

	if (!original_select || !group_select || !option_select) {
		console.error('Function select_group_option select IDs one or more not found in the DOM.');
		return;
	}

	// Populate group select with optgroup labels
	const optgroups = original_select.querySelectorAll('optgroup');
	optgroups.forEach(optgroup => {
		const option = document.createElement('option');
		option.value = optgroup.label;
		option.textContent = optgroup.label;
		group_select.appendChild(option);
	});

	// When a group is selected, populate option select
	group_select.addEventListener('change', function() {
		const selected_group = this.value;
		option_select.innerHTML = '<option value=""></option>';

		if (selected_group) {
			option_select.disabled = false;
			const optgroup = original_select.querySelector(`optgroup[label="${selected_group}"]`);
			if (optgroup) {
				const options = optgroup.querySelectorAll('option');
				options.forEach(opt => {
					const option_element = document.createElement('option');
					option_element.value = opt.value;
					option_element.textContent = opt.textContent;
					option_select.appendChild(option_element);
				});
			}
		} else {
			option_select.disabled = true;
		}
	});

	// Set the selected value
	const original_value = original_select.value;
	if (original_value) {
		// Find which optgroup contains the selected option
		const selected_optgroup = Array.from(original_select.querySelectorAll('optgroup')).find(optgroup =>
			optgroup.querySelector(`option[value="${original_value}"]`)
		);

		if (selected_optgroup) {
			// Set group select to the selected optgroup
			group_select.value = selected_optgroup.label;

			// Populate and select the corresponding option
			const optgroup = original_select.querySelector(`optgroup[label="${selected_optgroup.label}"]`);
			if (optgroup) {
				const options = optgroup.querySelectorAll('option');
				option_select.innerHTML = '<option value=""></option>';

				options.forEach(opt => {
					const option_element = document.createElement('option');
					option_element.value = opt.value;
					option_element.textContent = opt.textContent;
					option_select.appendChild(option_element);
				});

				// Select the matching option
				option_select.value = original_value;
				option_select.disabled = false;
			}
		}
	}
}
//select_group_option('original_select', 'group_select', 'option_select');
