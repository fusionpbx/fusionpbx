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
	Copyright (C) 2010 - 2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if (!class_exists('button')) {
	class button {

		static function create($array) {
			$button_icons = $_SESSION['theme']['button_icons']['text'] != '' ? $_SESSION['theme']['button_icons']['text'] : 'auto';
			//button: open
				$button = "<button ";
				$button .= "type='".($array['type'] ? $array['type'] : 'button')."' ";
				$button .= $array['name'] ? "name=\"".$array['name']."\" " : null;
				$button .= $array['value'] ? "value=\"".$array['value']."\" " : null;
				$button .= $array['id'] ? "id=\"".$array['id']."\" " : null;
				$button .= $array['label'] ? "alt=\"".$array['label']."\" " : ($array['title'] ? "alt=\"".$array['title']."\" " : null);
				if ($button_icons == 'only' || $button_icons == 'auto' || $array['title']) {
					$button .= "title=\"".($array['title'] ? $array['title'] : $array['label'])."\" ";
				}
				$button .= $array['onclick'] ? "onclick=\"".$array['onclick']."\" " : null;
				$button .= "class='btn btn-".($array['class'] ? $array['class'] : 'default')."' ";
				$button .= "style='margin-left: 2px; margin-right: 2px; ".($array['style'] ? $array['style'] : null)."' ";
				$button .= ">";
			//icon
				if ($array['icon'] && (
					$button_icons != 'never' ||
					!$array['label']
					)) {
					$icon_class = is_array($array['icon']) ? $array['icon']['text'] : 'fas fa-'.$array['icon'];
					$button .= "<span class='".$icon_class."'></span>";
				}
			//label
				if ($array['label'] && (
					$button_icons != 'only' ||
					!$array['icon'] ||
					$array['class'] == 'link'
					)) {
					$hide_class = $array['icon'] && $button_icons != 'always' && $button_icons != 'never' ? 'hide-sm' : null;
					$pad_class = $array['icon'] ? 'pad' : null;
					$button .= "<span class='button-label ".$hide_class." ".$pad_class."'>".$array['label']."</span>";
				}
			//button: close
				$button .= "</button>";
			//link
				if ($array['link']) {
					$button = "<a href='".$array['link']."' target=\"".($array['target'] ? $array['target'] : '_self')."\">".$button."</a>";
				}
			return $button;
			unset($button);
		}

	}
}

/*
//usage example (all possible options)

echo button::create(['type'=>'button','label'=>$label,'icon'=>'icon','name'=>'btn','id'=>'btn','value'=>'value','link'=>'url','target'=>'_blank','onclick'=>'action','class'=>'name','style'=>'css','title'=>'title']);

*/

?>