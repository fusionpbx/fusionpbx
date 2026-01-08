<?php
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
        Portions created by the Initial Developer are Copyright (C) 2008-2024
        the Initial Developer. All Rights Reserved.

        Contributor(s):
        Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
        require_once dirname(__DIR__, 2) . "/resources/require.php";
        require_once "resources/check_auth.php";

//check permissions
        if (permission_exists('voicemail_greeting_edit')) {
                //access granted
        }
        else {
                echo "access denied";
                exit;
        }

//add multi-lingual support
        $language = new text;
        $text = $language->get();

//set the variables
        $domain_uuid = $_SESSION['domain_uuid'];
        $domain_name = $_SESSION['domain_name'];
        $user_uuid = $_SESSION['user_uuid'];

//add the settings object
        $settings = new settings(["domain_uuid" => $domain_uuid, "user_uuid" => $user_uuid]);

//as long as the class exists, enable speech using default settings
        $speech_enabled = class_exists('speech') && $settings->get('speech', 'enabled', false);
        $speech_engine = $settings->get('speech', 'engine', '');

//as long as the class exists, enable transcribe using default settings
        $transcribe_enabled = class_exists('transcribe') && $settings->get('transcribe', 'enabled', false);
        $transcribe_engine = $settings->get('transcribe', 'engine', '');

//check if toggle switches are enabled
        $input_toggle_style_switch = (substr($settings->get('theme', 'input_toggle_style', ''), 0, 6) == 'switch');

//set the storage type from default settings
        $storage_type = $settings->get('voicemail', 'storage_type', '');

//set defaults
        $translate_enabled = false;
        $language_enabled = false;

//add the speech object and get the voices and languages arrays
        if ($speech_enabled && !empty($speech_engine)) {
                $speech = new speech($settings);
                $voices = $speech->get_voices();
                $greeting_format = $speech->get_format();
                //$speech_models = $speech->get_models();
                //$translate_enabled = $speech->get_translate_enabled();
                //$language_enabled = $speech->get_language_enabled();
                //$languages = $speech->get_languages();
        }

//add the transcribe object and get the languages arrays
        if ($transcribe_enabled && !empty($transcribe_engine)) {
                $transcribe = new transcribe($settings);
                //$transcribe_models = $transcribe->get_models();
                //$translate_enabled = $transcribe->get_translate_enabled();
                //$language_enabled = $transcribe->get_language_enabled();
                //$languages = $transcribe->get_languages();
        }

//action add or update
        if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
                $action = "update";
                $voicemail_greeting_uuid = $_REQUEST["id"];
        }
        else {
                $action = "add";
                $voicemail_greeting_uuid = uuid();
        }
        if (!empty($_REQUEST["voicemail_id"]) && is_numeric($_REQUEST["voicemail_id"])) {
                $voicemail_id = $_REQUEST["voicemail_id"];
        }

//get the form value and set to php variables
        if (!empty($_POST) && is_array($_POST)) {
                $greeting_id = $_POST["greeting_id"];
                $greeting_name = $_POST["greeting_name"];
                $greeting_voice = $_POST["greeting_voice"];
                //$greeting_model = $_POST["greeting_model"];
                $greeting_language = $_POST["greeting_language"] ?? null;
                //$translate = $_POST["translate"];
                $greeting_message = $_POST["greeting_message"];
                $greeting_description = $_POST["greeting_description"];

                //clean the name
                $greeting_name = str_replace("'", "", $greeting_name);
        }

if (!empty($_POST) && empty($_POST["persistformvar"])) {

        //delete the voicemail greeting
                if (permission_exists('voicemail_greeting_delete')) {
                        if (!empty($_POST['action']) && $_POST['action'] == 'delete' && is_uuid($voicemail_greeting_uuid)) {
                                //prepare
                                        $array[0]['checked'] = 'true';
                                        $array[0]['uuid'] = $voicemail_greeting_uuid;
                                //delete
                                        $obj = new voicemail_greetings;
                                        $obj->voicemail_id = $voicemail_id;
                                        $obj->delete($array);
                                //redirect
                                        header("Location: voicemail_greetings.php?id=".$voicemail_id);
                                        exit;
                        }
                }

        //validate the token
                $token = new token;
                if (!$token->validate($_SERVER['PHP_SELF'])) {
                        message::add($text['message-invalid_token'],'negative');
                        header('Location: ../voicemails/voicemails.php');
                        exit;
                }

        //check for all required data
                $msg = '';
                if (empty($greeting_name)) { $msg .= "".$text['confirm-name']."<br>\n"; }
                if (!empty($msg) && empty($_POST["persistformvar"])) {
                        require_once "resources/header.php";
                        require_once "resources/persist_form_var.php";
                        echo "<div align='center'>\n";
                        echo "<table><tr><td>\n";
                        echo $msg."<br />";
                        echo "</td></tr></table>\n";
                        persistformvar($_POST);
                        echo "</div>\n";
                        require_once "resources/footer.php";
                        return;
                }

        //update the database
        if ((empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true") && permission_exists('voicemail_greeting_edit')) {

                //get current vm greeting ids for mailbox
                $sql = "select greeting_id ";
                $sql .= "from v_voicemail_greetings where domain_uuid = :domain_uuid ";
                $sql .= "and voicemail_id = :voicemail_id ";
                $sql .= "order by greeting_id asc ";
                $parameters['domain_uuid'] = $domain_uuid;
                $parameters['voicemail_id'] = $voicemail_id;
                $rows = $database->select($sql, $parameters, 'all');
                $greeting_ids = array();
                if (!empty($rows) && is_array($rows)) {
                        foreach ($rows as $row) {
                                $greeting_ids[] = $row['greeting_id'];
                        }
                }
                unset($sql, $parameters);

                //build the setting object and get the recording path
                $greeting_path = $settings->get('switch', 'voicemail').'/default/'.$_SESSION['domain_name'].'/'.$voicemail_id;

                //set the recording format
                $greeting_files = glob($greeting_path.'/greeting_'.$greeting_id.'.*');
                if (empty($greeting_format) && !empty($greeting_files)) {
                        $greeting_format = pathinfo($greeting_files[0], PATHINFO_EXTENSION);
                } else {
                        $greeting_format = $greeting_format ?? 'wav';
                }

                if ($action == 'add') {
                        //find the next available greeting id
                        $greeting_id = 0;
                        for ($i = 1; $i <= 9; $i++) {
                                if (!in_array($i, $greeting_ids) && !file_exists($greeting_path.'/greeting_'.$i.'.'.$greeting_format)) {
                                        $greeting_id = $i;
                                        break;
                                }
                        }
                }

                if (!empty($greeting_id)) {
                        //set file name
                        $greeting_filename = 'greeting_'.$greeting_id.'.'.$greeting_format;

                        //text to audio - make a new audio file from the message
                        if ($speech_enabled && !empty($greeting_voice) && !empty($greeting_message)) {
                                $speech->audio_path = $greeting_path;
                                $speech->audio_filename = $greeting_filename;
                                //$speech->audio_model = $greeting_model ?? '';
                                $speech->audio_voice = $greeting_voice;
                                //$speech->audio_language = $greeting_language;
                                //$speech->audio_translate = $translate;
                                $speech->audio_message = $greeting_message;
                                $speech->speech();

                                //fix invalid riff & data header lengths in generated wave file
                                if ($speech_engine == 'openai') {
                                        $greeting_filename_temp = str_replace('.'.$greeting_format, '.tmp.'.$greeting_format, $greeting_filename);
                                        exec('sox --ignore-length '.$greeting_path.'/'.$greeting_filename.' '.$greeting_path.$greeting_filename_temp);
                                        if (file_exists($greeting_path.'/'.$greeting_filename_temp)) {
                                                exec('rm -f '.$greeting_path.'/'.$greeting_filename.' && mv '.$greeting_path.'/'.$greeting_filename_temp.' '.$greeting_path.'/'.$greeting_filename);
                                        }
                                        unset($greeting_filename_temp);
                                }
                        }

                        //audio to text - get the transcription from the audio file
                        if ($transcribe_enabled && empty($greeting_voice) && empty($greeting_message)) {
                                $transcribe->audio_path = $greeting_path;
                                $transcribe->audio_filename = $greeting_filename;
                                $greeting_message = $transcribe->transcribe();
                        }

                        //if base64 is enabled base64
                        if ($storage_type == 'base64' && file_exists($greeting_path.'/'.$greeting_filename)) {
                                $greeting_base64 = base64_encode(file_get_contents($greeting_path.'/'.$greeting_filename));
                        }

                        //build data array
                        $array['voicemail_greetings'][0]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
                        $array['voicemail_greetings'][0]['domain_uuid'] = $domain_uuid;
                        $array['voicemail_greetings'][0]['voicemail_id'] = $voicemail_id;
                        $array['voicemail_greetings'][0]['greeting_id'] = $greeting_id;
                        $array['voicemail_greetings'][0]['greeting_name'] = $greeting_name;
                        $array['voicemail_greetings'][0]['greeting_message'] = $greeting_message;
                        $array['voicemail_greetings'][0]['greeting_filename'] = $greeting_filename;
                        $array['voicemail_greetings'][0]['greeting_base64'] = $greeting_base64;
                        $array['voicemail_greetings'][0]['greeting_description'] = $greeting_description;

                        //execute query
                        $database->app_name = 'voicemail_greetings';
                        $database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
                        $database->save($array);
                        unset($array);

                        //set message
                        message::add($text['message-'.($action == 'add' ? 'greeting_created' : 'update')]);

                }

                //redirect
                        header("Location: voicemail_greetings.php?id=".$voicemail_id);
                        exit;
        }
}

//pre-populate the form
        if ($action == 'update' && !empty($voicemail_greeting_uuid) && is_uuid($voicemail_greeting_uuid) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
                $sql = "select * from v_voicemail_greetings ";
                $sql .= "where domain_uuid = :domain_uuid ";
                $sql .= "and voicemail_greeting_uuid = :voicemail_greeting_uuid ";
                $parameters['domain_uuid'] = $domain_uuid;
                $parameters['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
                $row = $database->select($sql, $parameters, 'row');
                if (is_array($row) && @sizeof($row) != 0) {
                        $greeting_id = $row["greeting_id"];
                        $greeting_name = $row["greeting_name"];
                        $greeting_message = $row["greeting_message"];
                        $greeting_description = $row["greeting_description"];
                }
                unset($sql, $parameters, $row);
        }

//create token
        $object = new token;
        $token = $object->create($_SERVER['PHP_SELF']);

//show the header
        $document['title'] = $text['label-'.($action == 'update' ? 'edit' : 'add')];
        require_once "resources/header.php";

//show the content
        echo "<form name='frm' id='frm' method='post'>\n";

        echo "<div class='action_bar' id='action_bar'>\n";
        echo "  <div class='heading'><b>".$text['label-'.($action == 'update' ? 'edit' : 'add')]."</b></div>\n";
        echo "  <div class='actions'>\n";
        echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','collapse'=>'hide-xs','link'=>'voicemail_greetings.php?id='.urlencode($voicemail_id)]);
        if (permission_exists('voicemail_greeting_delete') && $action == 'update') {
                echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'name'=>'btn_delete','collapse'=>'hide-xs','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
        }
        echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs']);
        echo "  </div>\n";
        echo "  <div style='clear: both;'></div>\n";
        echo "</div>\n";

        if (permission_exists('voicemail_greeting_delete') && $action == 'update') {
                echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
        }

        echo "<div class='card'>\n";
        echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

        echo "<tr>\n";
        echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
        echo "    ".$text['label-name']."\n";
        echo "</td>\n";
        echo "<td width='70%' class='vtable' align='left'>\n";
        echo "    <input class='formfld' type='text' name='greeting_name' maxlength='255' value=\"".escape($greeting_name ?? '')."\">\n";
        echo "<br />\n";
        echo "".$text['description-name']."\n";
        echo "</td>\n";
        echo "</tr>\n";

        if ($speech_enabled || $transcribe_enabled) {
                //models
                if (!empty($models) && is_array($models)) {
                        echo "<tr>\n";
                        echo "<td class='vncell' valign='top' align='left' nowrap>\n";
                        echo "    ".$text['label-model']."\n";
                        echo "</td>\n";
                        echo "<td class='vtable' align='left'>\n";
                        echo "  <select class='formfld' name='greeting_model'>\n";
                        echo "          <option value=''></option>\n";
                        foreach ($models as $model_id => $model_name) {
                                echo "          <option value='".escape($model_id)."' ".($model_id == $greeting_model ? "selected='selected'" : '').">".escape($model_name)."</option>\n";
                        }
                        echo "  </select>\n";
                        echo "<br />\n";
                        echo $text['description-model']."\n";
                        echo "</td>\n";
                        echo "</tr>\n";
                }

                // Check if this is Inworld with structured voice data
                $is_inworld = ($speech_engine == 'inworld' && !empty($voices) && is_array($voices));
                $has_structured_voices = false;
                if ($is_inworld) {
                        // Check if voices have structured data
                        $first_voice = reset($voices);
                        if (is_array($first_voice) && isset($first_voice['name'])) {
                                $has_structured_voices = true;
                        }
                }

                // Language Filter (only for Inworld with structured voices)
                if ($has_structured_voices) {
                        echo "<tr>\n";
                        echo "<td class='vncell' valign='top' align='left' nowrap>\n";
                        echo "    Language Filter\n";
                        echo "</td>\n";
                        echo "<td class='vtable' align='left'>\n";
                        echo "  <select class='formfld' id='greeting_language_filter' onchange='filterVoicesByLanguage(this.value)'>\n";
                        echo "          <option value=''>All Languages</option>\n";
                        echo "  </select>\n";
                        echo "<br />\n";
                        echo "Filter voices by language\n";
                        echo "</td>\n";
                        echo "</tr>\n";
                }

                //voices
                echo "<tr>\n";
                echo "<td class='vncell' valign='top' align='left' nowrap>\n";
                echo "    ".$text['label-voice']."\n";
                echo "</td>\n";
                echo "<td class='vtable' align='left'>\n";
                if (!empty($voices) && is_array($voices)) {
                        echo "  <select class='formfld' id='greeting_voice' name='greeting_voice'";
                        if ($has_structured_voices) {
                                echo " onfocus='showFullVoiceDetails()' onmousedown='showFullVoiceDetails()' onchange='updateVoiceDisplay(); this.blur();' onblur='updateVoiceDisplay()'";
                        }
                        echo ">\n";
                        echo "          <option value=''></option>\n";
                        foreach ($voices as $key => $voice) {
                                if ($has_structured_voices) {
                                        // Inworld structured voice data
                                        $voice_name = $voice['name'];
                                        $voice_description = $voice['description'];
                                        $voice_languages = is_array($voice['languages']) ? implode(', ', $voice['languages']) : $voice['languages'];
                                        $full_text = $voice_name . " - " . $voice_description . " (" . $voice_languages . ")";
                                        $voice_selected = (!empty($greeting_voice) && $key == $greeting_voice) ? "selected='selected'" : null;
                                        
                                        echo "          <option value='".escape($key)."' $voice_selected ";
                                        echo "data-name='".escape($voice_name)."' ";
                                        echo "data-description='".escape($voice_description)."' ";
                                        echo "data-languages='".escape($voice_languages)."'>";
                                        echo escape($full_text)."</option>\n";
                                } else {
                                        // Standard voice dropdown
                                        $voice_value = is_int($key) ? $voice : $key;
                                        $voice_display = is_array($voice) ? ($voice['name'] ?? $voice_value) : $voice;
                                        $voice_selected = (!empty($greeting_voice) && $voice_value == $greeting_voice) ? "selected='selected'" : null;
                                        echo "          <option value='".escape($voice_value)."' $voice_selected>".escape(ucwords($voice_display))."</option>\n";
                                }
                        }
                        echo "  </select>\n";
                }
                else {
                        echo "          <input class='formfld' type='text' name='greeting_voice' maxlength='255' value=\"".escape($greeting_voice ?? '')."\">\n";
                }
                echo "<br />\n";
                echo $text['description-voice']."\n";
                echo "</td>\n";
                echo "</tr>\n";

                if ($language_enabled) {
                        echo "<tr>\n";
                        echo "<td class='vncell' valign='top' align='left' nowrap>\n";
                        echo "    ".$text['label-language']."\n";
                        echo "</td>\n";
                        echo "<td class='vtable' align='left'>\n";
                        if (!empty($languages) && is_array($languages)) {
                                sort($languages);
                                echo "  <select class='formfld' name='greeting_language'>\n";
                                echo "          <option value=''></option>\n";
                                foreach ($languages as $language) {
                                        echo "          <option value='".escape($language)."' ".($language == $greeting_language ? "selected='selected'" : null).">".escape($language)."</option>\n";
                                }
                                echo "  </select>\n";
                        }
                        else {
                                echo "          <input class='formfld' type='text' name='greeting_language' maxlength='255' value=\"".escape($greeting_language ?? '')."\">\n";
                        }
                        echo "<br />\n";
                        echo $text['description-languages']."\n";
                        echo "</td>\n";
                        echo "</tr>\n";
                }

                if ($translate_enabled) {
                        echo "<tr>\n";
                        echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
                        echo "  ".$text['label-translate']."\n";
                        echo "</td>\n";
                        echo "<td class='vtable' align='left'>\n";
                        if ($input_toggle_style_switch) {
                                echo "  <span class='switch'>\n";
                        }
                        echo "  <select class='formfld' id='translate' name='translate'>\n";
                        echo "          <option value='true' ".($translate == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
                        echo "          <option value='false' ".($translate == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
                        echo "  </select>\n";
                        if ($input_toggle_style_switch) {
                                echo "          <span class='slider'></span>\n";
                                echo "  </span>\n";
                        }
                        echo "<br />\n";
                        echo $text['description-translate']."\n";
                        echo "</td>\n";
                        echo "</tr>\n";
                }

                echo "<tr>\n";
                echo "<td class='vncell' valign='top' align='left' nowrap>\n";
                echo "    ".$text['label-message']."\n";
                echo "</td>\n";
                echo "<td class='vtable' align='left'>\n";
                echo "    <textarea class='formfld' name='greeting_message' style='width: 300px; height: 150px;'>".escape($greeting_message ?? '')."</textarea>\n";
                echo "<br />\n";
                echo $text['description-message']."\n";
                echo "</td>\n";
                echo "</tr>\n";
        }

        echo "<tr>\n";
        echo "<td class='vncell' valign='top' align='left' nowrap>\n";
        echo "    ".$text['label-description']."\n";
        echo "</td>\n";
        echo "<td class='vtable' align='left'>\n";
        echo "    <input class='formfld' type='text' name='greeting_description' maxlength='255' value=\"".escape($greeting_description ?? '')."\">\n";
        echo "<br />\n";
        echo "".$text['description-info']."\n";
        echo "</td>\n";
        echo "</tr>\n";

        echo "</table>";
        echo "</div>\n";
        echo "<br /><br />";

        if ($action == 'update' && !empty($voicemail_greeting_uuid) && is_uuid($voicemail_greeting_uuid)) {
                echo "<input type='hidden' name='voicemail_greeting_uuid' value='".escape($voicemail_greeting_uuid)."'>\n";
                echo "<input type='hidden' name='greeting_id' value='".escape($greeting_id ?? '')."'>\n";
        }
        echo "<input type='hidden' name='voicemail_id' value='".escape($voicemail_id)."'>\n";
        echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

        echo "</form>";

        // Add JavaScript for Inworld enhancements
        if ($has_structured_voices) {
?>
<script>
// Add CSS for voice select dynamic sizing and toggle switches
const style = document.createElement('style');
style.textContent = `
        /* Voice select starts at normal size */
        #greeting_voice {
                width: 300px !important;
                max-width: 300px !important;
        }
        /* Expands to show full details when open */
        #greeting_voice:focus,
        #greeting_voice.dropdown-open {
                width: 600px !important;
                max-width: 100% !important;
        }
        
        /* Hide the select dropdown when inside a switch - use !important to override formfld */
        .switch select {
                display: none !important;
                opacity: 0 !important;
                position: absolute !important;
                width: 0 !important;
                height: 0 !important;
        }
        
        /* Complete Toggle Switch Styles */
        .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 30px;
                vertical-align: middle;
        }
        
        .switch .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 30px;
        }
        
        .switch .slider:before {
                position: absolute;
                content: "";
                height: 22px;
                width: 22px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
        }
        
        .switch.checked .slider {
                background-color: #00bfff;
        }
        
        .switch.checked .slider:before {
                transform: translateX(30px);
        }
`;
document.head.appendChild(style);

// Make toggle switches work
document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.switch').forEach(function(switchElem) {
                const select = switchElem.querySelector('select');
                const slider = switchElem.querySelector('.slider');
                
                if (select && slider) {
                        // Set initial state based on select value
                        if (select.value === 'true') {
                                switchElem.classList.add('checked');
                        }
                        
                        // Toggle on slider click
                        slider.addEventListener('click', function() {
                                if (select.value === 'true') {
                                        select.value = 'false';
                                        switchElem.classList.remove('checked');
                                } else {
                                        select.value = 'true';
                                        switchElem.classList.add('checked');
                                }
                        });
                }
        });
});

// Build Inworld voices data from PHP
const inworldVoices = {};
<?php
foreach ($voices as $voice_id => $voice_data) {
        if (is_array($voice_data)) {
                echo "inworldVoices[" . json_encode($voice_id) . "] = " . json_encode($voice_data) . ";\n";
        }
}
?>

// Language names mapping
const languageNames = {
        'en': 'English',
        'es': 'Spanish (Español)',
        'fr': 'French (Français)',
        'de': 'German (Deutsch)',
        'it': 'Italian (Italiano)',
        'pt': 'Portuguese (Português)',
        'pl': 'Polish (Polski)',
        'zh': 'Chinese (中文)',
        'ja': 'Japanese (日本語)',
        'ko': 'Korean (한국어)',
        'nl': 'Dutch (Nederlands)',
        'ru': 'Russian (Русский)',
        'hi': 'Hindi (हिन्दी)'
};

// Get unique languages from voices
function getLanguages() {
        const languages = new Set();
        for (const voiceId in inworldVoices) {
                const voice = inworldVoices[voiceId];
                if (voice.languages && Array.isArray(voice.languages)) {
                        voice.languages.forEach(lang => languages.add(lang));
                }
        }
        return Array.from(languages);
}

// Populate language filter dropdown
function populateLanguageFilter() {
        const languages = getLanguages();
        
        // Sort languages: English first, then alphabetically
        languages.sort((a, b) => {
                if (a === 'en') return -1;
                if (b === 'en') return 1;
                const nameA = languageNames[a] || a;
                const nameB = languageNames[b] || b;
                return nameA.localeCompare(nameB);
        });
        
        const filterSelect = document.getElementById('greeting_language_filter');
        if (filterSelect) {
                languages.forEach(lang => {
                        const option = document.createElement('option');
                        option.value = lang;
                        option.textContent = languageNames[lang] || lang;
                        filterSelect.appendChild(option);
                });
        }
}

// Filter voices by selected language
function filterVoicesByLanguage(selectedLang) {
        const voiceSelect = document.getElementById('greeting_voice');
        if (!voiceSelect) return;
        
        // Save currently selected voice
        const currentValue = voiceSelect.value;
        
        // Clear existing options except the first empty one
        while (voiceSelect.options.length > 1) {
                voiceSelect.remove(1);
        }
        
        // Add filtered voices
        for (const voiceId in inworldVoices) {
                const voice = inworldVoices[voiceId];
                
                // Filter by language if specified
                if (selectedLang && voice.languages && !voice.languages.includes(selectedLang)) {
                        continue;
                }
                
                const option = document.createElement('option');
                option.value = voiceId;
                option.setAttribute('data-name', voice.name);
                option.setAttribute('data-description', voice.description);
                option.setAttribute('data-languages', voice.languages.join(', '));
                
                // Full text for when dropdown is open
                const fullText = voice.name + ' - ' + voice.description + ' (' + voice.languages.join(', ') + ')';
                option.textContent = fullText;
                
                // Restore selection if this was the selected voice
                if (voiceId === currentValue) {
                        option.selected = true;
                }
                
                voiceSelect.appendChild(option);
        }
        
        // Update display to show only names
        updateVoiceDisplay();
}

// Show only voice names when dropdown is closed
function updateVoiceDisplay() {
        const voiceSelect = document.getElementById('greeting_voice');
        if (!voiceSelect) return;
        
        // Remove dropdown-open class
        voiceSelect.classList.remove('dropdown-open');
        
        // Update each option to show only the name
        for (let i = 0; i < voiceSelect.options.length; i++) {
                const option = voiceSelect.options[i];
                const voiceName = option.getAttribute('data-name');
                if (voiceName) {
                        option.textContent = voiceName;
                }
        }
}

// Show full voice details when dropdown is opened
function showFullVoiceDetails() {
        const voiceSelect = document.getElementById('greeting_voice');
        if (!voiceSelect) return;
        
        // Add dropdown-open class for width expansion
        voiceSelect.classList.add('dropdown-open');
        
        // Update each option to show full details
        for (let i = 0; i < voiceSelect.options.length; i++) {
                const option = voiceSelect.options[i];
                const voiceName = option.getAttribute('data-name');
                const voiceDesc = option.getAttribute('data-description');
                const voiceLangs = option.getAttribute('data-languages');
                
                if (voiceName && voiceDesc && voiceLangs) {
                        option.textContent = voiceName + ' - ' + voiceDesc + ' (' + voiceLangs + ')';
                }
        }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
        populateLanguageFilter();
        updateVoiceDisplay(); // Start with names only
        
        // Handle dropdown closing
        const voiceSelect = document.getElementById('greeting_voice');
        if (voiceSelect) {
                // When user clicks outside
                document.addEventListener('click', function(e) {
                        if (e.target !== voiceSelect) {
                                updateVoiceDisplay();
                        }
                });
        }
});
</script>
<?php
        }

//include the footer
        require_once "resources/footer.php";

?>
