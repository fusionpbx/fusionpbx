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

		public static $collapse = 'hide-md-dn';

		static function create($array) {
			$button_icons = $_SESSION['theme']['button_icons']['text'] != '' ? $_SESSION['theme']['button_icons']['text'] : 'auto';
			//parse styles into array
				if ($array['style']) {
					$tmp = explode(';',$array['style']);
					foreach ($tmp as $style) {
						if ($style) {
							$style = explode(':', $style);
							$styles[trim($style[0])] = trim($style[1]);
						}
					}
					$array['style'] = $styles;
					unset($styles);
				}
			//button: open
				$button = "<button ";
				$button .= "type='".($array['type'] ? $array['type'] : 'button')."' ";
				$button .= $array['name'] ? "name=".self::quote($array['name'])." " : null;
				$button .= $array['value'] ? "value=".self::quote($array['value'])." " : null;
				$button .= $array['id'] ? "id='".$array['id']."' " : null;
				$button .= $array['label'] ? "alt=".self::quote($array['label'])." " : ($array['title'] ? "alt=".self::quote($array['title'])." " : null);
				if ($button_icons == 'only' || $button_icons == 'auto' || $array['title']) {
					if ($array['title'] || $array['label']) {
						$button .= "title=".($array['title'] ? self::quote($array['title']) : self::quote($array['label']))." ";
					}
				}
				$button .= $array['onclick'] ? "onclick=".self::quote($array['onclick'])." " : null;
				$button .= $array['onmouseover'] ? "onmouseenter=".self::quote($array['onmouseover'])." " : null;
 				$button .= $array['onmouseout'] ? "onmouseleave=".self::quote($array['onmouseout'])." " : null;
				//detect class addition (using + prefix)
				$button_class = $array['class'] && $array['class'][0] == '+' ? 'default '.substr($array['class'], 1) : $array['class'];
				$button .= "class='btn btn-".($button_class ? $button_class : 'default')." ".($array['disabled'] ? 'disabled' : null)."' ";
				//ensure margin* styles are not applied to the button element when a link is defined
				if (is_array($array['style']) && @sizeof($array['style']) != 0) {
					foreach ($array['style'] as $property => $value) {
						if (!$array['link'] || !substr_count($property, 'margin')) {
							$styles .= $property.': '.$value.'; ';
						}
					}
					$button .= $styles ? "style=".self::quote($styles)." " : null;
					unset($styles);
				}
				$button .= $array['disabled'] ? "disabled='disabled' " : null;
				$button .= ">";
			//icon
				if ($array['icon'] && (
					$button_icons == 'only' ||
					$button_icons == 'always' ||
					$button_icons == 'auto' ||
					!$array['label']
					)) {
					$icon_class = is_array($array['icon']) ? $array['icon']['text'] : 'fas fa-'.$array['icon'];
					$button .= "<span class='".$icon_class." fa-fw'></span>";
				}
			//label
				if ($array['label'] && (
					$button_icons != 'only' ||
					!$array['icon'] ||
					$array['class'] == 'link'
					)) {
					if ($array['icon'] && $button_icons != 'always' && $button_icons != 'never' && $array['collapse'] !== false) {
						if ($array['collapse'] != '') {
							$collapse_class = $array['collapse'];
						}
						else if (self::$collapse !== false) {
							$collapse_class = self::$collapse;
						}
					}
					$pad_class = $array['icon'] ? 'pad' : null;
					$button .= "<span class='button-label ".$collapse_class." ".$pad_class."'>".$array['label']."</span>";
				}
			//button: close
				$button .= "</button>";
			//link
				if ($array['link']) {
					$anchor = "<a ";
					$anchor .= "href='".$array['link']."' ";
					$anchor .= "target='".($array['target'] ? $array['target'] : '_self')."' ";
					//ensure only margin* styles are applied to the anchor element
					if (is_array($array['style']) && @sizeof($array['style']) != 0) {
						foreach ($array['style'] as $property => $value) {
							if (substr_count($property, 'margin')) {
								$styles .= $property.': '.$value.'; ';
							}
						}
						$anchor .= $styles ? "style=".self::quote($styles)." " : null;
						unset($styles);
					}
					$anchor .= $array['disabled'] ? "class='disabled' onclick='return false;' " : null;
					$anchor .= ">";
					$button = $anchor.$button."</a>";
				}
			return $button;
			unset($button);
		}

		private static function quote($value) {
			return substr_count($value, "'") ? '"'.$value.'"' : "'".$value."'";
		}

	}
}

/*

//usage

	echo button::create(['type'=>'button','label'=>$text['button-label'],'icon'=>'icon','name'=>'btn','id'=>'btn','value'=>'value','link'=>'url','target'=>'_blank','onclick'=>'javascript','onmouseover'=>'javascript','onmouseout'=>'javascript','class'=>'name','style'=>'css','title'=>$text['button-label'],'collapse'=>'class','disabled'=>false]);

	echo button::create([
		'type'=>'button',
		'label'=>$text['button-label'],
		'icon'=>'icon',
		'name'=>'btn',
		'id'=>'btn',
		'value'=>'value',
		'link'=>'url',
		'target'=>'_blank',
		'onclick'=>'javascript',
		'onmouseover'=>'javascript',
		'onmouseout'=>'javascript',
		'class'=>'name',
		'style'=>'css',
		'title'=>$text['button-label'],
		'collapse'=>'class',
		'disabled'=>false
		]);


//options

	type		'button' (default) | 'submit' | 'link'
	label		button text
	icon		name without vendor prefix (e.g. 'user' instead of 'fa-user')
	value		submitted value (if type is also set to 'submit')
	target		'_blank' | '_self' (default) | etc
	onclick		javascript
	onmouseover	javascript (actually uses onmouseenter so doesn't bubble to child elements)
	onmouseout	javascript (actually uses onmouseleave so doesn't bubble to child elements)
	class		css class[es]
	style		css style[s]
	title		tooltip text (if not set, defaults to value of label)
	collapse	overide the default hide class ('hide-md-dn')
	disabled	boolean true/false, or a value that evaluates to a boolean


//notes

	1) all parameters are optional, but at least set a value for label or icon
	2) overide the default hide class ('hide-md-dn') for all buttons that follow by using...

		button::$collapse = '...';

	3) setting either collapse (instance or default) to false (boolean) will cause the button label to always be visible


//example: enable/disable buttons with javascript

	//javascript
		onclick='button_enable('disabled_button');
	//button
		echo button::create(['type'=>'button', ... ,'id'=>'disabled_button','disabled'=>true]);

	//javascript
		onclick='button_disable('enabled_button');

	//button
		echo button::create(['type'=>'button', ... ,'id'=>'enabled_button']);


	//enable button class button
		echo "<script>\n";
		echo "	function button_enable(button_id) {\n";
		echo "		button = document.getElementById(button_id);\n";
		echo "		button.disabled = false;\n";
		echo "		button.classList.remove('disabled');\n";
		echo "		if (button.parentElement.nodeName == 'A') {\n";
		echo "			anchor = button.parentElement;\n";
		echo "			anchor.classList.remove('disabled');\n";
		echo "			anchor.setAttribute('onclick','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";

	//disable button class button
		echo "<script>\n";
		echo "	function button_disable(button_id) {\n";
		echo "		button = document.getElementById(button_id);\n";
		echo "		button.disabled = true;\n";
		echo "		button.classList.add('disabled');\n";
		echo "		if (button.parentElement.nodeName == 'A') {\n";
		echo "			anchor = button.parentElement;\n";
		echo "			anchor.classList.add('disabled');\n";
		echo "			anchor.setAttribute('onclick','return false;');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";

	//note: the javascript functions above are already contained in the template.php file.


*/

?>