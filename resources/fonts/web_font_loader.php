<?php
include "root.php";
require_once "resources/require.php";

$font_loader_version = ($_GET['v'] != '') ? $_GET['v'] : 1;

header("Content-type: text/javascript; charset: UTF-8");

//web font loader
	if ($_SESSION['theme']['font_loader']['text'] == 'true') {
		//parse font names
			if (is_array($_SESSION['theme']) && sizeof($_SESSION['theme']) > 0) {
				foreach ($_SESSION['theme'] as $subcategory => $type) {
					if (substr_count($subcategory, '_font') > 0) {
						$font_string = $type['text'];
						if ($font_string != '') {
							if (substr_count($font_string, ',') > 0) {
								$tmp_array = explode(',', $font_string);
							}
							else {
								$tmp_array[] = $font_string;
							}
							foreach ($tmp_array as $font_name) {
								$font_name = trim($font_name, "'");
								$font_name = trim($font_name, '"');
								$font_name = trim($font_name);
								$fonts[] = $font_name;
							}
						}
					}
					unset($tmp_array);
				}
			}

		//optimize fonts array
			if (is_array($fonts) && sizeof($fonts) > 0) {
				$fonts = array_unique($fonts);
				$common_fonts = 'serif,sans-serif,arial,arial black,arial narrow,calibri,'.
					'candara,apple gothic,geneva,tahoma,microsoft sans serif,'.
					'lucidia,lucidia console,monaco,lucidia sans unicode,'.
					'lucidiagrande,consolas,menlo,trebuchet,trebuchet ms,'.
					'helvetica,times,times new roman,courier,courier new,'.
					'impact,comic sans,comic sans ms,georgia,palatino,'.
					'palatino linotype,verdana,franklin gothic,'.
					'franklin gothic medium,gill sans,gill sans mt,'.
					'brush script,corbel,segoe,segoe ui,optima,';
				$common_fonts = explode(',', $common_fonts);
				foreach ($fonts as $index => $font) {
					if (in_array(strtolower($font), $common_fonts)) {
						unset($fonts[$index]);
					}
				}
			}

		//load fonts
			if (is_array($fonts) && sizeof($fonts) > 0) {
				if ($_SESSION['theme']['font_retrieval']['text'] == 'asynchronous') {
					?>
					WebFontConfig = {
						google: {
							families: ['<?php echo implode("','", $fonts); ?>']
						}
					};
					(function(d) {
						var wf = d.createElement('script'), s = d.scripts[0];
						wf.src = '//ajax.googleapis.com/ajax/libs/webfont/<?php echo $font_loader_version; ?>/webfont.js';
						s.parentNode.insertBefore(wf, s);
					})(document);
					<?php
				}
				else { //synchronous
					?>
					WebFont.load({
						google: {
							families: ['<?php echo implode("','", $fonts); ?>']
						}
					});
					<?php
				}
			}
	}
?>




























