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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
	$document['title'] = $text['title-active_conferences'];
	require_once "resources/header.php";

?>

<script type="text/javascript">
function loadXmlHttp(url, id) {
	var f = this;
	f.xmlHttp = null;
	/*@cc_on @*/ // used here and below, limits try/catch to those IE browsers that both benefit from and support it
	/*@if(@_jscript_version >= 5) // prevents errors in old browsers that barf on try/catch & problems in IE if Active X disabled
	try {f.ie = window.ActiveXObject}catch(e){f.ie = false;}
	@end @*/
	if (window.XMLHttpRequest&&!f.ie||/^http/.test(window.location.href))
		f.xmlHttp = new XMLHttpRequest(); // Firefox, Opera 8.0+, Safari, others, IE 7+ when live - this is the standard method
	else if (/(object)|(function)/.test(typeof createRequest))
		f.xmlHttp = createRequest(); // ICEBrowser, perhaps others
	else {
		f.xmlHttp = null;
		 // Internet Explorer 5 to 6, includes IE 7+ when local //
		/*@cc_on @*/
		/*@if(@_jscript_version >= 5)
		try{f.xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");}
		catch (e){try{f.xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");}catch(e){f.xmlHttp=null;}}
		@end @*/
	}
	if(f.xmlHttp != null){
		f.el = document.getElementById(id);
		f.xmlHttp.open("GET",url,true);
		f.xmlHttp.onreadystatechange = function(){f.stateChanged();};
		f.xmlHttp.send(null);
	}
}

loadXmlHttp.prototype.stateChanged=function () {
	var url = new URL(this.xmlHttp.responseURL);
	if (/login\.php$/.test(url.pathname)) {
		// You are logged out. Stop refresh!
		url.searchParams.set('path', '<?php echo $_SERVER['REQUEST_URI']; ?>');
		window.location.href = url.href;
		return;
	}

	if (this.xmlHttp.readyState == 4 && (this.xmlHttp.status == 200 || !/^http/.test(window.location.href)))
		//this.el.innerHTML = this.xmlHttp.responseText;
		document.getElementById('ajax_response').innerHTML = this.xmlHttp.responseText;

	//link table rows (except the last - the list_control_icons cell) on a table with a class of 'tr_hover', according to the href attribute of the <tr> tag
		$('.tr_hover tr,.list tr').each(function(i,e) {
			$(e).children('td:not(.list_control_icon,.list_control_icons,.tr_link_void,.list-row > .no-link,.list-row > .checkbox,.list-row > .button,.list-row > .action-button)').on('click', function() {
				var href = $(this).closest('tr').attr('href');
				var target = $(this).closest('tr').attr('target');
				if (href) {
					if (target) { window.open(href, target); }
					else { window.location = href; }
				}
			});
		});
}

var requestTime = function() {
	var url = 'conferences_active_inc.php';
	new loadXmlHttp(url, 'ajax_response');
	setInterval(function(){new loadXmlHttp(url, 'ajax_response');}, 1777);
}

if (window.addEventListener) {
	window.addEventListener('load', requestTime, false);
}
else if (window.attachEvent) {
	window.attachEvent('onload', requestTime);
}

</script>

<?php

//page header
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-active_conferences']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-active']."\n";
	echo "<br /><br />\n";

//show the content
	echo "<div id='ajax_response'></div>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>