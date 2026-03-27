
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
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no' />
<meta name="robots" content="noindex, nofollow, noarchive" />

{*//external css files *}
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap-tempusdominus.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/bootstrap/css/bootstrap-colorpicker.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/resources/fontawesome/css/all.min.css.php'>
	<link rel='stylesheet' type='text/css' href='{$project_path}/themes/default/css.php?updated=202603110200'>
{*//link to custom css file *}
	{if !empty($settings.theme.custom_css)}
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
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/fontawesome/js/all.min.js.php' defer></script>

{*//web font loader *}
	{if isset($settings.theme.font_loader) && $settings.theme.font_loader == 'true'}
		{if $settings.theme.font_retrieval != 'asynchronous'}
			<script language='JavaScript' type='text/javascript' src='//ajax.googleapis.com/ajax/libs/webfont/{$settings.theme.font_loader_version}/webfont.js'></script>
		{/if}
		<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/fonts/web_font_loader.php?v={$settings.theme.font_loader_version}'></script>
	{/if}

{*//javascript functions *}
	<script language='JavaScript' type='text/javascript' src='{$project_path}/resources/javascript/select_group_option.js'></script>

{*//local javascript *}
	<script language='JavaScript' type='text/javascript'>

		//message bar display
			{literal}
			function display_message(msg, mood, delay) {
				mood = mood !== undefined ? mood : 'default';
				delay = delay !== undefined ? delay : {/literal}{$settings.theme.message_delay}{literal};
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
				var menu_side_expand_timer;
				var menu_side_contract_timer;
				var menu_side_state_current = '{if $menu_side_state == 'hidden'}expanded{else}{$menu_side_state}{/if}';
				{literal}

				function menu_side_contract_start() {
					menu_side_contract_timer = setTimeout(function() {
						menu_side_contract();
						}, {/literal}{$settings.theme.menu_side_toggle_hover_delay_contract}{literal});
				}

				function menu_side_contract() {
					if (menu_side_state_current == 'expanded') {
						{/literal}
						{if $menu_side_state == 'hidden'}
							{literal}
							$('#menu_side_container').hide();
							{/literal}
						{else}
							{literal}
							$('.menu_side_sub').slideUp(180);
							$('.menu_side_item_title').hide();
							{/literal}
							{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == 'image_text' || $settings.theme.menu_brand_type == ''}
								{literal}
								$('#menu_brand_image_expanded').fadeOut(180, function() {
									$('#menu_brand_image_contracted').fadeIn(180);
								});
								{/literal}
							{else if $settings.theme.menu_brand_type == 'text'}
								{literal}
								$('.menu_brand_text').hide();
								{/literal}
							{/if}
							{literal}
							$('.menu_side_control_state').hide();
							$('.menu_side_item_main_sub_icons').hide();
							$('.sub_arrows').removeClass('{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}').addClass('{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}');
							$('#menu_side_container').animate({ width: '{/literal}{$settings.theme.menu_side_width_contracted}{literal}px' }, 180, function() {
								menu_side_state_current = 'contracted';
							});
							{/literal}
							{if $settings.theme.menu_side_toggle_body_width == 'shrink' || ($settings.theme.menu_side_state == 'expanded' && $settings.theme.menu_side_toggle_body_width == 'fixed')}
								{literal}
								if ($(window).width() >= 576) {
									$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_contracted}{literal} }, 250);
								}
								{/literal}
							{/if}
							{literal}
							$('.menu_side_contract').hide();
							$('.menu_side_expand').show();
							if ($(window).width() < 576) {
								$('#menu_side_container').hide();
							}
							{/literal}
						{/if}
						{literal}
					}
				}

				function menu_side_expand_start() {
					menu_side_expand_timer = setTimeout(function() {
						menu_side_expand();
						}, {/literal}{$settings.theme.menu_side_toggle_hover_delay_expand}{literal});
				}

				function menu_side_expand() {
					{/literal}
					{if $menu_side_state == 'hidden'}
						{literal}
						$('.menu_side_contract').show();
						{/literal}
						{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == 'image_text' || $settings.theme.menu_brand_type == ''}
							{literal}
							$('#menu_brand_image_contracted').hide();
							$('#menu_brand_image_expanded').show();
							{/literal}
						{/if}
						{literal}
						$('.menu_side_control_state').show();
						$('.menu_brand_text').show();
						$('.menu_side_item_main_sub_icons').show();
						$('.menu_side_item_title').show();
						if ($(window).width() < 576) {
							$('#menu_side_container').width($(window).width());
						}
						$('#menu_side_container').show();
						{/literal}
					{else}
						{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == 'image_text' ||$settings.theme.menu_brand_type == ''}
							{literal}
							$('#menu_brand_image_contracted').fadeOut(180);
							{/literal}
						{/if}
						{literal}
						$('.menu_side_expand').hide();
						$('.menu_side_contract').show();
						$('#menu_side_container').show();
						var menu_side_container_width = $(window).width() < 576 ? $(window).width() : '{/literal}{$settings.theme.menu_side_width_expanded}{literal}px';
						$('#menu_side_container').animate({ width: menu_side_container_width }, 180, function() {
							{/literal}
							{if $settings.theme.menu_brand_type == 'image' || $settings.theme.menu_brand_type == 'image_text' || $settings.theme.menu_brand_type == ''}
								{literal}
								$('#menu_brand_image_expanded').fadeIn(180);
								{/literal}
							{/if}
							{literal}
							$('.menu_side_control_state').fadeIn(180);
							$('.menu_brand_text').fadeIn(180);
							$('.menu_side_item_main_sub_icons').fadeIn(180);
							$('.menu_side_item_title').fadeIn(180, function() {
								menu_side_state_current = 'expanded';
							});
						});
						{/literal}
						{if $settings.theme.menu_side_toggle_body_width == 'shrink' || ($settings.theme.menu_side_state == 'expanded' && $settings.theme.menu_side_toggle_body_width == 'fixed')}
							{literal}
							if ($(window).width() >= 576) {
								$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_expanded}{literal} }, 250);
							}
							{/literal}
						{/if}
					{/if}
					{literal}
				}

				function menu_side_item_toggle(item_id) {
					$('#sub_arrow_'+item_id).toggleClass(['{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}','{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}']);
					$('.sub_arrows').not('#sub_arrow_'+item_id).removeClass('{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}').addClass('{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}');
					$('#sub_'+item_id).slideToggle(180, function() {
						{/literal}
						{if $settings.theme.menu_side_item_main_sub_close != 'manual'}
							{literal}
							if (!$(this).is(':hidden')) {
								$('.menu_side_sub').not($(this)).slideUp(180);
							}
							{/literal}
						{/if}
						{literal}
					});
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
				$('.header_domain_selector_domain').on('click', function() { event.preventDefault(); show_domains(); });
				$('#domains_hide').on('click', function() { hide_domains(); });

				function show_domains() {
					$('#body_header_user_menu').fadeOut(200);
					search_domains('domains_list');

					$('#domains_visible').val(1);
					var scrollbar_width = (window.innerWidth - $(window).width()); //gold: only solution that worked with body { overflow:auto } (add -ms-overflow-style: scrollbar; to <body> style for ie 10+)
					if (scrollbar_width > 0) {
						$('body').css({'margin-right':scrollbar_width, 'overflow':'hidden'}); //disable body scroll bars
						$('.navbar').css('margin-right',scrollbar_width); //adjust navbar margin to compensate
						$('#domains_container').css('right',-scrollbar_width); //domain container right position to compensate
					}
					$('#domains_container').show();
					$('#domains_block').animate({marginRight: '+=300'}, 400, function() {
						$('#domains_search').trigger('focus');
					});
				}

				function hide_domains() {
					$('#domains_visible').val(0);
					$(document).ready(function() {
						$('#domains_block').animate({marginRight: '-=300'}, 400, function() {
							$('#domains_search').val('');
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
				var action_bar_actions, first_form, first_submit, modal_input_class, modal_continue_button;
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
							modal_input_class = e.target.className;
							if (e.which == 13 && (e.target.tagName == 'INPUT' || e.target.tagName == 'SELECT')) {
								if (modal_input_class.includes('modal-input')) {
									e.preventDefault();
									modal_continue_button = document.getElementById(e.target.dataset.continue);
									if (modal_continue_button) { modal_continue_button.click(); }
								}
								else {
									if (typeof window.submit_form === 'function') { submit_form(); }
									else { document.getElementById('frm').submit(); }
								}
							}
						});
					}
				}
				{/literal}
			{/if}

		//common (used by delete and toggle)
			{if !empty($settings.theme.keyboard_shortcut_delete_enabled) || !empty($settings.theme.keyboard_shortcut_toggle_enabled)}
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
							var add_button;
							add_button = document.getElementById('btn_add');
							if (add_button === null || add_button === undefined) {
								add_button = document.querySelector('button[name=btn_add]');
							}
							if (add_button !== null) { add_button.click(); }
						}
						{/literal}
					{/if}

				//key: [delete], list: to delete checked, edit: to delete
					{if !empty($settings.theme.keyboard_shortcut_delete_enabled)}
						{literal}
						if (e.which == 46 && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							e.preventDefault();
							var delete_button;
							delete_button = document.querySelector('button[name=btn_delete]');
							if (delete_button === null || delete_button === undefined) {
								delete_button = document.getElementById('btn_delete');
							}
							if (delete_button !== null) { delete_button.click(); }
						}
						{/literal}
					{/if}

				//key: [space], list,edit:prevent default space key behavior when opening toggle confirmation (which would automatically *click* the focused continue button on key-up)
					{if $settings.theme.keyboard_shortcut_toggle_enabled}
						{literal}
						if (e.which == 32 && e.target.id == 'btn_toggle') {
							e.preventDefault();
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
						if (e.which == 32 && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'BUTTON' && !(e.target.tagName == 'INPUT' && e.target.type == 'button') && !(e.target.tagName == 'INPUT' && e.target.type == 'submit') && e.target.tagName != 'TEXTAREA' && list_checkboxes.length !== 0) {
							e.preventDefault();
							var toggle_button;
							toggle_button = document.querySelector('button[name=btn_toggle]');
							if (toggle_button === null || toggle_button === undefined) {
								toggle_button = document.getElementById('btn_toggle');
							}
							if (toggle_button !== null) { toggle_button.click(); }
						}
						{/literal}
					{/if}

				//key: [ctrl]+[a], list,edit: to check all
					{if $settings.theme.keyboard_shortcut_check_all_enabled}
						{literal}
						// if ((((e.which == 97 || e.which == 65) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || e.which == 19) && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
						// 	var all_checkboxes;
						// 	all_checkboxes = document.querySelectorAll('table.list tr.list-header th.checkbox input[name=checkbox_all]');
						// 	if (typeof all_checkboxes != 'object' || all_checkboxes.length == 0) {
						// 		all_checkboxes = document.querySelectorAll('td.edit_delete_checkbox_all > span > input[name=checkbox_all]');
						// 	}
						// 	if (typeof all_checkboxes == 'object' && all_checkboxes.length > 0) {
						// 		e.preventDefault();
						// 		for (var x = 0, max = all_checkboxes.length; x < max; x++) {
						// 			all_checkboxes[x].click();
						// 		}
						// 	}
						// }
						{/literal}

					{/if}

				//key: [ctrl]+[s], edit: to save
					{if $settings.theme.keyboard_shortcut_save_enabled}
						{literal}
						if (((e.which == 115 || e.which == 83) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || (e.which == 19)) {
							e.preventDefault();
							var save_button;
							save_button = document.getElementById('btn_save');
							if (save_button === null || save_button === undefined) {
								save_button = document.querySelector('button[name=btn_save]');
							}
							if (save_button !== null) { save_button.click(); }
						}
						{/literal}
					{/if}

				//key: [ctrl]+[c], list,edit: to copy
					{if $settings.theme.keyboard_shortcut_copy_enabled|default:false}
						{if $browser_name_short == 'Safari'} //emulate with detecting [c] only, as [command] and [control] keys are ignored when captured
							{literal}
							if (
								(e.which == 99 || e.which == 67) &&
								!(e.target.tagName == 'INPUT' && e.target.type == 'text') &&
								!(e.target.tagName == 'INPUT' && e.target.type == 'password') &&
								e.target.tagName != 'TEXTAREA'
								) {
							{/literal}
						{else}
							{literal}
							if (
								(
									(
										(e.which == 99 || e.which == 67) &&
										(e.ctrlKey || e.metaKey) &&
										!e.shiftKey
									) ||
									e.which == 19
								) &&
								!(e.target.tagName == 'INPUT' && e.target.type == 'text') &&
								e.target.tagName != 'TEXTAREA'
								) {
							{/literal}
						{/if}
						{literal}
							var current_selection, copy_button;
							current_selection = window.getSelection();
							if (current_selection === null || current_selection === undefined || current_selection.toString() == '') {
								e.preventDefault();
								copy_button = document.querySelector('button[name=btn_copy]');
								if (copy_button === null || copy_button === undefined) {
									copy_button = document.getElementById('btn_copy');
								}
								if (copy_button !== null) { copy_button.click(); }
							}
						}
						{/literal}
					{/if}

				//key: [left] / [right], audio playback: rewind / fast-forward
					{literal}
					if (
						e.which == 39 &&
						!(e.target.tagName == 'INPUT' && e.target.type == 'text') &&
						e.target.tagName != 'TEXTAREA'
						) {
						recording_fast_forward();
					}
					if (
						e.which == 37 &&
						!(e.target.tagName == 'INPUT' && e.target.type == 'text') &&
						e.target.tagName != 'TEXTAREA'
						) {
						recording_rewind();
					}
					{/literal}

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
							time: 'fa-solid fa-clock',
							date: 'fa-solid fa-calendar-days',
							up: 'fa-solid fa-arrow-up',
							down: 'fa-solid fa-arrow-down',
							previous: 'fa-solid fa-chevron-left',
							next: 'fa-solid fa-chevron-right',
							today: 'fa-solid fa-calendar-check',
							clear: 'fa-solid fa-trash',
							close: 'fa-solid fa-xmark',
						}
					});
				//define formatting of individual classes
					$('.datepicker').datetimepicker({ format: 'YYYY-MM-DD', });
					{/literal}

					{if !empty($time_format) && $time_format == '24h'}
						{literal}
						$(".datetimepicker").datetimepicker({ format: 'YYYY-MM-DD HH:mm', });
						$(".datetimepicker-future").datetimepicker({ format: 'YYYY-MM-DD HH:mm', minDate: new Date(), });
						$(".datetimesecpicker").datetimepicker({ format: 'YYYY-MM-DD HH:mm:ss', });
						{/literal}
					{else}
						{literal}
						$(".datetimepicker").datetimepicker({ format: 'YYYY-MM-DD hh:mm a', });
						$(".datetimepicker-future").datetimepicker({ format: 'YYYY-MM-DD hh:mm a', minDate: new Date(), });
						$(".datetimesecpicker").datetimepicker({ format: 'YYYY-MM-DD hh:mm:ss a', });
						{/literal}
					{/if}

			{literal}
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
			{if !empty($settings.theme.menu_brand_image) && !empty($settings.theme.menu_brand_image_hover) && isset($settings.theme.menu_style) && $settings.theme.menu_style != 'side'}
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
					if ($(window).width() < 576) {
						if (menu_side_state_current == 'contracted') {
							$('#menu_side_container').hide();
						}
						if (menu_side_state_current == 'expanded') {
							{/literal}
							{if $menu_side_state != 'hidden'}
								{literal}
								$('#menu_side_container').show();
								{/literal}
							{/if}
							{literal}
							$('#menu_side_container').animate({ width: $(window).width() }, 180);
						}
						$('#content_container').animate({ width: $(window).width() }, 100);
					}
					else {
						{/literal}
						{if $menu_side_state == 'hidden'}
							{literal}
							$('#menu_side_container').animate({ width: '{/literal}{$settings.theme.menu_side_width_expanded}{literal}px' }, 180);
							$('#content_container').animate({ width: $(window).width() }, 100);
							{/literal}
						{else}
							{literal}
							$('#menu_side_container').show();
							if (menu_side_state_current == 'expanded') {
								$('#menu_side_container').animate({ width: '{/literal}{$settings.theme.menu_side_width_expanded}{literal}px' }, 180, function() {
									$('#content_container').animate({ width: $(window).width() - $('#menu_side_container').width() }, 100);
								});
							}
							else {
								$('#content_container').animate({ width: $(window).width() - $('#menu_side_container').width() }, 100);
							}
							{/literal}
						{/if}
						{literal}
					}
				});
				{/literal}
			{/if}

		//hide an open user menu in the body header or menu on scroll
			{literal}
			$(window).on('scroll', function() {
				$('#body_header_user_menu').fadeOut(200);
			});
			$('div#main_content').on('click', function() {
				$('#body_header_user_menu').fadeOut(200);
			});
			{/literal}

		//create function to mimic toggling fade and slide at the same time
			{literal}
			(function($){
				$.fn.toggleFadeSlide = function(speed = 200, easing, callback){
					return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
				};
			})(jQuery);
			{/literal}

		//slide toggle
			{literal}
			var switches = document.getElementsByClassName('switch');
			var toggle = function(){
				this.children[0].value = (this.children[0].value == 'false' ? 'true' : 'false');
				this.children[0].dispatchEvent(new Event('change'));
				};
			for (var i = 0; i < switches.length; i++) {
				switches[i].addEventListener('click', toggle, false);
			}
			{/literal}

		//domain select: searchable picker
			{literal}
			(function() {
				var domain_cache = null;
				var domain_cache_loading = false;
				var domain_cache_callbacks = [];

				function fetch_domains(callback) {
					if (domain_cache !== null) {
						callback(domain_cache);
						return;
					}
					domain_cache_callbacks.push(callback);
					if (domain_cache_loading) { return; }
					domain_cache_loading = true;
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState === 4) {
							var results = [];
							if (this.status === 200) {
								try { results = JSON.parse(this.responseText) || []; }
								catch (error) { results = []; }
							}
							domain_cache = results;
							domain_cache_loading = false;
							while (domain_cache_callbacks.length) {
								domain_cache_callbacks.shift()(domain_cache);
							}
						}
					};
					xhttp.open("GET", "/core/domains/domain_json.php?{/literal}{$domain_json_token_name}={$domain_json_token_hash}{literal}", true);
					xhttp.send();
				}

				function init_domain_search_select(select) {
					if (!select || select.dataset.domainSearchInit === 'true') { return; }
					select.dataset.domainSearchInit = 'true';

					var option_values = {};
					var option_order = [];
					var global_option = null;
					for (var o = 0; o < select.options.length; o++) {
						var option = select.options[o];
						var option_text = (option.text || option.innerText || '').trim();
						if (option.value === '') {
							if (!option.disabled && option_text !== '') {
								global_option = { domain_uuid: '', domain_name: option_text };
							}
							continue;
						}
						option_values[option.value] = option_text;
						option_order.push(option.value);
					}
					if (option_order.length === 0) { return; }

					var picker = document.createElement('div');
					picker.className = 'domain-search-picker';

					var input = document.createElement('input');
					input.type = 'text';
					input.className = 'formfld domain-search-input';
					input.placeholder = '{/literal}{$text.label_search_domains}{literal}...';

					var results = document.createElement('div');
					results.className = 'domain-search-results';
					results.setAttribute('role', 'listbox');
					picker.appendChild(input);
					select.parentNode.insertBefore(picker, select.nextSibling);
					document.body.appendChild(results);

					select.style.position = 'absolute';
					select.style.left = '-10000px';
					select.style.width = '1px';
					select.style.height = '1px';
					select.style.opacity = '0';
					select.style.pointerEvents = 'none';
					select.setAttribute('tabindex', '-1');
					if (select.id) {
						picker.id = select.id + '_domain_search_picker';
					}

					var dataset = [];
					var active_index = -1;
					var current_items = [];

					function get_selected_label() {
						if (select.selectedIndex > -1) {
							var selected_option = select.options[select.selectedIndex];
							if (selected_option && selected_option.value !== '') {
								return selected_option.text || selected_option.innerText || '';
							}
						}
						return '';
					}

					function sync_input_to_select() {
						input.value = get_selected_label();
					}

					// Results panel is on document.body; match .formfld / theme input typography
					function sync_results_typography_from_input() {
						var cs = window.getComputedStyle(input);
						results.style.fontFamily = cs.fontFamily;
						results.style.fontSize = cs.fontSize;
						results.style.fontWeight = cs.fontWeight;
						results.style.fontStyle = cs.fontStyle;
						results.style.lineHeight = cs.lineHeight;
						results.style.letterSpacing = cs.letterSpacing;
						results.style.color = cs.color;
					}

					function position_results_panel() {
						if (results.style.display === 'none' || results.style.display === '') { return; }
						var rect = input.getBoundingClientRect();
						var vw = window.innerWidth || document.documentElement.clientWidth;
						var vh = window.innerHeight || document.documentElement.clientHeight;
						var panel_width = Math.min(Math.max(rect.width, 160), vw - 16);
						var left = rect.left;
						if (left + panel_width > vw - 8) { left = Math.max(8, vw - panel_width - 8); }
						else if (left < 8) { left = 8; }
						results.style.left = left + 'px';
						var max_h = Math.min(260, Math.max(120, vh - 16));
						results.style.maxHeight = max_h + 'px';
						results.style.width = panel_width + 'px';
						var top = rect.bottom + 2;
						var h = results.offsetHeight || 1;
						if (top + h > vh - 8 && rect.top > h + 8) {
							top = rect.top - h - 2;
						}
						if (top < 8) { top = 8; }
						results.style.top = top + 'px';
					}

					function close_results() {
						active_index = -1;
						results.style.display = 'none';
						results.innerHTML = '';
					}

					function open_results() {
						results.style.display = 'block';
					}

					function score_item(item, query) {
						if (!query) { return 1; }
						var score = 0;
						var name = item.domain_name_lc;
						var description = item.domain_description_lc;
						if (name === query) { score += 1000; }
						if (name.indexOf(query) === 0) { score += 500; }
						else if (name.indexOf(query) > -1) { score += 300; }
						if (description && description.indexOf(query) > -1) { score += 100; }
						return score;
					}

					function render_results() {
						sync_results_typography_from_input();
						var query = (input.value || '').toLowerCase().trim();
						var matches = [];
						if (global_option !== null) {
							if (!query || global_option.domain_name.toLowerCase().indexOf(query) > -1) {
								matches.push({
									domain_uuid: '',
									domain_name: global_option.domain_name,
									domain_name_lc: global_option.domain_name.toLowerCase(),
									domain_description: '',
									domain_description_lc: '',
									score: query ? 800 : 2
								});
							}
						}
						for (var i = 0; i < dataset.length; i++) {
							var score = score_item(dataset[i], query);
							if (score > 0) {
								dataset[i].score = score;
								matches.push(dataset[i]);
							}
						}

						matches.sort(function(a, b) {
							if (b.score !== a.score) { return b.score - a.score; }
							return a.domain_name_lc.localeCompare(b.domain_name_lc);
						});

						current_items = matches.slice(0, 50);
						results.innerHTML = '';
						active_index = current_items.length > 0 ? 0 : -1;

						if (current_items.length === 0) {
							var empty = document.createElement('div');
							empty.className = 'domain-search-empty';
							empty.innerText = '{/literal}{$text.label_no_matching_domains}{literal}...';
							results.appendChild(empty);
							open_results();
							window.requestAnimationFrame(position_results_panel);
							return;
						}

						for (var r = 0; r < current_items.length; r++) {
							var row = document.createElement('div');
							row.className = 'domain-search-result-item' + (r === active_index ? ' active' : '');
							row.setAttribute('data-index', r);
							var name = document.createElement('span');
							name.className = 'domain-search-result-name';
							name.innerText = current_items[r].domain_name;
							row.appendChild(name);
							if (current_items[r].domain_description) {
								var desc = document.createElement('span');
								desc.className = 'domain-search-result-description';
								desc.innerText = current_items[r].domain_description;
								row.appendChild(desc);
							}
							results.appendChild(row);
						}
						open_results();
						window.requestAnimationFrame(position_results_panel);
					}

					function update_active_row() {
						var rows = results.querySelectorAll('.domain-search-result-item');
						for (var i = 0; i < rows.length; i++) {
							rows[i].classList.toggle('active', i === active_index);
						}
					}

					function select_value(domain_uuid) {
						select.value = domain_uuid;
						select.dispatchEvent(new Event('change', { bubbles: true }));
						sync_input_to_select();
						close_results();
					}

					results.addEventListener('mousedown', function(event) {
						var row = event.target.closest('.domain-search-result-item');
						if (!row) { return; }
						event.preventDefault();
						var index = parseInt(row.getAttribute('data-index'), 10);
						if (!isNaN(index) && current_items[index]) {
							select_value(current_items[index].domain_uuid);
						}
					});

					input.addEventListener('focus', function() {
						render_results();
					});

					input.addEventListener('input', function() {
						render_results();
					});

					input.addEventListener('keydown', function(event) {
						if (results.style.display !== 'block') {
							if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
								render_results();
								event.preventDefault();
							}
							return;
						}
						if (event.key === 'ArrowDown') {
							if (active_index < current_items.length - 1) { active_index++; }
							update_active_row();
							event.preventDefault();
						}
						else if (event.key === 'ArrowUp') {
							if (active_index > 0) { active_index--; }
							update_active_row();
							event.preventDefault();
						}
						else if (event.key === 'Enter') {
							if (active_index > -1 && current_items[active_index]) {
								select_value(current_items[active_index].domain_uuid);
								event.preventDefault();
							}
						}
						else if (event.key === 'Escape') {
							close_results();
						}
					});

					select.addEventListener('change', function() {
						sync_input_to_select();
					});

					function sync_picker_visibility() {
						var hide = window.getComputedStyle(select).display === 'none';
						picker.style.display = hide ? 'none' : 'inline-block';
						if (hide) { close_results(); }
					}
					sync_picker_visibility();
					select._domainSearchSyncVisibility = sync_picker_visibility;

					function on_reposition_results() {
						if (results.style.display === 'block') { position_results_panel(); }
					}
					window.addEventListener('scroll', on_reposition_results, true);
					window.addEventListener('resize', on_reposition_results);

					document.addEventListener('mousedown', function(event) {
						if (!picker.contains(event.target) && !results.contains(event.target)) {
							close_results();
						}
					});

					fetch_domains(function(domains) {
						var domains_by_uuid = {};
						for (var i = 0; i < domains.length; i++) {
							domains_by_uuid[domains[i].domain_uuid] = domains[i];
						}
						var longest_label = global_option !== null ? global_option.domain_name.length : 0;
						for (var x = 0; x < option_order.length; x++) {
							var domain_uuid = option_order[x];
							var domain = domains_by_uuid[domain_uuid] || {};
							var domain_name = (domain.domain_name || option_values[domain_uuid] || '').trim();
							if (!domain_name) { continue; }
							if (domain_name.length > longest_label) { longest_label = domain_name.length; }
							var domain_description = (domain.domain_description || '').trim();
							dataset.push({
								domain_uuid: domain_uuid,
								domain_name: domain_name,
								domain_name_lc: domain_name.toLowerCase(),
								domain_description: domain_description,
								domain_description_lc: domain_description.toLowerCase(),
								score: 0
							});
						}
						var width_ch = Math.max(18, Math.min(longest_label + 2, 80));
						input.style.width = width_ch + 'ch';
						sync_input_to_select();
						sync_results_typography_from_input();
					});
				}

				window.sync_domain_search_select_visibility = function(select_id) {
					var select = document.getElementById(select_id);
					if (select && typeof select._domainSearchSyncVisibility === 'function') {
						select._domainSearchSyncVisibility();
					}
				};

				window.init_domain_search_selects = function() {
					var selectors = document.querySelectorAll("select[name='domain_uuid'], select[id='domain_uuid'], select[data-domain-search='true']");
					for (var i = 0; i < selectors.length; i++) {
						if (selectors[i].dataset.domainSearch === 'false') { continue; }
						if (selectors[i].multiple) { continue; }
						init_domain_search_select(selectors[i]);
					}
				};
			})();

			window.init_domain_search_selects();
			{/literal}

		// Multi select box with search
			{literal}
			const container = document.querySelector('.multiselect_container');

			if (container) {
				const trigger_btn = container.querySelector('.selected_values');
				const dropdown_list = container.querySelector('.dropdown_list');
				const search_input = container.querySelector('.search_box');
				const options_list = container.querySelector('.options_list');
				const input_name = options_list.getAttribute('name');
				const no_results = container.querySelector('#no_results');
				const placeholder = container.querySelector('.placeholder_text');
				let is_open = false;

				// Toggle dropdown open/close
				trigger_btn.addEventListener('click', (event) => {
					event.stopPropagation();
					is_open = !is_open;
					if (is_open) {
						dropdown_list.classList.add('open');
						search_input.focus();
					}
					else {
						dropdown_list.classList.remove('open');
					}
				});

				// Close dropdown if clicked outside
				document.addEventListener('click', (event) => {
					if (!container.contains(event.target)) {
						is_open = false;
						dropdown_list.classList.remove('open');
					}
				});

				// Prevent dropdown from closing when clicking inside the dropdown
				dropdown_list.addEventListener('click', (event) => {
					event.stopPropagation();
				});

				// Handle Search Filtering
				search_input.addEventListener('input', (event) => {
					const search_term = event.target.value.toLowerCase();
					const option_items = document.querySelectorAll('.option_item');
					let visible_count = 0;

					option_items.forEach(item => {
						const text = item.innerText.toLowerCase();

						if (text.includes(search_term)) {
							item.style.display = 'block';
							visible_count++;
						}
						else {
							item.style.display = 'none';
						}
					});

					if (visible_count === 0) {
						no_results.style.display = 'block';
					}
					else {
						no_results.style.display = 'none';
					}
				});

				// Handle checkbox selection
				container.addEventListener('change', (event) => {
					if (event.target.type === 'checkbox') {
						// If unchecked, remove the corresponding hidden input
						if (!event.target.checked) {
							const value = event.target.value;
							const hidden_input = document.querySelector(`input[name="${input_name}"][value="${value}"]`);
							if (hidden_input) {
								hidden_input.remove();
							}
						}

						// Update visual tags and handle checked boxes
						update_selected_values();
					}
				});

				// Handle clicking the text part of the option
				container.addEventListener('click', (event) => {
					if (event.target.classList.contains('option_item')) {
						const checkbox = event.target.querySelector('input[type="checkbox"]');
						if (checkbox) {
							checkbox.checked = !checkbox.checked;
							update_selected_values();
						}
					}
				});

				// Update display logic (tags & hidden input)
				function update_selected_values() {
					const checked_boxes = document.querySelectorAll('.option_item input:checked');
					const selected_count = checked_boxes.length;

					// Update visual tags
					if (selected_count === 0) {
						placeholder.style.display = 'block';
						trigger_btn.innerHTML = `<span class="placeholder_text">{/literal}{$text.label_select}{literal}...</span>`;
						trigger_btn.innerHTML += `<i class='fa-solid fa-angle-down' style='position: absolute; right: 6px; transform: scale(0.70, 0.75);'></i>`;
					}
					else {
						placeholder.style.display = 'none';
						let html = '';

						checked_boxes.forEach(box => {
							const label = box.parentElement.innerText;

							// Create a hidden input for each selected tag
							create_hidden_input_for_tag(label, box.value);

							html += `<span class="tag" data-value="${box.value}">`;
							html += `	${label}`;
							html += `	<span onclick="remove_option('${box.value}')">&times;</span>`;
							html += `</span>`;
						});

						trigger_btn.innerHTML = html;
					}
				}

				// Helper function to remove a tag when clicked (External to scope)
				window.remove_option = function(value) {
					const checkbox = document.querySelector(`input[value="${value}"]`);
					if (checkbox) {
						checkbox.checked = false;

						// Remove the hidden input corresponding to this tag
						const hidden_input = document.querySelector(`input[name="${input_name}"][value="${value}"]`);
						if (hidden_input) {
							hidden_input.remove();
						}

						update_selected_values();
					}
				};

				// Function to create a hidden input for each selected tag
				function create_hidden_input_for_tag(label, value) {
					const existing_hidden_input = document.querySelector(`input[name="${input_name}"][value="${value}"]`);
					if (!existing_hidden_input) {
						const hidden_input = document.createElement('input');
						hidden_input.type = 'hidden';
						hidden_input.name = input_name;
						hidden_input.value = value;
						container.appendChild(hidden_input);
					}
				}

				// Initialize state
				update_selected_values();
			}
			{/literal}

	{literal}
	}); //document ready end
	{/literal}


	//audio playback functions
		{literal}
		var recording_audio, audio_clock, recording_id_playing, label_play;

		function recording_load(player_id, data, audio_type) {
			{/literal}
			//create and load waveform image
			{if $settings.theme.audio_player_waveform_enabled == 'true'}
				{literal}
				//list playback
				if (document.getElementById('playback_progress_bar_background_' + player_id)) {
					// alert("waveform.php?id=" + player_id + (data !== undefined ? '&data=' + data : '') + (audio_type !== undefined ? '&type=' + audio_type : ''));
					document.getElementById('playback_progress_bar_background_' + player_id).style.backgroundImage = "linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 20%), url('waveform.php?id=" + player_id + (data !== undefined ? '&data=' + data : '') + (audio_type !== undefined ? '&type=' + audio_type : '') + "')";
				}
				//form playback
				else if (document.getElementById('recording_progress_bar_' + player_id)) {
					// alert("waveform.php?id=" + player_id + (data !== undefined ? '&data=' + data : '') + (audio_type !== undefined ? '&type=' + audio_type : ''));
					document.getElementById('recording_progress_bar_' + player_id).style.backgroundImage = "linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 20%), url('waveform.php?id=" + player_id + (data !== undefined ? '&data=' + data : '') + (audio_type !== undefined ? '&type=' + audio_type : '') + "')";
				}
				{/literal}
			{else}
				{literal}
				//list playback
				if (document.getElementById('playback_progress_bar_background_' + player_id)) {
					document.getElementById('playback_progress_bar_background_' + player_id).style.backgroundImage = "linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 100%)";
				}
				//form playback
				else if (document.getElementById('recording_progress_bar_' + player_id)) {
					document.getElementById('recording_progress_bar_' + player_id).style.backgroundImage = "linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 100%)";
				}
				{/literal}
			{/if}
			{literal}
		}

		function recording_play(player_id, data, audio_type, label) {
			if (document.getElementById('recording_progress_bar_' + player_id)) {
				document.getElementById('recording_progress_bar_' + player_id).style.display='';
			}
			recording_audio = document.getElementById('recording_audio_' + player_id);

			if (label !== undefined) {
				label_play = "<span class='button-label pad'>" + label + "</span>";
				var label_pause = "<span class='button-label pad'>" + label + "</span>";
			}
			else {
				label_play = "{/literal}{if $php_self == 'xml_cdr_details.php'}{literal}<span class='button-label pad'>{/literal}{$text.label_play}{literal}</span>{/literal}{/if}{literal}";
				var label_pause = "{/literal}{if $php_self == 'xml_cdr_details.php'}{literal}<span class='button-label pad'>{/literal}{$text.label_pause}{literal}</span>{/literal}{/if}{literal}";
			}

			if (recording_audio.paused) {
				recording_load(player_id, data, audio_type);
				recording_audio.volume = 1;
				recording_audio.play();
				recording_id_playing = player_id;
				document.getElementById('recording_button_' + player_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_pause}{literal} fa-fw'></span>" + (label_pause ?? '');
				audio_clock = setInterval(function () { update_progress(player_id); }, 20);

				$('[id*=recording_button]').not('[id*=recording_button_' + player_id + ']').html("<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>" + (label_play ?? ''));
				$('[id*=recording_button_intro]').not('[id*=recording_button_' + player_id + ']').html("<span class='{/literal}{$settings.theme.button_icon_comment}{literal} fa-fw'></span>");
				$('[id*=recording_progress_bar]').not('[id*=recording_progress_bar_' + player_id + ']').css('display', 'none');

				$('audio').each(function(){
					if ($(this).get(0) != recording_audio) {
						$(this).get(0).pause(); //stop playing
						$(this).get(0).currentTime = 0; //reset time
					}
				});
			}
			else {
				recording_audio.pause();
				recording_id_playing = '';
				if (player_id.substring(0,6) == 'intro_') {
					document.getElementById('recording_button_' + player_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_comment}{literal} fa-fw'></span>";
				}
				else {
					document.getElementById('recording_button_' + player_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>" + (label_play ?? '');
				}
				clearInterval(audio_clock);
			}
		}

		function recording_stop(player_id) {
			recording_reset(player_id);
			clearInterval(audio_clock);
		}

		function recording_reset(player_id) {
			recording_audio = document.getElementById('recording_audio_' + player_id);
			recording_audio.pause();
			recording_audio.currentTime = 0;
			{/literal}
			{if $php_self <> 'xml_cdr_details.php'}
				{literal}
				if (document.getElementById('recording_progress_bar_' + player_id)) {
					document.getElementById('recording_progress_bar_' + player_id).style.display='none';
				}
				{/literal}
			{/if}
			{literal}
			if (player_id.substring(0,6) == 'intro_') {
				document.getElementById('recording_button_' + player_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_comment}{literal} fa-fw'></span>";
			}
			else {
				document.getElementById('recording_button_' + player_id).innerHTML = "<span class='{/literal}{$settings.theme.button_icon_play}{literal} fa-fw'></span>" + (label_play ?? '');
			}
			clearInterval(audio_clock);
		}

		function update_progress(player_id) {
			recording_audio = document.getElementById('recording_audio_' + player_id);
			var recording_progress = document.getElementById('recording_progress_' + player_id);
			var value = 0;
			if (recording_audio != null && recording_audio.currentTime > 0) {
				value = Number(((100 / recording_audio.duration) * recording_audio.currentTime).toFixed(1));
			}
			if (recording_progress) {
				recording_progress.style.marginLeft = value + '%';
			}
			// if (recording_audio != null && parseInt(recording_audio.duration) > 30) { //seconds
			// 	clearInterval(audio_clock);
			// }
		}

		function recording_fast_forward() {
			if (recording_audio) {
				recording_audio.currentTime += {/literal}{if !empty($settings.theme.audio_player_scrub_seconds) }{$settings.theme.audio_player_scrub_seconds}{else}2{/if}{literal};
				update_progress(recording_id_playing);
			}
		}

		function recording_rewind() {
			if (recording_audio) {
				recording_audio.currentTime -= {/literal}{if !empty($settings.theme.audio_player_scrub_seconds) }{$settings.theme.audio_player_scrub_seconds}{else}2{/if}{literal};
				update_progress(recording_id_playing);
			}
		}

		function recording_seek(event, player_id) {
			if (recording_audio) {
				if (document.getElementById('playback_progress_bar_background_' + player_id)) {
					audio_player = document.getElementById('playback_progress_bar_background_' + player_id);
				}
				else if (document.getElementById('recording_progress_bar_' + player_id)) {
					audio_player = document.getElementById('recording_progress_bar_' + player_id);
				}
				recording_audio.currentTime = (event.offsetX / audio_player.offsetWidth) * recording_audio.duration;
				update_progress(recording_id_playing);
				document.getElementById('recording_button_' + player_id).focus();
			}
		}

		{/literal}

	//handle action bar style on scroll
		{literal}
		window.addEventListener('scroll', function(){
			action_bar_scroll('action_bar', {/literal}{if $settings.theme.menu_style == 'side'}60{else}20{/if}{literal});
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

	//checkbox on change
		{literal}
		function checkbox_on_change(checkbox) {
			checked = false;
			var inputs = document.getElementsByTagName('input');
			for (var i = 0, max = inputs.length; i < max; i++) {
				if (inputs[i].type === 'checkbox' && inputs[i].checked == true) {
					checked = true;
					break;
				}
			}
			btn_copy = document.getElementById("btn_copy");
			btn_toggle = document.getElementById("btn_toggle");
			btn_delete = document.getElementById("btn_delete");
			btn_download = document.getElementById("btn_download");
			btn_transcribe = document.getElementById("btn_transcribe");
			any_revealed = document.getElementsByClassName('revealed');
			if (checked == true) {
				if (btn_copy) { btn_copy.style.display = "inline"; }
				if (btn_toggle) { btn_toggle.style.display = "inline"; }
				if (btn_delete) { btn_delete.style.display = "inline"; }
				if (btn_download) { btn_download.style.display = "inline"; }
				if (btn_transcribe) { btn_transcribe.style.display = "inline"; }
				if (any_revealed) { [...any_revealed].map(btn => btn.style.display = "inline"); }
			}
		 	else {
				if (btn_copy) { btn_copy.style.display = "none"; }
				if (btn_toggle) { btn_toggle.style.display = "none"; }
				if (btn_delete) { btn_delete.style.display = "none"; }
				if (btn_download) { btn_download.style.display = "none"; }
				if (btn_transcribe) { btn_transcribe.style.display = "none"; }
				if (any_revealed) { [...any_revealed].map(btn => btn.style.display = "none"); }
		 	}
		}
		{/literal}

	//list page functions
		{literal}
		function list_all_toggle(modifier) {
			var checkboxes = (modifier !== undefined) ? document.getElementsByClassName('checkbox_'+modifier) : document.querySelectorAll("input[type='checkbox']:not([id*='_enabled'])");
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
			any_revealed = document.getElementsByClassName('revealed');
			if (checkbox_checked == true) {
				if (any_revealed) { [...any_revealed].map(btn => btn.style.display = "inline"); }
			}
		 	else {
				if (any_revealed) { [...any_revealed].map(btn => btn.style.display = "none"); }
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
			//unchecks each selected checkbox
			document.querySelectorAll('input[type="checkbox"]:not([name*="enabled"])').forEach(checkbox => {
				checkbox.checked = false;
			});

			//select the checkbox with the specified id
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

	//modal functions
		{literal}
		function modal_open(modal_id, focus_id) {
			var modal = document.getElementById(modal_id);
			modal.style.opacity = '1';
			modal.style.pointerEvents = 'auto';
			if (focus_id !== undefined) {
				document.getElementById(focus_id).focus();
			}
		}

		function modal_close() {
			var modals = document.getElementsByClassName('modal-window');
			if (modals.length > 0) {
				for (var m = 0; m < modals.length; ++m) {
					modals[m].style.opacity = '0';
					modals[m].style.pointerEvents = 'none';
				}
			}
			document.activeElement.blur();
		}

		function modal_display_selected(modal_id) {
			const selected_items = [];
			const modal_message_element = document.querySelector(`#${modal_id} .modal-message`);

			if (!modal_message_element.hasAttribute('data-message')) {
				modal_message_element.setAttribute('data-message', modal_message_element.innerHTML);
				modal_message_element.style.cssText += 'max-height: 50vh; overflow: scroll;';
			}
			const message = modal_message_element.getAttribute('data-message');

			document.querySelectorAll('input[type="checkbox"]:checked:not(#checkbox_all)').forEach(checkbox => {
				selected_items.push({
					name: checkbox.dataset.itemName,
					domain: checkbox.dataset.itemDomain
				});
			});

			if (selected_items.length > 0) {
				content = message;
				content += '<table style="margin: 20px 40px; min-width: 70%;">';
				content += '	<tbody>';
				selected_items.forEach(item => {
					content += '	<tr>';
					content += `		<td style="display: list-item;">${item.name}</td>`;
					content += `		<td>${item.domain || ''}</td>`;
					content += '	</tr>';
				});
				content += '	</tbody>';
				content += '</table>';

				modal_message_element.innerHTML = content;
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

	{*//session timer *}
		{if !empty($session_timer)}
			{$session_timer}
		{/if}

	{*//domain selector *}
	function search_domains(element_id) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			//if (this.readyState == 4 && this.status == 200) {
			//	document.getElementById(element_id).innerHTML = this.responseText;
			//}

			//remove current options
			document.getElementById(element_id).innerHTML = '';

			if (this.readyState == 4 && this.status == 200) {

				//create the json object from the response
				obj = JSON.parse(this.responseText);

				//update the domain count
				document.getElementById('domain_count').innerText = obj.length;

				//add new options from the json results
				for (var i=0; i < obj.length; i++) {

					//get the variables
					domain_uuid = obj[i].domain_uuid;
					domain_name = obj[i].domain_name;
					if (obj[i].domain_description != null) {
					//	domain_description = DOMPurify.sanitize(obj[i].domain_description);
					}

					//create a div element
					var div = document.createElement('div');

					//add a div title
					div.title = obj[i].domain_name;

					//add a css class
					div.classList.add("domains_list_item");

					//alternate the background color
					if(i%2==0) {
						div.style.background = '{$domain_selector_background_color_1}';
					}
					else {
						div.style.background = '{$domain_selector_background_color_2}';
					}

					//set the active domain style
					if ('{$domain_uuid}' == obj[i].domain_uuid) {
						div.style.background = '{$domain_active_background_color}';
						div.style.fontWeight = 'bold';
						//div.classList.add("domains_list_item_active");
						//var item_description_class = 'domain_active_list_item_description';
					}
					else {
						//div.classList.add("domains_list_item_inactive");
						//var item_description_class = 'domain_inactive_list_item_description';
					}

					//set link on domain div in list
					div.setAttribute('onclick',"window.location.href='{$domains_app_path}?domain_uuid=" + obj[i].domain_uuid + "&domain_change=true';");

					//define domain link text and description (if any)
					link_label = obj[i].domain_name;
					if (obj[i].domain_description != null) {
						link_label += " <span class='domain_list_item_description' title=\"" + obj[i].domain_description + "\">" + obj[i].domain_description + "</span>";
					}
					var a_tag = document.createElement('a');
					a_tag.setAttribute('href','manage:'+obj[i].domain_name);
					a_tag.setAttribute('onclick','event.preventDefault();');
					a_tag.innerHTML = link_label;
					div.appendChild(a_tag);

					document.getElementById(element_id).appendChild(div);
				}
			}
		};
		search = document.getElementById('domains_search');
		if (search.value) {
			//xhttp.open("GET", "/core/domains/domain_list.php?search="+search.value, true);
			xhttp.open("GET", "/core/domains/domain_json.php?search="+search.value+"&{$domain_json_token_name}={$domain_json_token_hash}", true);
		}
		else {
			//xhttp.open("GET", "/core/domains/domain_list.php", true);
			xhttp.open("GET", "/core/domains/domain_json.php?{$domain_json_token_name}={$domain_json_token_hash}", true);
		}
		xhttp.send();
	}
	{*//domain selector *}
	</script>

</head>
<body>

	{*//video background *}
	{if !empty($background_video)}
		<video id="background-video" autoplay muted poster="" disablePictureInPicture="true" onloadstart="this.playbackRate = 1; this.pause();">
			<source src="{$background_video}" type="video/mp4">
		</video>
	{/if}

	{*//image background *}
	<div id='background-image'></div>

	{*//color background *}
	<div id='background-color'></div>

	{*//message container *}
	<div id='message_container'></div>

	{*//domain selector *}
	{if $authenticated && $domain_selector_enabled}

		<div id='domains_container'>
			<input type='hidden' id='domains_visible' value='0'>
			<div id='domains_block'>
				<div id='domains_header'>
					<input id='domains_hide' type='button' class='btn' style='float: right' value="{$text.theme_button_close}">
					<a id='domains_title' href='{$domains_app_path}'>{$text.theme_title_domains}<div class='count' id='domain_count' style='font-size: 80%;'></div></a>
					<br><br>
					<input type='text' id='domains_search' class='formfld' style='margin-left: 0; min-width: 100%; width: 100%;' placeholder="{$text.theme_label_search}" onkeyup="search_domains('domains_list');">
				</div>
				<div id='domains_list'></div>
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
	{if !empty($login_page)}
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
			{if $settings.theme.menu_style == 'side'}<input type='hidden' id='menu_side_state_current' value='{if $menu_side_state == 'hidden'}expanded{else}{$menu_side_state}{/if}'>{/if}
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
