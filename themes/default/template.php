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
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no' />

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
							$('.sub_arrows').removeClass('fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}').addClass('fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}');
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
					$('#sub_arrow_'+item_id).toggleClass(['fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}','fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}']);
					$('.sub_arrows').not('#sub_arrow_'+item_id).removeClass('fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_contract}{literal}').addClass('fa-{/literal}{$settings.theme.menu_side_item_main_sub_icon_expand}{literal}');
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

				function menu_side_state_set(state) {
					var user_setting_set_path = '{/literal}{$project_path}{literal}/core/user_settings/user_setting_set.php?category=theme&subcategory=menu_side_state&name=text&value='+state;
					var xhr = new XMLHttpRequest();
					xhr.open('GET', user_setting_set_path);
					xhr.send(null);
					xhr.onreadystatechange = function () {
						var setting_modified;
						if (xhr.readyState === 4) {
							if (xhr.status === 200) {
								setting_modified = xhr.responseText;
								if (setting_modified == 'true') {
									document.getElementById('menu_side_state_set_expanded').style.display = state == 'expanded' ? 'none' : 'block';
									document.getElementById('menu_side_state_set_contracted').style.display = state == 'contracted' ? 'none' : 'block';
									{/literal}
									{if $menu_side_state == 'hidden'}
										{literal}
										document.getElementById('menu_side_state_hidden_button').style.display='none';
										{/literal}
									{/if}
									{literal}
									if (state == 'expanded') {
										if ($(window).width() >= 576) {
											$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_expanded}{literal} }, 250);
										}
										else {
											$('#menu_side_container').animate({ width: $(window).width() }, 180);
										}
										document.getElementById('menu_side_state_current').value = 'expanded';
										display_message("{/literal}{$text.theme_message_menu_expanded}{literal}", 'positive', 1000);
									}
									else {
										menu_side_contract();
										if ($(window).width() >= 576) {
											$('#content_container').animate({ width: $(window).width() - {/literal}{$settings.theme.menu_side_width_contracted}{literal} }, 250);
										}
										menu_side_state_current = 'contracted';
										document.getElementById('menu_side_state_current').value = 'contracted';
										display_message("{/literal}{$text.theme_message_menu_contracted}{literal}", 'positive', 1000);
									}
								}
								else if (setting_modified == 'deleted') {
									display_message("{/literal}{$text.theme_message_menu_reset}{literal}", 'positive', 1000);
									document.location.reload();
								}
							}
						}
					}
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
					search_domains('domains_list');

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
					{if $settings.theme.keyboard_shortcut_delete_enabled}
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
						if ((((e.which == 97 || e.which == 65) && (e.ctrlKey || e.metaKey) && !e.shiftKey) || e.which == 19) && !(e.target.tagName == 'INPUT' && e.target.type == 'text') && e.target.tagName != 'TEXTAREA') {
							var all_checkboxes;
							all_checkboxes = document.querySelectorAll('table.list tr.list-header th.checkbox input[name=checkbox_all]');
							if (typeof all_checkboxes != 'object' || all_checkboxes.length == 0) {
								all_checkboxes = document.querySelectorAll('td.edit_delete_checkbox_all > span > input[name=checkbox_all]');
							}
							if (typeof all_checkboxes == 'object' && all_checkboxes.length > 0) {
								e.preventDefault();
								for (var x = 0, max = all_checkboxes.length; x < max; x++) {
									all_checkboxes[x].click();
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
					{if $settings.theme.keyboard_shortcut_copy_enabled}
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
					$('.datetimepicker-future').datetimepicker({ format: 'YYYY-MM-DD HH:mm', minDate: new Date(), });
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
			if (checked == true) {
				if (btn_copy) {
					btn_copy.style.display = "inline";
				}
				if (btn_toggle) {
					btn_toggle.style.display = "inline";
				}
				if (btn_delete) {
					btn_delete.style.display = "inline";
				}
			}
		 	else {
				if (btn_copy) {
					btn_copy.style.display = "none";
				}
				if (btn_toggle) {
					btn_toggle.style.display = "none";
				}
				if (btn_delete) {
					btn_delete.style.display = "none";
				}
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
		{$session_timer}

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
				document.getElementById('domain_count').innerText = '('+ obj.length +')';

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

	{*//message container *}
		<div id='message_container'></div>

	{*//domain selector *}
		{if $authenticated && $domain_selector_enabled}

			<div id='domains_container'>
				<input type='hidden' id='domains_visible' value='0'>
				<div id='domains_block'>
					<div id='domains_header'>
						<input id='domains_hide' type='button' class='btn' style='float: right' value="{$text.theme_button_close}">
						<a id='domains_title' href='{$domains_app_path}'>{$text.theme_title_domains} <span id='domain_count' style='font-size: 80%;'></span></a>
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
