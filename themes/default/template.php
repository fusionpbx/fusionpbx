{* <?php *}

{*//set the doctype *}
	{if $browser_name == 'Internet Explorer'}
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	{else}
		<!DOCTYPE html>
	{/if}

<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
<meta charset='utf-8'>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<meta http-equiv='X-UA-Compatible' content='IE=edge'>
<meta name='viewport' content='width=device-width, initial-scale=1'>

{*//external css files *}
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap-tempusdominus.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap-colorpicker.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/fontawesome/css/all.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/themes/default/css.php'>

{*//link to custom css file *}
	{if $settings.theme.custom_css}
		<link rel='stylesheet' type='text/css' href='{$settings.theme.custom_css}'>
	{/if}

{*//set favorite icon *}
	<link rel='icon' href='{$settings.theme.favicon}'>

{*//document title *}
	<title>{$document_title}</title>

{*//remote javascript *}
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/jquery/jquery.min.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/jquery/jquery.autosize.input.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/momentjs/moment-with-locales.min.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/bootstrap/js/bootstrap.min.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/bootstrap/js/bootstrap-tempusdominus.min.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/bootstrap/js/bootstrap-colorpicker.min.js.php'></script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/bootstrap/js/bootstrap-pwstrength.min.js.php'></script>
	<script language='JavaScript' type='text/javascript'>{literal}window.FontAwesomeConfig = { autoReplaceSvg: false }{/literal}</script>
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/fontawesome/js/solid.min.js.php' defer></script>

{*//web font loader *}
	{if $settings.theme.font_loader == 'true'}
		{if $settings.theme.font_retrieval != 'asynchronous'}
			<script language='JavaScript' type='text/javascript' src='//ajax.googleapis.com/ajax/libs/webfont/{$settings.theme.font_loader_version}/webfont.js'></script>
		{/if}
		<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/fonts/web_font_loader.php?v={$settings.theme.font_loader_version}'></script>
	{/if}

{*//local javascript *}
	<script language='JavaScript' type='text/javascript'>

		//message bar display
			{literal}
			function display_message(msg, mood, delay) {
				mood = typeof mood !== 'undefined' ? mood : 'default';
				delay = typeof delay !== 'undefined' ? delay : {/literal}{$settings.theme.message_delay}{literal};
				if (msg !== '') {
					var message_text = $(document.createElement('div'));
					message_text.addClass('message_text message_mood_'+mood);
					message_text.html(msg);
					message_text.on('click', function() {
						var object = $(this);
						object.clearQueue().finish();
						$('#message_container div').remove();
						$('#message_container').css({opacity: 0, 'height': 0}).css({'height': 'auto'});
					} );
					$('#message_container').append(message_text);
					message_text.css({'height': 'auto'}).animate({opacity: 1}, 250, function(){
						$('#message_container').delay(delay).animate({opacity: 0, 'height': 0}, 500, function() {
							$('#message_container div').remove();
							$('#message_container').animate({opacity: 1}, 300).css({'height': 'auto'});
						});
					});
				}
			}
			{/literal}

		{if $settings.theme.menu_style == 'side'}
			//side menu visibility toggle
				{literal}
				var menu_side_state = 'contracted';
				function menu_side_contract() {
					$('.menu_side_sub').slideUp(180);
					$('.menu_side_item_title').hide();
					{/literal}
					{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == ''}
						{literal}
						$('#menu_brand_image_expanded').fadeOut(180, function() {
							$('#menu_brand_image_contracted').fadeIn(180);
						});
						{/literal}
					{elseif $settings.theme.menu_brand_type == 'image_text'}
						{literal}
						$('.menu_brand_text').hide();
						$('#menu_brand_image_contracted').animate({ width: '20px', 'margin-left': '-2px' }, 250);
						{/literal}
					{else if $settings.theme.menu_brand_type == 'text'}
						{literal}
						$('.menu_brand_text').fadeOut(180);
						{/literal}
					{/if}
					{literal}
					$('#menu_side_container').animate({ width: '{/literal}{$settings.theme.menu_side_width_contracted}{literal}px' }, 250);
					$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_contracted}{literal} }, 250, function() {
						menu_side_state = 'contracted';
					});

					$('.menu_side_contract').hide();
					$('.menu_side_expand').show();
				}

				function menu_side_expand() {
					{/literal}
					{if $settings.theme.menu_brand_type == 'image_text'}
						{literal}
						$('#menu_brand_image_contracted').animate({ width: '30px', 'margin-left': '0' }, 250);
						{/literal}
					{elseif $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == ''}
						{literal}
						$('#menu_brand_image_contracted').fadeOut(180);
						{/literal}
					{/if}
					{literal}
					$('#menu_side_container').animate({ width: '{/literal}{$settings.theme.menu_side_width_expanded}{literal}px' }, 250);
					$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_expanded}{literal} }, 250, function() {
						$('.menu_brand_text').fadeIn(180);
						$('.menu_side_item_title').fadeIn(180);
						{/literal}
						{if $settings.theme.menu_brand_type != 'none'}
							{literal}
							$('.menu_side_contract').fadeIn(180);
							{/literal}
						{/if}
						{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == ''}
							{literal}
							$('#menu_brand_image_expanded').fadeIn(180);
							{/literal}
						{/if}
						{literal}
						menu_side_state = 'expanded';
					});
					{/literal}
					{if $settings.theme.menu_brand_type == 'none'}
						{literal}
						$('.menu_side_contract').show();
						{/literal}
					{/if}
					{literal}
					$('.menu_side_expand').hide();
				}
				{/literal}
		{/if}

	{literal}
	$(document).ready(function() {
		{/literal}

		{$messages}

		//message bar hide on hover
			{literal}
			$('#message_container').on('mouseenter',function() {
				$('#message_container div').remove();
				$('#message_container').css({opacity: 0, 'height': 0}).css({'height': 'auto'});
			});
			{/literal}

		//domain selector controls
			{if $domain_selector_enabled}
				{literal}
				$('.domain_selector_domain').on('click', function() { show_domains(); });
				$('#header_domain_selector_domain').on('click', function() { show_domains(); });
				$('#domains_hide').on('click', function() { hide_domains(); });

				function show_domains() {
					$('#domains_visible').val(1);
					var scrollbar_width = (window.innerWidth - $(window).width()); //gold: only solution that worked with body { overflow:auto } (add -ms-overflow-style: scrollbar; to <body> style for ie 10+)
					if (scrollbar_width > 0) {
						$('body').css({'margin-right':scrollbar_width, 'overflow':'hidden'}); //disable body scroll bars
						$('.navbar').css('margin-right',scrollbar_width); //adjust navbar margin to compensate
						$('#domains_container').css('right',-scrollbar_width); //domain container right position to compensate
					}
					$(document).scrollTop(0);
					$('#domains_container').show();
					$('#domains_block').animate({marginRight: '+=300'}, 400, function() {
						$('#domain_filter').trigger('focus');
					});
				}

				function hide_domains() {
					$('#domains_visible').val(0);
					$(document).ready(function() {
						$('#domains_block').animate({marginRight: '-=300'}, 400, function() {
							$('#domain_filter').val('');
							domain_search($('#domain_filter').val());
							$('.navbar').css('margin-right','0'); //restore navbar margin
							$('#domains_container').css('right','0'); //domain container right position
							$('#domains_container').hide();
							$('body').css({'margin-right':'0','overflow':'auto'}); //enable body scroll bars
							document.activeElement.blur();
						});
					});
				}
				{/literal}
			{/if}

		//keyboard shortcut scripts

		//key: [enter] - retain default behavior to submit form, when present - note: safari does not honor the first submit element when hiding it using 'display: none;' in the setAttribute method
			{if $settings.theme.keyboard_shortcut_submit_enabled}
				{literal}
				var action_bar_actions, first_form, first_submit;
				action_bar_actions = document.querySelector('div#action_bar.action_bar > div.actions');
				first_form = document.querySelector('form#frm');

				if (action_bar_actions !== null) {
					if (first_form !== null) {
						first_submit = document.createElement('input');
						first_submit.type = 'submit';
						first_submit.id = 'default_submit';
						first_submit.setAttribute('style','position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;');
						first_form.prepend(first_submit);
						window.addEventListener('keydown',function(e){
							if (e.which == 13 && (e.target.tagName == 'INPUT' || e.target.tagName == 'SELECT')) {
								if (typeof window.submit_form === 'function') { submit_form(); }
								else { document.getElementById('frm').submit(); }
							}
						});
					}
				}
				{/literal}
			{/if}

		//common (used by delete and toggle)
			{if $settings.theme.keyboard_shortcut_delete_enabled || $settings.theme.keyboard_shortcut_toggle_enabled}
				var list_checkboxes;
				list_checkboxes = document.querySelectorAll('table.list tr.list-row td.checkbox input[type=checkbox]');
			{/if}

		//keyup event listener
			{literal}
			window.addEventListener('keyup', function(e) {
				{/literal}

				//key: [escape] - close modal window, if open, or toggle domain selector
					{literal}
					if (e.which == 27) {
						e.preventDefault();
						var modals, modal_visible, modal;
						modal_visible = false;
						modals = document.querySelectorAll('div.modal-window');
						if (modals.length !== 0) {
							for (var x = 0, max = modals.length; x < max; x++) {
								modal = document.getElementById(modals[x].id);
								if (window.getComputedStyle(modal).getPropertyValue('opacity') == 1) {
									modal_visible = true;
								}
							}
						}
						if (modal_visible) {
							modal_close();
						}
						{/literal}
						{if $domain_selector_enabled}
							{literal}
							else {
								if (document.getElementById('domains_visible').value == 0) {
									show_domains();
								}
								else {
									hide_domains();
								}
							}
							{/literal}
						{/if}
						{literal}
					}
					{/literal}

				//key: [insert], list: to add
					{if $settings.theme.keyboard_shortcut_add_enabled}
						{literal}
						if (e.which == 45 && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							e.preventDefault();
							var list_add_button;
							list_add_button = document.getElementById('btn_add');
							if (list_add_button === null || list_add_button === 'undefined') {
								list_add_button = document.querySelector('button[name=btn_add]');
							}
							if (list_add_button !== null) { list_add_button.click(); }
						}
						{/literal}
					{/if}

				//key: [delete], list: to delete checked, edit: to delete
					{if $settings.theme.keyboard_shortcut_delete_enabled}
						{literal}
						if (e.which == 46 && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							e.preventDefault();
							if (list_checkboxes.length !== 0) {
								var list_delete_button;
								list_delete_button = document.querySelector('button[name=btn_delete]');
								if (list_delete_button === null || list_delete_button === 'undefined') {
									list_delete_button = document.getElementById('btn_delete');
								}
								if (list_delete_button !== null) { list_delete_button.click(); }
							}
							else {
								var edit_delete_button;
								edit_delete_button = document.querySelector('button[name=btn_delete]');
								if (edit_delete_button === null || edit_delete_button === 'undefined') {
									edit_delete_button = document.getElementById('btn_delete');
								}
								if (edit_delete_button !== null) { edit_delete_button.click(); }
							}
						}
						{/literal}
					{/if}

		//keyup end
			{literal}
			});
			{/literal}

		//keydown event listener
			{literal}
			window.addEventListener('keydown', function(e) {
				{/literal}

				//key: [space], list: to toggle checked - note: for default [space] checkbox behavior (ie. toggle focused checkbox) include in the if statement: && !(e.target.tagName == 'INPUT' && e.target.type == 'checkbox')
					{if $settings.theme.keyboard_shortcut_toggle_enabled}
						{literal}
						if (e.which == 32 && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA' && list_checkboxes.length !== 0) {
							e.preventDefault();
							var list_toggle_button;
							list_toggle_button = document.querySelector('button[name=btn_toggle]');
							if (list_toggle_button === null || list_toggle_button === 'undefined') {
								list_toggle_button = document.getElementById('btn_toggle');
							}
							if (list_toggle_button !== null) { list_toggle_button.click(); }
						}
						{/literal}
					{/if}

				//key: [ctrl]+[a], list,edit: to check all
					{if $settings.theme.keyboard_shortcut_check_all_enabled}
						{literal}
						if ((((e.which == 97 || e.which == 65) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || e.which == 19) && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							var list_checkbox_all;
							list_checkbox_all = document.querySelectorAll('table.list tr.list-header th.checkbox input[name=checkbox_all]');
							if (list_checkbox_all !== null && list_checkbox_all.length > 0) {
								e.preventDefault();
								for (var x = 0, max = list_checkbox_all.length; x < max; x++) {
									list_checkbox_all[x].click();
								}
							}
							var edit_checkbox_all;
							edit_checkbox_all = document.querySelectorAll('td.edit_delete_checkbox_all > span > input[name=checkbox_all]');
							if (edit_checkbox_all !== null && edit_checkbox_all.length > 0) {
								e.preventDefault();
								for (var x = 0, max = edit_checkbox_all.length; x < max; x++) {
									edit_checkbox_all[x].click();
								}
							}
						}
						{/literal}
					{/if}

				//key: [ctrl]+[s], edit: to save
					{if $settings.theme.keyboard_shortcut_save_enabled}
						{literal}
						if (((e.which == 115 || e.which == 83) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || (e.which == 19)) {
							e.preventDefault();
							var edit_save_button;
							edit_save_button = document.getElementById('btn_save');
							if (edit_save_button === null || edit_save_button === 'undefined') {
								edit_save_button = document.querySelector('button[name=btn_save]');
							}
							if (edit_save_button !== null) { edit_save_button.click(); }
						}
						{/literal}
					{/if}

				//key: [ctrl]+[c], list,edit: to copy
					{if $settings.theme.keyboard_shortcut_copy_enabled}
						{if $browser_name_short == 'Safari'} //emulate with detecting [c] only, as [command] and [control] keys are ignored when captured
							{literal}
							if ((e.which == 99 || e.which == 67) && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							{/literal}
						{else}
							{literal}
							if ((((e.which == 99 || e.which == 67) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || (e.which == 19)) && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							{/literal}
						{/if}
						{literal}
							var current_selection, copy_button;
							current_selection = window.getSelection();
							if (current_selection === null || current_selection == 'undefined' || current_selection.toString() == '') {
								e.preventDefault();
								copy_button = document.getElementById('btn_copy');
								if (copy_button === null || copy_button === 'undefined') {
									copy_button = document.querySelector('button[name=btn_copy]');
								}
								if (copy_button !== null) { copy_button.click(); }
							}
						}
						{/literal}
					{/if}

		//keydown end
			{literal}
			});
			{/literal}


		//link list rows
			{literal}
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
			{/literal}

		//autosize jquery autosize plugin on applicable input fields
			{literal}
			$('input[type=text].txt.auto-size,input[type=number].txt.auto-size,input[type=password].txt.auto-size,input[type=text].formfld.auto-size,input[type=number].formfld.auto-size,input[type=password].formfld.auto-size').autosizeInput();
			{/literal}

		//initialize bootstrap tempusdominus (calendar/datetime picker) plugin
			{literal}
			$(function() {
				//set defaults
					$.fn.datetimepicker.Constructor.Default = $.extend({}, $.fn.datetimepicker.Constructor.Default, {
						buttons: {
							showToday: true,
							showClear: true,
							showClose: true,
						},
						icons: {
							time: 'fas fa-clock',
							date: 'fas fa-calendar-alt',
							up: 'fas fa-arrow-up',
							down: 'fas fa-arrow-down',
							previous: 'fas fa-chevron-left',
							next: 'fas fa-chevron-right',
							today: 'fas fa-calendar-check',
							clear: 'fas fa-trash',
							close: 'fas fa-times',
						}
					});
				//define formatting of individual classes
					$('.datepicker').datetimepicker({ format: 'YYYY-MM-DD', });
					$('.datetimepicker').datetimepicker({ format: 'YYYY-MM-DD HH:mm', });
					$('.datetimesecpicker').datetimepicker({ format: 'YYYY-MM-DD HH:mm:ss', });
			});
			{/literal}

		//apply bootstrap colorpicker plugin
			{literal}
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
			{/literal}

		//apply bootstrap password strength plugin
			{literal}
			$('#password').pwstrength({
				common: {
					minChar: 8,
					usernameField: '#username',
				},
				//rules: { },
				ui: {
					colorClasses: ['danger', 'warning', 'warning', 'warning', 'success', 'success'], //weak,poor,normal,medium,good,strong
					progressBarMinPercentage: 15,
					showVerdicts: false,
					viewports: {
						progress: '#pwstrength_progress'
					}
				}
			});
			{/literal}

		//crossfade menu brand images (if hover version set)
			{if $settings.theme.menu_brand_image != '' && $settings.theme.menu_brand_image_hover != '' && $settings.theme.menu_style != 'side'}
				{literal}
				$(function(){
					$('#menu_brand_image').on('mouseover',function(){
						$(this).fadeOut('fast', function(){
							$('#menu_brand_image_hover').fadeIn('fast');
						});
					});
					$('#menu_brand_image_hover').on('mouseout',function(){
						$(this).fadeOut('fast', function(){
							$('#menu_brand_image').fadeIn('fast');
						});
					});
				});
				{/literal}
			{/if}

		//generate resizeEnd event after window resize event finishes (used when side menu and on messages app)
			{literal}
			$(window).on('resize', function() {
				if (this.resizeTO) { clearTimeout(this.resizeTO); }
				this.resizeTO = setTimeout(function() { $(this).trigger('resizeEnd'); }, 180);
			});
			{/literal}

		//side menu: adjust content container width after window resize
			{if $settings.theme.menu_style == 'side'}
				{literal}
				$(window).on('resizeEnd', function() {
					$('#content_container').animate({ width: $(window).width() - $('#menu_side_container').width() }, 200);
				});
				{/literal}
			{/if}

	{literal}
	}); //document ready end
	{/literal}


	//audio playback functions
		{literal}
		var recording_audio, audio_clock;

		function recording_play(recording_id) {
			if (document.getElementById('recording_progress_bar_'+recording_id)) {
				document.getElementById('recording_progress_bar_'+recording_id).style.display='';
			}
			recording_audio = document.getElementById('recording_audio_'+recording_id);

			if (recording_audio.paused) {
				recording_audio.volume = 1;
				recording_audio.play();
				document.getElementById('recording_button_'+recording_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_pause}{literal} fa-fw'></span>";
				audio_clock = setInterval(function () { update_progress(recording_id); }, 20);

				$('[id*=recording_button]').not('[id*=recording_button_'+recording_id+']').html("<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>");
				$('[id*=recording_progress_bar]').not('[id*=recording_progress_bar_'+recording_id+']').css('display', 'none');

				$('audio').each(function(){$('#menu_side_container').width()
					if ($(this).get(0) != recording_audio) {
						$(this).get(0).pause(); //stop playing
						$(this).get(0).currentTime = 0; //reset time
					}
				});
			}
			else {
				recording_audio.pause();
				document.getElementById('recording_button_'+recording_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>";
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
			document.getElementById('recording_button_'+recording_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>";
			clearInterval(audio_clock);
		}

		function update_progress(recording_id) {
			recording_audio = document.getElementById('recording_audio_'+recording_id);
			var recording_progress = document.getElementById('recording_progress_'+recording_id);
			var value = 0;
			if (recording_audio.currentTime > 0) {
				value = (100 / recording_audio.duration) * recording_audio.currentTime;
			}
			recording_progress.style.marginLeft = value + '%';
			if (parseInt(recording_audio.duration) > 30) { //seconds
				clearInterval(audio_clock);
			}
		}
		{/literal}

	//handle action bar style on scroll
		{literal}
		window.addEventListener('scroll', function(){
			action_bar_scroll('action_bar', 20);
		}, false);
		function action_bar_scroll(action_bar_id, scroll_position, function_sticky, function_inline) {
			if (document.getElementById(action_bar_id)) {
				//sticky
					if (this.scrollY > scroll_position) {
						document.getElementById(action_bar_id).classList.add('scroll');
						if (typeof function_sticky === 'function') { function_sticky(); }
					}
				//inline
					if (this.scrollY < scroll_position) {
						document.getElementById(action_bar_id).classList.remove('scroll');
						if (typeof function_inline === 'function') { function_inline(); }
					}
			}
		}
		{/literal}

	//enable button class button
		{literal}
		function button_enable(button_id) {
			button = document.getElementById(button_id);
			button.disabled = false;
			button.classList.remove('disabled');
			if (button.parentElement.nodeName == 'A') {
				anchor = button.parentElement;
				anchor.classList.remove('disabled');
				anchor.setAttribute('onclick','');
			}
		}
		{/literal}

	//disable button class button
		{literal}
		function button_disable(button_id) {
			button = document.getElementById(button_id);
			button.disabled = true;
			button.classList.add('disabled');
			if (button.parentElement.nodeName == 'A') {
				anchor = button.parentElement;
				anchor.classList.add('disabled');
				anchor.setAttribute('onclick','return false;');
			}
		}
		{/literal}

	//list page functions
		{literal}
		function list_all_toggle(modifier) {
			var checkboxes = (modifier !== undefined) ? document.getElementsByClassName('checkbox_'+modifier) : document.querySelectorAll("input[type='checkbox']");
			var checkbox_checked = document.getElementById('checkbox_all' + (modifier !== undefined ? '_'+modifier : '')).checked;
			for (var i = 0, max = checkboxes.length; i < max; i++) {
				checkboxes[i].checked = checkbox_checked;
			}
			if (document.getElementById('btn_check_all') && document.getElementById('btn_check_none')) {
				if (checkbox_checked) {
					document.getElementById('btn_check_all').style.display = 'none';
					document.getElementById('btn_check_none').style.display = '';
				}
				else {
					document.getElementById('btn_check_all').style.display = '';
					document.getElementById('btn_check_none').style.display = 'none';
				}
			}
		}

		function list_all_check() {
			var inputs = document.getElementsByTagName('input');
			document.getElementById('checkbox_all').checked;
			for (var i = 0, max = inputs.length; i < max; i++) {
				if (inputs[i].type === 'checkbox') {
					inputs[i].checked = true;
				}
			}
		}

		function list_self_check(checkbox_id) {
			var inputs = document.getElementsByTagName('input');
			for (var i = 0, max = inputs.length; i < max; i++) {
				if (inputs[i].type === 'checkbox') {
					inputs[i].checked = false;
				}
			}
			document.getElementById(checkbox_id).checked = true;
		}

		function list_action_set(action) {
			document.getElementById('action').value = action;
		}

		function list_form_submit(form_id) {
			document.getElementById(form_id).submit();
		}

		function list_search_reset() {
			document.getElementById('btn_reset').style.display = 'none';
			document.getElementById('btn_search').style.display = '';
		}
		{/literal}

	//edit page functions
		{literal}
		function edit_all_toggle(modifier) {
			var checkboxes = document.getElementsByClassName('checkbox_'+modifier);
			var checkbox_checked = document.getElementById('checkbox_all_'+modifier).checked;
			if (checkboxes.length > 0) {
				for (var i = 0; i < checkboxes.length; ++i) {
					checkboxes[i].checked = checkbox_checked;
				}
				if (document.getElementById('btn_delete')) {
					document.getElementById('btn_delete').value = checkbox_checked ? '' : 'delete';
				}
			}
		}

		function edit_delete_action(modifier) {
			var checkboxes = document.getElementsByClassName('chk_delete');
			if (document.getElementById('btn_delete') && checkboxes.length > 0) {
				var checkbox_checked = false;
				for (var i = 0; i < checkboxes.length; ++i) {
					if (checkboxes[i].checked) {
						checkbox_checked = true;
					}
					else {
						if (document.getElementById('checkbox_all'+(modifier !== undefined ? '_'+modifier : ''))) {
							document.getElementById('checkbox_all'+(modifier !== undefined ? '_'+modifier : '')).checked = false;
						}
					}
				}
				document.getElementById('btn_delete').value = checkbox_checked ? '' : 'delete';
			}
		}
		{/literal}

	//misc functions
		{literal}
		function swap_display(a_id, b_id, display_value) {
			display_value = display_value !== undefined ? display_value : 'inline-block';
			a = document.getElementById(a_id);
			b = document.getElementById(b_id);
			if (window.getComputedStyle(a).display === 'none') {
				a.style.display = display_value;
				b.style.display = 'none';
			}
			else {
				a.style.display = 'none';
				b.style.display = display_value;
			}
		}

		function modal_close() {
			document.location.href='#';
			document.activeElement.blur();
		}

		function hide_password_fields() {
			var password_fields = document.querySelectorAll("input[type='password']");
			for (var p = 0, max = password_fields.length; p < max; p++) {
				password_fields[p].style.visibility = 'hidden';
				password_fields[p].type = 'text';
			}
		}

		window.addEventListener('beforeunload', function(e){
			hide_password_fields();
		});
		{/literal}

	</script>

</head>
<body>

	{*//message container *}
		<div id='message_container'></div>

	{*//domain selector *}
		{if $authenticated && $domain_selector_enabled}

			<div id='domains_container'>
				<input type='hidden' id='domains_visible' value='0'>
				<div id='domains_block'>
					<div id='domains_header'>
						<input id='domains_hide' type='button' class='btn' style='float: right' value="{$text.theme_button_close}">
						<a href='{$domains_app_path}'><b style='color: #000;'>{$text.theme_title_domains}</b></a> ({$domain_count})
						<br><br>
						<input type='text' id='domain_filter' class='formfld' style='margin-left: 0; min-width: 100%; width: 100%;' placeholder="{$text.theme_label_search}" onkeyup='domain_search(this.value)'>
					</div>
					<div id='domains_list'>
						{foreach $domains as $row}
							{*//alternate background colors of inactive domains *}
								{if $background_color == $domain_selector_background_color_1}
									{$background_color=$domain_selector_background_color_2}
								{else}
									{$background_color=$domain_selector_background_color_1}
								{/if}
							{*//set active domain color *}
								{if $domain_active_background_color != ''}
									{if $row.domain_uuid == $domain_uuid}{$background_color=$domain_active_background_color}{/if}
								{/if}
							{*//active domain text hover color *}
								{if $settings.theme.domain_active_text_color_hover != '' && $row.domain_uuid == $domain_uuid}
									<div id='{$row.domain_name}' class='domains_list_item_active' style='background-color: {$background_color}' onclick="document.location.href='{$domains_app_path}?domain_uuid={$row.domain_uuid}&domain_change=true';">
								{elseif $settings.theme.domain_inactive_text_color_hover != '' && $row.domain_uuid != $domain_uuid}
									<div id='{$row.domain_name}' class='domains_list_item_inactive' style='background-color: {$background_color}' onclick="document.location.href='{$domains_app_path}?domain_uuid={$row.domain_uuid}&domain_change=true';">
								{else}
									<div id='{$row.domain_name}' class='domains_list_item' style='background-color: {$background_color}' onclick="document.location.href='{$domains_app_path}?domain_uuid={$row.domain_uuid}&domain_change=true';">
								{/if}
							{*//domain link *}
								<a href='{$domains_app_path}?domain_uuid={$row.domain_uuid}&domain_change=true' {if $row.domain_uuid == $domain_uuid}style='font-weight: bold;'{/if}>{$row.domain_name}</a>
							{*//domain description *}
								{if $row.domain_description != ''}
									{*//active domain description text color *}
										{if $settings.theme.domain_active_desc_text_color != '' && $row.domain_uuid == $domain_uuid}
											<span class='domain_active_list_item_description' title="{$row.domain_description}"> - {$row.domain_description}</span>
									{*//inactive domains description text color *}
										{elseif $settings.theme.domain_inactive_desc_text_color != '' && $row.domain_uuid != $domain_uuid}
											<span class='domain_inactive_list_item_description' title="{$row.domain_description}"> - {$row.domain_description}</span>
									{*//default domain description text color *}
										{else}
											<span class='domain_list_item_description' title="{$row.domain_description}"> - {$row.domain_description}</span>
										{/if}
								{/if}
							</div>
							{$ary_domain_names[]=$row.domain_name}
							{$ary_domain_descs[]=$row.domain_description|replace:'"':'\"'}
						{/foreach}
					</div>

					<script>
						{literal}
						var domain_names = new Array("{/literal}{'","'|implode:$ary_domain_names}{literal}");
						var domain_descs = new Array("{/literal}{'","'|implode:$ary_domain_descs}{literal}");
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
						{/literal}
					</script>

				</div>
			</div>

		{/if}

	{*//qr code container for contacts *}
		<div id='qr_code_container' style='display: none;' onclick='$(this).fadeOut(400);'>
			<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'><tr><td align='center' valign='middle'>
				<span id='qr_code' onclick="$('#qr_code_container').fadeOut(400);"></span>
			</td></tr></table>
		</div>

	{*//login page *}
		{if $login_page}
			<div id='default_login'>
				<a href='{$project_path}/'><img id='login_logo' style='width: {$login_logo_width}; height: {$login_logo_height};' src='{$login_logo_source}'></a><br />
				{$document_body}
			</div>
			<div id='footer_login'>
				<span class='footer'>{$settings.theme.footer}</span>
			</div>

	{*//other pages *}
		{else}
			{if $settings.theme.menu_style == 'side' || $settings.theme.menu_style == 'inline' || $settings.theme.menu_style == 'static'}
				{$container_open}
				{if $settings.theme.menu_style == 'inline'}{$logo}{/if}
				{$menu}
				{if $settings.theme.menu_style == 'inline' || $settings.theme.menu_style == 'static'}<br />{/if}
			{else} {*//default: fixed *}
				{$menu}
				{$container_open}
			{/if}
			<div id='main_content'>
				{$document_body}
			</div>
			<div id='footer'>
				<span class='footer'>{$settings.theme.footer}</span>
			</div>
			{$container_close}
		{/if}

</body>
</html>