/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//record by phone, used by the Call button and dialog from app/recordings/resources/classes/recording_call.php
//the recording_call object (url, token and text) is set by the page before this file is loaded

function recording_call_status(message, done) {
	var status = document.getElementById('recording_call_status');
	status.innerText = message;
	status.style.display = message ? 'block' : 'none';
	document.getElementById('recording_call_btn_hangup').style.display = done ? 'none' : 'inline-block';
	document.getElementById('recording_call_btn_call').style.display = done ? 'inline-block' : 'none';
	document.getElementById('recording_call_form').style.display = done ? 'block' : 'none';
}

function recording_call_open(target, recording_uuid, full_path) {
	recording_call.target = target;
	recording_call.full_path = full_path ? true : false;
	recording_call.recording_uuid = recording_uuid;
	recording_call.call_uuid = '';
	recording_call.saved = false;
	document.getElementById('recording_call_name').value = '';
	document.getElementById('recording_call_name_row').style.display = recording_uuid ? 'none' : 'block';
	recording_call_status('', true);
	modal_open('modal-recording_call', recording_uuid ? 'recording_call_extension' : 'recording_call_name');
}

function recording_call_request(data) {
	var form = new FormData();
	for (var key in data) { form.append(key, data[key]); }
	form.append(recording_call.token_name, recording_call.token_hash);
	return fetch(recording_call.url, { method: 'POST', body: form, credentials: 'same-origin' })
		.then(function(response) { return response.json(); });
}

function recording_call_start() {
	recording_call_status(recording_call.text.calling, false);
	recording_call_request({
		action: 'call',
		extension_uuid: document.getElementById('recording_call_extension').value,
		recording_name: document.getElementById('recording_call_name').value,
		recording_uuid: recording_call.recording_uuid
	}).then(function(result) {
		if (result.error) {
			recording_call_status(result.error, true);
			return;
		}
		recording_call.call_uuid = result.call_uuid;
		recording_call.timer = setInterval(recording_call_poll, 1500);
	}).catch(function() {
		recording_call_status(recording_call.text.failed, true);
	});
}

function recording_call_poll() {
	if (!recording_call.call_uuid) { return; }
	recording_call_request({ action: 'status', call_uuid: recording_call.call_uuid }).then(function(result) {
		if (result.error) {
			recording_call_stop();
			recording_call_status(result.error, true);
			return;
		}
		if (result.done) {
			//the call has ended, close the dialog and show the result in the message bar
			recording_call_stop();
			if (result.state === 'saved') {
				recording_call_saved(result);
			}
			var mood = result.state === 'saved' ? 'positive' : (result.state === 'deleted' ? 'default' : 'negative');
			recording_call_close();
			if (typeof display_message === 'function') {
				display_message(recording_call.text[result.state] || recording_call.text.ended, mood, 3000);
			}
			return;
		}
		recording_call_status(recording_call.text[result.state] || recording_call.text.calling, false);
	});
}

function recording_call_stop() {
	clearInterval(recording_call.timer);
	recording_call.timer = null;
	recording_call.call_uuid = '';
}

function recording_call_hangup() {
	if (recording_call.call_uuid) {
		recording_call_request({ action: 'hangup', call_uuid: recording_call.call_uuid });
	}
}

function recording_call_saved(result) {
	recording_call.saved = true;
	var select = recording_call.target ? document.getElementById(recording_call.target) : null;
	if (!select) { return; }

	//find or add the recordings group
	var group = select.querySelector("optgroup[data-type='recordings']");
	if (!group) {
		group = document.createElement('optgroup');
		group.label = recording_call.text.label_recordings;
		group.setAttribute('data-type', 'recordings');
		select.appendChild(group);
	}

	//find or add the option
	var value = recording_call.full_path ? result.recording_path : result.recording_filename;
	var option = null;
	select.querySelectorAll('option').forEach(function(o) { if (o.value === value) { option = o; } });
	if (!option) {
		option = document.createElement('option');
		option.value = value;
		option.textContent = result.recording_name;
		group.insertBefore(option, group.firstChild);
	}

	//select it and update the searchable select box
	select.value = value;
	select.dispatchEvent(new Event('change'));
	var search = document.querySelector('#' + recording_call.target + '_search input');
	if (search) { search.value = option.label || option.textContent; }
}

function recording_call_close() {
	if (recording_call.call_uuid) {
		recording_call_hangup();
		recording_call_stop();
	}
	modal_close();
	if (recording_call.saved && !document.getElementById(recording_call.target)) {
		window.location.reload();
	}
}
