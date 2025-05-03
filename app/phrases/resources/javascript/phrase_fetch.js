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
 * Portions created by the Initial Developer are Copyright (C) 2008-2024
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

async function fetchData() {
    try {
        const response = await fetch('phrase_responder.php', {
            method: 'POST', // or 'GET' depending on your requirement
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ request: 'phrase_details', phrase_uuid: '86141ace-b218-4f07-b412-fe02d4fdde17' }) // If sending data with POST
        });

        const data = await response.text();
        const json = JSON.parse(data);

        const body = document.body;
        const input = document.createElement('input');
        input.type = 'text';
        input.value = json.message;
        body.appendChild(input);

        console.log(data);
    } catch (error) {
        console.error('Error:', error);
    }
}

fetchData();
