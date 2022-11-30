<?php
//get the browser version
	$user_agent = http_user_agent();
	$browser_version =  $user_agent['version'];
	$browser_name =  $user_agent['name'];
	$browser_version_array = explode('.', $browser_version);

//set the doctype
	echo ($browser_name != "Internet Explorer") ? "<!DOCTYPE html>\n" : "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";

//get the php self path and set a variable with only the directory path
	$php_self_array = explode ("/", $_SERVER['PHP_SELF']);
	$php_self_dir = '';
	foreach ($php_self_array as &$value) {
		if (substr($value, -4) != ".php") {
			$php_self_dir .= $value."/";
		}
	}
	unset($php_self_array);
	if (strlen(PROJECT_PATH) > 0) {
		$php_self_dir = substr($php_self_dir, strlen(PROJECT_PATH), strlen($php_self_dir));
	}
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" type="text/css" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap-datetimepicker.min.css" />
<link rel="stylesheet" type="text/css" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap-colorpicker.min.css">
<link rel="stylesheet" type="text/css" href="<!--{project_path}-->/themes/<?php echo escape($_SESSION['domain']['template']['name']); ?>/css.php<?php echo ($default_login) ? '?login=default' : null; ?>">
<?php
//load custom css
	if ($_SESSION['theme']['custom_css']['text'] != '') {
		echo "<link rel='stylesheet' type='text/css' href='".escape($_SESSION['theme']['custom_css']['text'])."'>\n\n";
	}

//set fav icon
	$favicon = (isset($_SESSION['theme']['favicon']['text'])) ? escape($_SESSION['theme']['favicon']['text']) : '<!--{project_path}-->/themes/default/favicon.ico';
	echo "<link rel='icon' href='".$favicon."'>\n";
?>

<title><!--{title}--></title>

<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/jquery/jquery-1.11.1.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/jquery/jquery.autosize.input.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/momentjs/moment.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap.min.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap-datetimepicker.min.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap-colorpicker.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap-pwstrength.min.js"></script>
<?php
//web font loader
	if ($_SESSION['theme']['font_loader']['text'] == 'true') {
		if ($_SESSION['theme']['font_retrieval']['text'] != 'asynchronous') {
			$font_loader_version = ($_SESSION['theme']['font_loader_version']['text'] != '') ? escape($_SESSION['theme']['font_loader_version']['text']) : 1;
			echo "<script language='JavaScript' type='text/javascript' src='//ajax.googleapis.com/ajax/libs/webfont/".escape($font_loader_version)."/webfont.js'></script>\n";
		}
		echo "<script language='JavaScript' type='text/javascript' src='<!--{project_path}-->/resources/fonts/web_font_loader.php?v=".escape($font_loader_version)."'></script>\n";
	}
?>
<script language="JavaScript" type="text/javascript">

	//display message bar via js
		function display_message(msg, mood, delay) {
			mood = (typeof mood !== 'undefined') ? mood : 'default';
			delay = (typeof delay !== 'undefined') ? delay : <?php echo (1000 * (float) $_SESSION['theme']['message_delay']['text']); ?>;
			if (msg !== '') {
				var message_text = $(document.createElement('div'));
				message_text.addClass('message_text message_mood_'+mood);
				message_text.html(msg);
				message_text.click(function() {
					var object = $(this);
					object.clearQueue().finish();
					object.animate({height: '0', 'font-size': '0', 'border-bottom-width': '0'}, 1000).animate({opacity: 0});
				} );
				$("#messages_container").append(message_text);
				message_text.animate({opacity: 1}, 'fast').delay(delay).animate({height: '0', 'font-size': '0', 'border-bottom-width': '0'}, 1000).animate({opacity: 0});
			}
		}

	$(document).ready(function() {

<?php	echo messages::html(true, "		");?>

		//hide message bar on hover
			$("#message_text").mouseover(function() { $(this).hide(); $("#message_container").hide(); });

		<?php
		if (permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
			?>

			//domain selector controls
				$(".domain_selector_domain").click(function() { show_domains(); });
				$("#domains_hide").click(function() { hide_domains(); });

				function show_domains() {
					$('#domains_visible').val(1);
					var scrollbar_width = (window.innerWidth - $(window).width()); //gold: only solution that worked with body { overflow:auto } (add -ms-overflow-style: scrollbar; to <body> style for ie 10+)
					if (scrollbar_width > 0) {
						$("body").css({'margin-right':scrollbar_width, 'overflow':'hidden'}); //disable body scroll bars
						$(".navbar").css('margin-right',scrollbar_width); //adjust navbar margin to compensate
						$("#domains_container").css('right',-scrollbar_width); //domain container right position to compensate
					}
					$(document).scrollTop(0);
					$("#domains_container").show();
					$("#domains_block").animate({marginRight: '+=300'}, 400, function() {
						$("#domain_filter").focus();
					});
				}

				function hide_domains() {
					$('#domains_visible').val(0);
					$(document).ready(function() {
						$("#domains_block").animate({marginRight: '-=300'}, 400, function() {
							$("#domain_filter").val('');
							domain_search($("#domain_filter").val());
							$(".navbar").css('margin-right','0'); //restore navbar margin
							$("#domains_container").css('right','0'); //domain container right position
							$("#domains_container").hide();
							$("body").css({'margin-right':'0','overflow':'auto'}); //enable body scroll bars
						});
					});
				}

			<?php
			key_press('escape', 'up', 'document', null, null, "if ($('#domains_visible').val() == 0) { show_domains(); } else { hide_domains(); }", false);
		}
		?>

		//link table rows (except the last - the list_control_icons cell) on a table with a class of 'tr_hover', according to the href attribute of the <tr> tag
			$('.tr_hover tr').each(function(i,e) {
			  $(e).children('td:not(.list_control_icon,.list_control_icons,.tr_link_void)').click(function() {
				 var href = $(this).closest("tr").attr("href");
				 if (href) { window.location = href; }
			  });
			});

		//apply the auto-size jquery script to all text inputs
			$("input[type=text].txt,input[type=number].txt,input[type=password].txt,input[type=text].formfld,input[type=number].formfld,input[type=password].formfld").not('.datetimepicker,.datepicker').autosizeInput();

		//apply bootstrap-datetime plugin
			$(function() {
				$('.datetimepicker').datetimepicker({
					format: 'YYYY-MM-DD HH:mm',
					showTodayButton: true,
					showClear: true,
					showClose: true,
				});
				$('.datepicker').datetimepicker({
					format: 'YYYY-MM-DD',
					showTodayButton: true,
					showClear: true,
					showClose: true,
				});
			});

		//apply bootstrap-colorpicker plugin
			$(function(){
				$('.colorpicker').colorpicker({
					align: 'left',
					customClass: 'colorpicker-2x',
					sliders: {
						saturation: {
							maxLeft: 200,
							maxTop: 200
						},
						hue: {
							maxTop: 200
						},
						alpha: {
							maxTop: 200
						}
					}
				});
			});

		//apply password strength plugin
			$('#password').pwstrength({
				common: {
					minChar: 8,
					usernameField: '#username',
				},
				/* rules: { },  */
				ui: {
					//				very weak weak		normal	   medium	  strong	 very strong
					colorClasses: ["danger", "warning", "warning", "warning", "success", "success"],
					progressBarMinPercentage: 15,
					showVerdicts: false,
					viewports: {
						progress: "#pwstrength_progress"
					}
				}
			});

		<?php if ($_SESSION['theme']['menu_brand_image']['text'] != '' && $_SESSION['theme']['menu_brand_image_hover']['text'] != '') { ?>
			//crossfade menu brand images (if hover version set)
				$(function(){
					$('#menu_brand_image').mouseover(function(){
						$(this).fadeOut('fast', function(){
							$('#menu_brand_image_hover').fadeIn('fast');
						});
					});
					$('#menu_brand_image_hover').mouseout(function(){
						$(this).fadeOut('fast', function(){
							$('#menu_brand_image').fadeIn('fast');
						});
					});
				});
		<?php } ?>
		

	});

	//audio playback functions
		var recording_audio;
		var audio_clock;

		function recording_play(recording_id) {
			if (document.getElementById('recording_progress_bar_'+recording_id)) {
				document.getElementById('recording_progress_bar_'+recording_id).style.display='';
			}
			recording_audio = document.getElementById('recording_audio_'+recording_id);

			if (recording_audio.paused) {
				recording_audio.volume = 1;
				recording_audio.play();
				document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo str_replace("class='list_control_icon'", "class='list_control_icon' style='opacity: 1;'", $v_link_label_pause); ?>";
				audio_clock = setInterval(function () { update_progress(recording_id); }, 20);

				$("[id*=recording_button]").not("[id*=recording_button_"+recording_id+"]").html("<?php echo $v_link_label_play; ?>");
				$("[id*=recording_progress_bar]").not("[id*=recording_progress_bar_"+recording_id+"]").css('display', 'none');
				
				$('audio').each(function(){
					if ($(this).get(0) != recording_audio) {
						$(this).get(0).pause(); // Stop playing
						$(this).get(0).currentTime = 0; // Reset time
					}
				});
			}
			else {
				recording_audio.pause();
				document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo $v_link_label_play; ?>";
				clearInterval(audio_clock);
			}
		}

		function recording_stop(recording_id) {
			recording_reset(recording_id);
			clearInterval(audio_clock);
		}

		function recording_reset(recording_id) {
			recording_audio = document.getElementById('recording_audio_'+recording_id);
			recording_audio.pause();
			recording_audio.currentTime = 0;
			if (document.getElementById('recording_progress_bar_'+recording_id)) {
				document.getElementById('recording_progress_bar_'+recording_id).style.display='none';
			}
			document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo $v_link_label_play; ?>";
			clearInterval(audio_clock);
		}

		function update_progress(recording_id) {
			recording_audio = document.getElementById('recording_audio_'+recording_id);
			var recording_progress = document.getElementById('recording_progress_'+recording_id);
			var value = 0;
			if (recording_audio.currentTime > 0) {
				value = (100 / recording_audio.duration) * recording_audio.currentTime;
			}
			recording_progress.style.marginLeft = value + "%";
			if (parseInt(recording_audio.duration) > 30) { //seconds
				clearInterval(audio_clock);
			}
		}

</script>

<!--{head}-->

</head>

<?php
//add multilingual support
	$language = new text;
	$text = $language->get(null,'themes/default');
?>

<body onload="<?php echo $onload;?>">

	<div id='messages_container'></div>

	<?php
	//logged in show the domains block
	if (strlen($_SESSION["username"]) > 0 && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
		?>
		<div id="domains_container">
			<input type="hidden" id="domains_visible" value="0">
			<div id="domains_block">
				<div id="domains_header">
					<input id="domains_hide" type="button" class="btn" style="float: right" value="<?php echo $text['theme-button-close']; ?>">
					<?php
					if (file_exists($_SERVER["DOCUMENT_ROOT"]."/app/domains/domains.php")) {
						$href = '/app/domains/domains.php';
					}
					else {
						$href = '/core/domain_settings/domains.php';
					}
					echo "<a href=\"".$href."\"><b style=\"color: #000;\">".$text['theme-title-domains']."</b></a> (".sizeof($_SESSION['domains']).")";
					?>
					<br><br>
					<input type="text" id="domain_filter" class="formfld" style="margin-left: 0; min-width: 100%; width: 100%;" placeholder="<?php echo $text['theme-label-search']; ?>" onkeyup="domain_search(this.value);">
				</div>
				<div id="domains_list">
					<?php
					$bgcolor1 = "#eaedf2";
					$bgcolor2 = "#fff";
					foreach($_SESSION['domains'] as $domain) {
						$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
						$bgcolor = ($domain['domain_uuid'] == $_SESSION['domain_uuid']) ? "#eeffee" : $bgcolor;
						echo "<div id=\"".escape($domain['domain_name'])."\" class='domains_list_item' style='background-color: ".$bgcolor."' onclick=\"document.location.href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".escape($domain['domain_uuid'])."&domain_change=true';\">";
						echo "<a href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".escape($domain['domain_uuid'])."&domain_change=true' ".(($domain['domain_uuid'] == $_SESSION['domain_uuid']) ? "style='font-weight: bold;'" : null).">".escape($domain['domain_name'])."</a>\n";
						if ($domain['domain_description'] != '') {
							echo "<span class=\"domain_list_item_description\"> - ".escape($domain['domain_description'])."</span>\n";
						}
						echo "</div>\n";
						$ary_domain_names[] = $domain['domain_name'];
						$ary_domain_descs[] = str_replace('"','\"',$domain['domain_description']);
					}
					?>
				</div>

				<script>
					var domain_names = new Array("<?php echo implode('","', $ary_domain_names)?>");
					var domain_descs = new Array("<?php echo implode('","', $ary_domain_descs)?>");

					function domain_search(criteria) {
						for (var x = 0; x < domain_names.length; x++) {
							if (domain_names[x].toLowerCase().match(criteria.toLowerCase()) || domain_descs[x].toLowerCase().match(criteria.toLowerCase())) {
								document.getElementById(domain_names[x]).style.display = '';
							}
							else {
								document.getElementById(domain_names[x]).style.display = 'none';
							}
						}
					}
				</script>

			</div>
		</div>
		<?php
	}


	// qr code container for contacts
	echo "<div id='qr_code_container' style='display: none;' onclick='$(this).fadeOut(400);'>";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'><tr><td align='center' valign='middle'>";
	echo "		<span id='qr_code' onclick=\"$('#qr_code_container').fadeOut(400);\"></span>";
	echo "	</td></tr></table>";
	echo "</div>";


	if (!$default_login) {

		//*************** BOOTSTRAP MENU ********************************
		function show_menu($menu_array, $menu_style, $menu_position) {
			global $text;

			//determine menu behavior
				switch ($menu_style) {
					case 'inline':
						$menu_type = 'default';
						$menu_width = 'calc(100% - 20px)';
						$menu_brand = false;
						$menu_corners = null;
						break;
					case 'static':
						$menu_type = 'static-top';
						$menu_width = 'calc(100% - 40px)';
						$menu_brand = true;
						$menu_corners = "style='-webkit-border-radius: 0 0 4px 4px; -moz-border-radius: 0 0 4px 4px; border-radius: 0 0 4px 4px;'";
						break;
					case 'fixed':
					default:
						$menu_position = ($menu_position != '') ? $menu_position : 'top';
						$menu_type = 'fixed-'.$menu_position;
						$menu_width = 'calc(90% - 20px)';
						$menu_brand = true;
						$menu_corners = null;
				}
			?>

			<nav class="navbar navbar-inverse navbar-<?php echo $menu_type; ?>" <?php echo $menu_corners; ?>>
				<div class="container-fluid" style='width: <?php echo $menu_width; ?>; padding: 0;'>
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" <?php echo ($menu_style == 'fixed') ? "style='margin-right: -2%;'" : null; ?> data-toggle="collapse" data-target="#main_navbar" aria-expanded="false" aria-controls="navbar">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar" style='margin-top: 1px;'></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<?php
						if ($menu_brand) {
							//define menu brand link
								if (strlen(PROJECT_PATH) > 0) {
									$menu_brand_link = PROJECT_PATH;
								}
								else if (!$default_login) {
									$menu_brand_link = '/';
								}
							//define menu brand mark
								$menu_brand_text = ($_SESSION['theme']['menu_brand_text']['text'] != '') ? escape($_SESSION['theme']['menu_brand_text']['text']) : "FusionPBX";
								if ($_SESSION['theme']['menu_brand_type']['text'] == 'image' || $_SESSION['theme']['menu_brand_type']['text'] == '') {
									$menu_brand_image = ($_SESSION['theme']['menu_brand_image']['text'] != '') ? escape($_SESSION['theme']['menu_brand_image']['text']) : PROJECT_PATH."/themes/default/images/logo.png";
									echo "<a href='".$menu_brand_link."'>";
									echo "<img id='menu_brand_image' class='navbar-logo' ".(($menu_style == 'fixed') ? "style='margin-right: -2%;'" : null)." src='".$menu_brand_image."' title=\"".escape($menu_brand_text)."\">";
									if ($_SESSION['theme']['menu_brand_image_hover']['text'] != '') {
										echo "<img id='menu_brand_image_hover' class='navbar-logo' style='display: none;' src='".$_SESSION['theme']['menu_brand_image_hover']['text']."' title=\"".escape($menu_brand_text)."\">";
									}
									echo "</a>";
								}
								else if ($_SESSION['theme']['menu_brand_type']['text'] == 'text') {
									echo "<div class='pull-left'><a class='navbar-brand' href=\"".$menu_brand_link."\">".$menu_brand_text."</a></div>\n";
								}
						}
						//domain name/selector (xs)
							if ($_SESSION["username"] != '' && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
								echo "<span class='pull-right visible-xs'><a href='#' class='domain_selector_domain' title='".escape($text['theme-label-open_selector'])."'>".escape($_SESSION['domain_name'])."</a></span>\n";
							}
						?>
					</div>
					<div class="collapse navbar-collapse" id="main_navbar">
						<ul class="nav navbar-nav">
							<?php
							foreach ($menu_array as $index_main => $menu_parent) {
								$mod_li = "";
								$mod_a_1 = "";
								$submenu = false;
								if (is_array($menu_parent['menu_items']) && sizeof($menu_parent['menu_items']) > 0) {
									$mod_li = "class='dropdown' ";
									$mod_a_1 = "class='dropdown-toggle text-left' data-toggle='dropdown' ";
									$submenu = true;
								}
								$mod_a_2 = ($menu_parent['menu_item_link'] != '' && !$submenu) ? $menu_parent['menu_item_link'] : '#';
								$mod_a_3 = ($menu_parent['menu_item_category'] == 'external') ? "target='_blank' " : null;
								if ($_SESSION['theme']['menu_main_icons']['boolean'] != 'false') {
									if ($menu_parent['menu_item_icon'] != '' && substr_count($menu_parent['menu_item_icon'], 'glyphicon-') > 0) {
										$menu_main_icon = "<span class='glyphicon ".$menu_parent['menu_item_icon']."' title=\"".escape($menu_parent['menu_language_title'])."\"></span>";
									}
									else {
										$menu_main_icon = null;
									}
									$menu_main_item = "<span class='hidden-sm' style='margin-left: 5px;'>".$menu_parent['menu_language_title']."</span>";
								}
								else {
									$menu_main_item = $menu_parent['menu_language_title'];
								}
								echo "<li ".$mod_li.">\n";
								echo "<a ".$mod_a_1." href='".$mod_a_2."' ".$mod_a_3.">".$menu_main_icon.$menu_main_item."</a>\n";
								if ($submenu) {
									echo "<ul class='dropdown-menu'>\n";
									foreach ($menu_parent['menu_items'] as $index_sub => $menu_sub) {
										$mod_a_2 = $menu_sub['menu_item_link'];
										if ($mod_a_2 == '') {
											$mod_a_2 = '#';
										}
										else if (($menu_sub['menu_item_category'] == 'internal') || (($menu_sub['menu_item_category'] == 'external') && substr($mod_a_2,0,1) == '/')) {
											// accomodate adminer auto-login, if enabled
												if (substr($mod_a_2,0,22) == '/app/adminer/index.php') {
													global $db_type;
													$mod_a_2 .= '?'.(($db_type == 'mysql') ? 'server' : $db_type).'&db=fusionpbx&ns=public';
													$mod_a_2 .= ($_SESSION['adminer']['auto_login']['boolean'] == 'true') ? "&username=auto" : null;
												}
											$mod_a_2 = PROJECT_PATH.$mod_a_2;
										}
										$mod_a_3 = ($menu_sub['menu_item_category'] == 'external') ? "target='_blank' " : null;
										if ($_SESSION['theme']['menu_sub_icons']['boolean'] != 'false') {
											if ($menu_sub['menu_item_icon'] != '' && substr_count($menu_sub['menu_item_icon'], 'glyphicon-') > 0) {
												$menu_sub_icon = "<span class='glyphicon ".escape($menu_sub['menu_item_icon'])."'></span>";
											}
											else {
												$menu_sub_icon = null;
											}
										}
										echo "<li><a href='".$mod_a_2."' ".$mod_a_3.">".(($_SESSION['theme']['menu_sub_icons']) ? "<span class='glyphicon glyphicon-minus visible-xs pull-left' style='margin: 4px 10px 0 25px;'></span>" : null).escape($menu_sub['menu_language_title']).$menu_sub_icon."</a></li>\n";
									}
									echo "</ul>\n";
								}
								echo "</li>\n";
							}
							?>
						</ul>
						<?php
						echo "<span class='pull-right hidden-xs' style='white-space: nowrap;'>";
						//domain name/selector (sm+)
							if ($_SESSION["username"] != '' && permission_exists("domain_select") && count($_SESSION['domains']) > 1 && $_SESSION['theme']['domain_visible']['text'] == 'true') {
								echo "<a href='#' class='domain_selector_domain' title='".$text['theme-label-open_selector']."'>".escape($_SESSION['domain_name'])."</a>";
							}
						//logout icon
							if ($_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
								$username_full = $_SESSION['username'].((count($_SESSION['domains']) > 1) ? "@".$_SESSION["user_context"] : null);
								echo "<a href='".PROJECT_PATH."/logout.php' class='logout_icon' title=\"".$text['theme-label-logout']."\" onclick=\"return confirm('".$text['theme-confirm-logout']."')\"><span class='glyphicon glyphicon-log-out'></span></a>";
								unset($username_full);
							}
						echo "</span>";
						?>
					</div>
				</div>
			</nav>

			<?php
		}


		//determine menu configuration
			$menu = new menu;
			$menu->db = $db;
			$menu->menu_uuid = $_SESSION['domain']['menu']['uuid'];
			$menu_array = $menu->menu_array();
			unset($menu);

			$menu_style = ($_SESSION['theme']['menu_style']['text'] != '') ? $_SESSION['theme']['menu_style']['text'] : 'fixed';
			$menu_position = ($_SESSION['theme']['menu_position']['text']) ? $_SESSION['theme']['menu_position']['text'] : 'top';
			$open_container = "<div class='container-fluid' style='padding: 0;' align='center'>";

			switch ($menu_style) {
				case 'inline':
					$logo_align = ($_SESSION['theme']['logo_align']['text'] != '') ? $_SESSION['theme']['logo_align']['text'] : 'left';
					$logo_style = ($_SESSION['theme']['logo_style']['text'] != '') ? $_SESSION['theme']['logo_style']['text'] : '';
					echo str_replace("center", $logo_align, $open_container);
					if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/core/install/install.php") {
						$logo = ($_SESSION['theme']['logo']['text'] != '') ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH."/themes/default/images/logo.png";
						echo "<a href='".((PROJECT_PATH != '') ? PROJECT_PATH : '/')."'><img src='".$logo."' style='padding: 15px 20px;$logo_style'></a>";
					}

					show_menu($menu_array, $menu_style, $menu_position);
					break;
				case 'static':
					echo $open_container;
					show_menu($menu_array, $menu_style, $menu_position);
					break;
				case 'fixed':
					show_menu($menu_array, $menu_style, $menu_position);
					echo $open_container;
			}
			?>

			<div id='main_content'>
				<!--{body}-->
			</div>
			<div id='footer'>
				<span class='footer'><?php echo (isset($_SESSION['theme']['footer']['text'])) ? $_SESSION['theme']['footer']['text'] : "&copy; ".$text['theme-label-copyright']." 2008 - ".date("Y")." <a href='http://www.fusionpbx.com' class='footer' target='_blank'>fusionpbx.com</a> ".$text['theme-label-all_rights_reserved']; ?></span>
			</div>
		</div>

		<?php
		// note: div above matches $open_container
	}

	// default login being used
	else {
		$logo = (isset($_SESSION['theme']['logo']['text'])) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH."/themes/default/images/logo.png";
		?>
		<div id='default_login'>
			<a href='<?php echo PROJECT_PATH; ?>/'><img id='login_logo' src='<?php echo escape($logo); ?>'></a><br />
			<!--{body}-->
		</div>
		<div id='footer_login'>
			<span class='footer'><?php echo (isset($_SESSION['theme']['footer']['text'])) ? $_SESSION['theme']['footer']['text'] : "&copy; ".$text['theme-label-copyright']." 2008 - ".date("Y")." <a href='http://www.fusionpbx.com' class='footer' target='_blank'>fusionpbx.com</a> ".$text['theme-label-all_rights_reserved']; ?></span>
		</div>
		<?php
		unset($_SESSION['background_image']);
	}
	?>

</body>
</html>
