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
	Copyright (C) 2010 - 2020
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if (!class_exists('modal')) {
	class modal {

		static function create($array) {

			$modal = "<div id='".($array['id'] ? $array['id'] : 'modal')."' class='modal-window'>\n";
			$modal .= "	<div>\n";
			$modal .= "		<span title=\"".$text['button-close']."\" class='modal-close' onclick=\"modal_close(); ".$array['onclose']."\">&times</span>\n";
			if ($array['type'] != '') {
				//add multi-lingual support
					$language = new text;
					$text = $language->get();
				//determine type
					switch ($array['type']) {
						case 'copy':
							$array['title'] = $text['modal_title-confirmation'];
							$array['message'] = $text['confirm-copy'];
							break;
						case 'toggle':
							$array['title'] = $text['modal_title-confirmation'];
							$array['message'] = $text['confirm-toggle'];
							break;
						case 'delete':
							$array['title'] = $text['modal_title-confirmation'];
							$array['message'] = $text['confirm-delete'];
							break;
						default: //general
							$array['title'] = $array['title'] ? $array['title'] : $text['modal_title-confirmation'];
					}
				//prefix cancel button to action
					$array['actions'] = button::create(['type'=>'button','label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'collapse'=>'never','onclick'=>'modal_close(); '.$array['onclose']]).$array['actions'];
			}
			$modal .= $array['title'] ? "		<span class='modal-title'>".$array['title']."</span>\n" : null;
			$modal .= $array['message'] ? "		<span class='modal-message'>".$array['message']."</span>\n" : null;
			$modal .= $array['actions'] ? "		<span class='modal-actions'>".$array['actions']."</span>\n" : null;
			$modal .= "	</div>\n";
			$modal .= "</div>";

			return $modal;
			unset($modal);

		}

	}
}

?>
