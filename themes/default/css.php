<?php

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";

ob_start('ob_gzhandler');
header('Content-type: text/css; charset: UTF-8');
header('Cache-Control: must-revalidate');
header('Expires: '.gmdate('D, d M Y H:i:s',time()+3600).' GMT');

/***********************************************************************************************************************************************/
/* SET DEFAULTS */

$pre_text_color = $_SESSION['theme']['pre_text_color']['text'] ?? '#5f5f5f';
$footer_background_color = $_SESSION['theme']['footer_background_color']['text'] ?? 'rgba(0,0,0,0.2)';
$footer_border_radius = $_SESSION['theme']['footer_border_radius']['text'] ?? null;
$footer_background_color = $_SESSION['theme']['footer_background_color']['text'] ?? 'rgba(0,0,0,0.2)';
$footer_color = $_SESSION['theme']['footer_color']['text'] ?? 'rgba(255,255,255,0.3)';
$menu_main_background_image = $_SESSION['theme']['menu_main_background_image']['text'] ?? null;
$menu_main_background_color = $_SESSION['theme']['menu_main_background_color']['text'] ?? 'rgba(0,0,0,0.90)';
$menu_main_shadow_color = isset($_SESSION['theme']['menu_main_shadow_color']['text']) ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';
$menu_main_border_color = $_SESSION['theme']['menu_main_border_color']['text'] ?? 'transparent';
$menu_main_border_size = $_SESSION['theme']['menu_main_border_size']['text'] ?? 0;
$menu_position = $_SESSION['theme']['menu_position']['text'] ?? 'top';
$menu_style = $_SESSION['theme']['menu_style']['text'] ?? 'fixed';
switch ($menu_style) {
	case 'inline': $menu_main_border_radius_default = '4px'; break;
	case 'static': $menu_main_border_radius_default = '0 0 4px 4px'; break;
	default: $menu_main_border_radius_default = '0';
}
$menu_main_border_radius = $_SESSION['theme']['menu_main_border_radius']['text'] ?? $menu_main_border_radius_default;
$menu_brand_text_color = $_SESSION['theme']['menu_brand_text_color']['text'] ?? 'rgba(255,255,255,0.80)';
$menu_brand_text_size = $_SESSION['theme']['menu_brand_text_size']['text'] ?? '13pt';
$menu_brand_text_color_hover = $_SESSION['theme']['menu_brand_text_color_hover']['text'] ?? 'rgba(255,255,255,1.0)';
$menu_main_text_font = $_SESSION['theme']['menu_main_text_font']['text'] ?? 'arial';
$menu_main_text_size = $_SESSION['theme']['menu_main_text_size']['text'] ?? '10.25pt';
$menu_main_text_color = $_SESSION['theme']['menu_main_text_color']['text'] ?? '#fff';
$menu_main_text_color_hover = $_SESSION['theme']['menu_main_text_color_hover']['text'] ?? '#fd9c03';
$menu_main_background_color_hover = $_SESSION['theme']['menu_main_background_color_hover']['text'] ?? 'rgba(0,0,0,1.0)';
$menu_sub_border_color = $_SESSION['theme']['menu_sub_border_color']['text'] ?? 'transparent';
$menu_sub_border_size = $_SESSION['theme']['menu_sub_border_size']['text'] ?? 0;
$menu_sub_background_color = $_SESSION['theme']['menu_sub_background_color']['text'] ?? 'rgba(0,0,0,0.90)';
$menu_sub_shadow_color = isset($_SESSION['theme']['menu_sub_shadow_color']['text']) ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';
$menu_sub_border_radius = $_SESSION['theme']['menu_sub_border_radius']['text'] ?? null;
$menu_sub_text_font = $_SESSION['theme']['menu_sub_text_font']['text'] ?? 'arial';
$menu_sub_text_color = $_SESSION['theme']['menu_sub_text_color']['text'] ?? '#fff';
$menu_sub_text_size = $_SESSION['theme']['menu_sub_text_size']['text'] ?? '10pt';
$menu_sub_text_color_hover = $_SESSION['theme']['menu_sub_text_color_hover']['text'] ?? '#fd9c03';
$menu_sub_background_color_hover = $_SESSION['theme']['menu_sub_background_color_hover']['text'] ?? '#141414';
$header_user_color_hover = $_SESSION['theme']['header_user_color_hover']['text'] ?? '#1892e6';
$header_domain_color_hover = $_SESSION['theme']['header_domain_color_hover']['text'] ?? '#1892e6';
$logout_icon_color = $_SESSION['theme']['logout_icon_color']['text'] ?? 'rgba(255,255,255,0.8)';
$logout_icon_color_hover = $_SESSION['theme']['logout_icon_color_hover']['text'] ?? 'rgba(255,255,255,1.0)';
$menu_main_toggle_color = $_SESSION['theme']['menu_main_toggle_color']['text'] ?? 'rgba(255,255,255,0.8)';
$menu_main_toggle_color_hover = $_SESSION['theme']['menu_main_toggle_color_hover']['text'] ?? 'rgba(255,255,255,1.0)';
$menu_side_state = $_SESSION['theme']['menu_side_state']['text'] ?? null;
$menu_side_width_expanded = $_SESSION['theme']['menu_side_width_expanded']['text'] ?? 225;
$menu_side_width_contracted = $_SESSION['theme']['menu_side_width_contracted']['text'] ?? 60;
$menu_main_icon_color = $_SESSION['theme']['menu_main_icon_color']['text'] ?? '#fd9c03';
$menu_main_icon_color_hover = $_SESSION['theme']['menu_main_icon_color_hover']['text'] ?? '#fd9c03';
$body_header_background_color = $_SESSION['theme']['body_header_background_color']['text'] ?? 'transparent';
$body_header_brand_text_color = $_SESSION['theme']['body_header_brand_text_color']['text'] ?? 'rgba(0,0,0,0.90)';
$body_header_brand_text_color_hover = $_SESSION['theme']['body_header_brand_text_color_hover']['text'] ?? 'rgba(0,0,0,1.0)';
$body_header_brand_text_size = $_SESSION['theme']['body_header_brand_text_size']['text'] ?? '16px';
$button_height = $_SESSION['theme']['button_height']['text'] ?? '28px';
$button_padding = $_SESSION['theme']['button_padding']['text'] ?? '5px 8px';
$button_border_size = $_SESSION['theme']['button_border_size']['text'] ?? '1px';
$button_border_color = $_SESSION['theme']['button_border_color']['text'] ?? '#242424';
$button_border_radius = $_SESSION['theme']['button_border_radius']['text'] ?? null;
$button_background_color = $_SESSION['theme']['button_background_color']['text'] ?? '#4f4f4f';
$button_background_color_bottom = $_SESSION['theme']['button_background_color_bottom']['text'] ?? '#000000';
$button_text_font = $_SESSION['theme']['button_text_font']['text'] ?? 'Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif';
$button_text_color = $_SESSION['theme']['button_text_color']['text'] ?? '#ffffff';
$button_text_weight = $_SESSION['theme']['button_text_weight']['text'] ?? 'bold';
$button_text_size = $_SESSION['theme']['button_text_size']['text'] ?? '11px';
$button_border_color_hover = $_SESSION['theme']['button_border_color_hover']['text'] ?? '#000000';
$button_background_color_hover = $_SESSION['theme']['button_background_color_hover']['text'] ?? '#000000';
$button_background_color_bottom_hover = $_SESSION['theme']['button_background_color_bottom_hover']['text'] ?? '#000000';
$button_text_color_hover = $_SESSION['theme']['button_text_color_hover']['text'] ?? '#ffffff';
$button_icons = $_SESSION['theme']['button_icons']['text'] ?? 'auto';
$body_icon_color = $_SESSION['theme']['body_icon_color']['text'] ?? 'rgba(0,0,0,0.25)';
$body_icon_color_hover = $_SESSION['theme']['body_icon_color_hover']['text'] ?? 'rgba(0,0,0,0.5)';
$domain_selector_background_color = $_SESSION['theme']['domain_selector_background_color']['text'] ?? '#fff';
$domain_selector_shadow_color = isset($_SESSION['theme']['domain_selector_shadow_color']['text']) ? '0 0 10px '.$_SESSION['theme']['domain_selector_shadow_color']['text'] : 'none';
$domain_selector_title_color = $_SESSION['theme']['domain_selector_title_color']['text'] ?? '#000';
$domain_selector_title_color_hover = $_SESSION['theme']['domain_selector_title_color_hover']['text'] ?? '#5082ca';
$domain_selector_list_background_color = $_SESSION['theme']['domain_selector_list_background_color']['text'] ?? '#fff';
$domain_selector_list_border_color = $_SESSION['theme']['domain_selector_list_border_color']['text'] ?? '#a4aebf';
$domain_selector_list_divider_color = $_SESSION['theme']['domain_selector_list_divider_color']['text'] ?? '#c5d1e5';
$domain_active_text_color = $_SESSION['theme']['domain_active_text_color']['text'] ?? '#004083';
$domain_active_text_color_hover = $_SESSION['theme']['domain_active_text_color_hover']['text'] ?? '#004083';
$domain_inactive_text_color = $_SESSION['theme']['domain_inactive_text_color']['text'] ?? '#004083';
$domain_inactive_text_color_hover = $_SESSION['theme']['domain_inactive_text_color_hover']['text'] ?? '#004083';
$domain_active_desc_text_color = $_SESSION['theme']['domain_active_desc_text_color']['text'] ?? '#999';
$domain_inactive_desc_text_color = $_SESSION['theme']['domain_inactive_desc_text_color']['text'] ?? '#999';
$heading_text_size = $_SESSION['theme']['heading_text_size']['text'] ?? '15px';
$heading_text_font = $_SESSION['theme']['heading_text_font']['text'] ?? 'arial';
$login_body_top = $_SESSION['theme']['login_body_top']['text'] ?? '50%';
$login_body_left = $_SESSION['theme']['login_body_left']['text'] ?? '50%';
$login_body_padding = $_SESSION['theme']['login_body_padding']['text'] ?? '30px';
$login_body_width = $_SESSION['theme']['login_body_width']['text'] ?? 'auto';
$login_body_background_color = $_SESSION['theme']['login_body_background_color']['text'] ?? 'rgba(255,255,255,0.35)';
$login_body_border_radius = $_SESSION['theme']['login_body_border_radius']['text'] ?? null;
$login_body_border_size = $_SESSION['theme']['login_body_border_size']['text'] ?? 0;
$login_body_border_color = $_SESSION['theme']['login_body_border_color']['text'] ?? 'transparent';
$login_body_border_style = $login_body_border_size || $login_body_border_color ? 'solid' : 'none';
$login_body_shadow_color = isset($_SESSION['theme']['login_body_shadow_color']['text']) ? '0 1px 20px '.$_SESSION['theme']['login_body_shadow_color']['text'] : 'none';
$login_link_text_color = $_SESSION['theme']['login_link_text_color']['text'] ?? '#004083';
$login_link_text_size = $_SESSION['theme']['login_link_text_size']['text'] ?? '11px';
$login_link_text_font = $_SESSION['theme']['login_link_text_font']['text'] ?? 'Arial';
$login_link_text_color_hover = $_SESSION['theme']['login_link_text_color_hover']['text'] ?? '#5082ca';
$body_color = $_SESSION['theme']['body_color']['text'] ?? '#ffffff';
$body_border_radius = $_SESSION['theme']['body_border_radius']['text'] ?? null;
$body_shadow_color = isset($_SESSION['theme']['body_shadow_color']['text']) ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';
$body_text_color = $_SESSION['theme']['body_text_color']['text'] ?? '#5f5f5f';
$body_text_size = $_SESSION['theme']['body_text_size']['text'] ?? '12px';
$body_text_font = $_SESSION['theme']['body_text_font']['text'] ?? 'arial';
$body_width = $_SESSION['theme']['body_width']['text'] ?? '90%';
$heading_text_color = $_SESSION['theme']['heading_text_color']['text'] ?? '#952424';
$heading_text_size = $_SESSION['theme']['heading_text_size']['text'] ?? '15px';
$heading_text_font = $_SESSION['theme']['heading_text_font']['text'] ?? 'arial';
$text_link_color = $_SESSION['theme']['text_link_color']['text'] ?? '#004083';
$text_link_color_hover = $_SESSION['theme']['text_link_color_hover']['text'] ?? '#5082ca';
$input_text_placeholder_color = $_SESSION['theme']['input_text_placeholder_color']['text'] ?? '#999999; opacity: 1.0;';
$input_text_font = $_SESSION['theme']['input_text_font']['text'] ?? 'Arial';
$input_text_size = $_SESSION['theme']['input_text_size']['text'] ?? '12px';
$input_text_color = $_SESSION['theme']['input_text_color']['text'] ?? '#000';
$input_border_size = $_SESSION['theme']['input_border_size']['text'] ?? '1px';
$input_border_color = $_SESSION['theme']['input_border_color']['text'] ?? '#c0c0c0';
$input_border_color_hover_focus = $_SESSION['theme']['input_border_color_hover_focus']['text'] ?? '#c0c0c0';
$input_background_color = $_SESSION['theme']['input_background_color']['text'] ?? '#fff';
$input_shadow_inner_color = isset($_SESSION['theme']['input_shadow_inner_color']['text']) ? '0 0 3px '.$_SESSION['theme']['input_shadow_inner_color']['text'].' inset' : null;
$input_shadow_inner_color_focus = isset($_SESSION['theme']['input_shadow_inner_color_focus']['text']) ? '0 0 3px '.$_SESSION['theme']['input_shadow_inner_color_focus']['text'].' inset' : null;
$input_shadow_outer_color = isset($_SESSION['theme']['input_shadow_outer_color']['text']) ? '0 0 5px '.$_SESSION['theme']['input_shadow_outer_color']['text'] : null;
$input_shadow_outer_color_focus = isset($_SESSION['theme']['input_shadow_outer_color_focus']['text']) ? '0 0 5px '.$_SESSION['theme']['input_shadow_outer_color_focus']['text'] : null;
$input_border_radius = $_SESSION['theme']['input_border_radius']['text'] ?? null;
$input_border_color_hover = $_SESSION['theme']['input_border_color_hover']['text'] ?? '#c0c0c0';
$input_border_color_focus = $_SESSION['theme']['input_border_color_focus']['text'] ?? '#c0c0c0';
$login_text_color = $_SESSION['theme']['login_text_color']['text'] ?? '#282828';
$login_text_size = $_SESSION['theme']['login_text_size']['text'] ?? '12px';
$login_text_font = $_SESSION['theme']['login_text_font']['text'] ?? 'Arial';
$login_input_text_font = $_SESSION['theme']['login_input_text_font']['text'] ?? $input_text_font;
$login_input_text_size = $_SESSION['theme']['login_input_text_size']['text'] ?? $input_text_size;
$login_input_text_color = $_SESSION['theme']['login_input_text_color']['text'] ?? $input_text_color;
$login_input_border_size = $_SESSION['theme']['login_input_border_size']['text'] ?? $input_border_size;
$login_input_border_color = $_SESSION['theme']['login_input_border_color']['text'] ?? $input_border_color;
$login_input_background_color = $_SESSION['theme']['login_input_background_color']['text'] ?? $input_background_color;
$login_input_shadow_inner_color = $_SESSION['theme']['login_input_shadow_inner_color']['text'] ?? $input_shadow_inner_color;
$login_input_shadow_inner_color = $login_input_shadow_inner_color != 'none' ? '0 0 3px '.$login_input_shadow_inner_color.' inset' : 'none';
$login_input_shadow_outer_color = $_SESSION['theme']['login_input_shadow_outer_color']['text'] ?? $input_shadow_outer_color;
$login_input_shadow_outer_color = $login_input_shadow_outer_color != 'none' ? '0 0 5px '.$login_input_shadow_outer_color : 'none';
$login_input_shadow_inner_color_focus = $_SESSION['theme']['login_input_shadow_inner_color_focus']['text'] ?? $input_shadow_inner_color_focus;
$login_input_shadow_inner_color_focus = $login_input_shadow_inner_color_focus != 'none' ? '0 0 3px '.$login_input_shadow_inner_color_focus.' inset' : 'none';
$login_input_shadow_outer_color_focus = $_SESSION['theme']['login_input_shadow_outer_color_focus']['text'] ?? $input_shadow_outer_color_focus;
$login_input_shadow_outer_color_focus = $login_input_shadow_outer_color_focus != 'none' ? '0 0 5px '.$login_input_shadow_outer_color_focus : 'none';
$login_input_border_radius = $_SESSION['theme']['login_input_border_radius']['text'] ?? $input_border_radius;
$login_input_border_color_hover = $_SESSION['theme']['login_input_border_color_hover']['text'] ?? $input_border_color_hover;
$login_input_border_color_hover_focus = $_SESSION['theme']['login_input_border_color_hover_focus']['text'] ?? $input_border_color_hover_focus;
$login_input_text_placeholder_color = $_SESSION['theme']['login_input_text_placeholder_color']['text'] ?? $input_text_placeholder_color;
$pwstrength_background_color = $_SESSION['theme']['input_background_color']['text'] ?? 'rgb(245, 245, 245)';
$input_toggle_style = $_SESSION['theme']['input_toggle_style']['text'] ?? 'switch_round';
$input_toggle_switch_background_color_true = $_SESSION['theme']['input_toggle_switch_background_color_true']['text'] ?? '#2e82d0';
$input_toggle_switch_background_color_false = $_SESSION['theme']['input_toggle_switch_background_color_false']['text'] ?? $input_border_color;
$input_toggle_switch_handle_symbol = $_SESSION['theme']['input_toggle_switch_handle_symbol']['boolean'] ?? 'false';
$input_toggle_switch_handle_color = $_SESSION['theme']['input_toggle_switch_handle_color']['boolean'] ?? '#ffffff';
$table_heading_text_color = $_SESSION['theme']['table_heading_text_color']['text'] ?? '#3164ad';
$table_heading_text_size = $_SESSION['theme']['table_heading_text_size']['text'] ?? '12px';
$table_heading_text_font = $_SESSION['theme']['table_heading_text_font']['text'] ?? 'arial';
$table_heading_background_color = $_SESSION['theme']['table_heading_background_color']['text'] ?? 'none';
$table_heading_border_color = $_SESSION['theme']['table_heading_border_color']['text'] ?? '#a4aebf';
$table_heading_padding = $_SESSION['theme']['table_heading_padding']['text'] ?? '4px 7px';
$table_row_text_color = $_SESSION['theme']['table_row_text_color']['text'] ?? '#000';
$table_row_text_font = $_SESSION['theme']['table_row_text_font']['text'] ?? 'arial';
$table_row_text_size = $_SESSION['theme']['table_row_text_size']['text'] ?? '12px';
$table_row_border_color = $_SESSION['theme']['table_row_border_color']['text'] ?? '#c5d1e5';
$table_row_background_color_light = $_SESSION['theme']['table_row_background_color_light']['text'] ?? '#fff';
$table_row_background_color_medium = $_SESSION['theme']['table_row_background_color_medium']['text'] ?? '#f0f2f6';
$table_row_background_color_dark = $_SESSION['theme']['table_row_background_color_dark']['text'] ?? '#e5e9f0';
$table_row_padding = $_SESSION['theme']['table_row_padding']['text'] ?? '4px 7px';
$form_table_label_background_color = $_SESSION['theme']['form_table_label_background_color']['text'] ?? '#e5e9f0';
$form_table_label_border_radius = $_SESSION['theme']['form_table_label_border_radius']['text'] ?? null;
$form_table_label_border_color = $_SESSION['theme']['form_table_label_border_color']['text'] ?? '#ffffff';
$form_table_label_padding = $_SESSION['theme']['form_table_label_padding']['text'] ?? '7px 8px';
$form_table_label_text_color = $_SESSION['theme']['form_table_label_text_color']['text'] ?? '#000000';
$form_table_label_text_font = $_SESSION['theme']['form_table_label_text_font']['text'] ?? 'Arial';
$form_table_label_text_size = $_SESSION['theme']['form_table_label_text_size']['text'] ?? '9pt';
$form_table_label_required_background_color = $_SESSION['theme']['form_table_label_required_background_color']['text'] ?? '#e5e9f0';
$form_table_label_required_border_color = $_SESSION['theme']['form_table_label_required_border_color']['text'] ?? '#cbcfd5';
$form_table_label_required_text_color = $_SESSION['theme']['form_table_label_required_text_color']['text'] ?? '#000';
$form_table_label_required_text_weight = $_SESSION['theme']['form_table_label_required_text_weight']['text'] ?? 'bold';
$form_table_field_background_color = $_SESSION['theme']['form_table_field_background_color']['text'] ?? '#fff';
$form_table_field_border_radius = $_SESSION['theme']['form_table_field_border_radius']['text'] ?? null;
$form_table_field_border_color = $_SESSION['theme']['form_table_field_border_color']['text'] ?? '#e5e9f0';
$form_table_field_padding = $_SESSION['theme']['form_table_field_padding']['text'] ?? '6px';
$form_table_field_text_color = $_SESSION['theme']['form_table_field_text_color']['text'] ?? '#666';
$form_table_field_text_font = $_SESSION['theme']['form_table_field_text_font']['text'] ?? 'Arial';
$form_table_field_text_size = $_SESSION['theme']['form_table_field_text_size']['text'] ?? '8pt';
$form_table_heading_padding = $_SESSION['theme']['form_table_heading_padding']['text'] ?? '8px 8px 4px 8px';
$form_table_row_padding = $_SESSION['theme']['form_table_row_padding']['text'] ?? null;
$message_default_color = $_SESSION['theme']['message_default_color']['text'] ?? '#666';
$message_default_background_color = $_SESSION['theme']['message_default_background_color']['text'] ?? '#fafafa';
$message_positive_color = $_SESSION['theme']['message_positive_color']['text'] ?? '#004200';
$message_positive_background_color = $_SESSION['theme']['message_positive_background_color']['text'] ?? '#ccffcc';
$message_negative_color = $_SESSION['theme']['message_negative_color']['text'] ?? '#670000';
$message_negative_background_color = $_SESSION['theme']['message_negative_background_color']['text'] ?? '#ffcdcd';
$message_alert_color = $_SESSION['theme']['message_alert_color']['text'] ?? '#d66721';
$message_alert_background_color = $_SESSION['theme']['message_alert_background_color']['text'] ?? '#ffe585';
$operator_panel_border_color = $_SESSION['theme']['operator_panel_border_color']['text'] ?? '#b9c5d8';
$operator_panel_sub_background_color = $_SESSION['theme']['operator_panel_sub_background_color']['text'] ?? '#e5eaf5';
$operator_panel_main_background_color = $_SESSION['theme']['operator_panel_main_background_color']['text'] ?? '#fff';
$dashboard_detail_background_color_edge = $_SESSION['theme']['dashboard_detail_background_color_edge']['text'] ?? '#edf1f7';
$dashboard_detail_background_color_center = $_SESSION['theme']['dashboard_detail_background_color_center']['text'] ?? '#f9fbfe';
$dashboard_border_radius = $_SESSION['theme']['dashboard_border_radius']['text'] ?? '5px';
$dashboard_border_color = $_SESSION['theme']['dashboard_border_color']['text'] ?? '#dbe0ea';
$dashboard_border_color_hover = $_SESSION['theme']['dashboard_border_color_hover']['text'] ?? '#cbd3e1';
$dashboard_heading_text_color = $_SESSION['theme']['dashboard_heading_text_color']['text'] ?? '#fff';
$dashboard_heading_text_color_hover = $_SESSION['theme']['dashboard_heading_text_color_hover']['text'] ?? '#fff';
$dashboard_heading_text_size = $_SESSION['theme']['dashboard_heading_text_size']['text'] ?? '12pt';
$dashboard_heading_text_font = $_SESSION['theme']['dashboard_heading_text_font']['text'] ?? 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif';
$dashboard_heading_text_shadow_color = $_SESSION['theme']['dashboard_heading_text_shadow_color']['text'] ?? '#000';
$dashboard_heading_background_color = $_SESSION['theme']['dashboard_heading_background_color']['text'] ?? '#8e96a5';
$dashboard_heading_background_color_hover = $_SESSION['theme']['dashboard_heading_background_color_hover']['text'] ?? color_adjust($dashboard_heading_background_color, 0.03);
$dashboard_number_text_color = $_SESSION['theme']['dashboard_number_text_color']['text'] ?? '#fff';
$dashboard_number_text_color_hover = $_SESSION['theme']['dashboard_number_text_color_hover']['text'] ?? '#fff';
$dashboard_number_text_font = $_SESSION['theme']['dashboard_number_text_font']['text'] ?? 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif';
$dashboard_number_text_size = $_SESSION['theme']['dashboard_number_text_size']['text'] ?? '60pt';
$dashboard_number_text_shadow_color = $_SESSION['theme']['dashboard_number_text_shadow_color']['text'] ?? '#737983';
$dashboard_number_text_shadow_color_hover = $_SESSION['theme']['dashboard_number_text_shadow_color_hover']['text'] ?? '#737983';
$dashboard_number_background_color = $_SESSION['theme']['dashboard_number_background_color']['text'] ?? '#a4aebf';
$dashboard_number_background_color_hover = $_SESSION['theme']['dashboard_number_background_color_hover']['text'] ?? color_adjust($dashboard_number_background_color, 0.03);
$dashboard_number_title_text_color = $_SESSION['theme']['dashboard_number_title_text_color']['text'] ?? '#fff';
$dashboard_number_title_text_size = $_SESSION['theme']['dashboard_number_title_text_size']['text'] ?? '14px';
$dashboard_number_title_text_font = $_SESSION['theme']['dashboard_number_title_text_font']['text'] ?? 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif';
$dashboard_number_title_text_shadow_color = $_SESSION['theme']['dashboard_number_title_text_shadow_color']['text'] ?? '#737983';
$dashboard_detail_shadow_color = $_SESSION['theme']['dashboard_detail_shadow_color']['text'] ?? '#737983';
$dashboard_detail_heading_text_size = $_SESSION['theme']['dashboard_detail_heading_text_size']['text'] ?? '11px';
$dashboard_detail_background_color_edge = $_SESSION['theme']['dashboard_detail_background_color_edge']['text'] ?? '#edf1f7';
$dashboard_detail_background_color_center = $_SESSION['theme']['dashboard_detail_background_color_center']['text'] ?? '#f9fbfe';
$dashboard_detail_row_text_size = $_SESSION['theme']['dashboard_detail_row_text_size']['text'] ?? '11px';
$dashboard_footer_background_color = $_SESSION['theme']['dashboard_footer_background_color']['text'] ?? '#e5e9f0';
$dashboard_footer_background_color_hover = $_SESSION['theme']['dashboard_footer_background_color_hover']['text'] ?? color_adjust($dashboard_footer_background_color, 0.02);
$dashboard_footer_dots_color = $_SESSION['theme']['dashboard_footer_dots_color']['text'] ?? '#a4aebf';
$dashboard_footer_dots_color_hover = $_SESSION['theme']['dashboard_footer_dots_color_hover']['text'] ?? $dashboard_footer_dots_color;
$action_bar_border_top = $_SESSION['theme']['action_bar_border_top']['text'] ?? 0;
$action_bar_border_right = $_SESSION['theme']['action_bar_border_right']['text'] ?? 0;
$action_bar_border_bottom = $_SESSION['theme']['action_bar_border_bottom']['text'] ?? 0;
$action_bar_border_left = $_SESSION['theme']['action_bar_border_left']['text'] ?? 0;
$action_bar_border_radius = $_SESSION['theme']['action_bar_border_radius']['text'] ?? 0;
$action_bar_background = $_SESSION['theme']['action_bar_background']['text'] ?? 'none';
$action_bar_shadow = $_SESSION['theme']['action_bar_shadow']['text'] ?? 'none';
$action_bar_border_top_scroll = $_SESSION['theme']['action_bar_border_top_scroll']['text'] ?? 'initial';
$action_bar_border_right_scroll = $_SESSION['theme']['action_bar_border_right_scroll']['text'] ?? 'initial';
$action_bar_border_bottom_scroll = $_SESSION['theme']['action_bar_border_bottom_scroll']['text'] ?? 'initial';
$action_bar_border_left_scroll = $_SESSION['theme']['action_bar_border_left_scroll']['text'] ?? 'initial';
$action_bar_border_radius_scroll = $_SESSION['theme']['action_bar_border_radius_scroll']['text'] ?? 'initial';
$action_bar_background_scroll = $_SESSION['theme']['action_bar_background_scroll']['text'] ?? 'rgba(255,255,255,0.9)';
$action_bar_shadow_scroll = $_SESSION['theme']['action_bar_shadow_scroll']['text'] ?? '0 3px 3px 0 rgba(0,0,0,0.2)';
$modal_transition_seconds = $_SESSION['theme']['modal_transition_seconds']['text'] ?? 0.03;
$modal_shade_color = $_SESSION['theme']['modal_shade_color']['text'] ?? 'rgba(0, 0, 0, 0.3)';
$modal_padding = $_SESSION['theme']['modal_padding']['text'] ?? '15px 20px 20px 20px';
$modal_background_color = $_SESSION['theme']['modal_background_color']['text'] ?? '#fff';
$modal_width = $_SESSION['theme']['modal_width']['text'] ?? '500px';
$modal_corner_radius = $_SESSION['theme']['modal_corner_radius']['text'] ?? '5px';
$modal_shadow = $_SESSION['theme']['modal_shadow']['text'] ?? '0 0 40px rgba(0,0,0,0.25)';
$modal_title_font = $_SESSION['theme']['modal_title_font']['text'] ?? $heading_text_font;
$modal_title_color = $_SESSION['theme']['modal_title_color']['text'] ?? $heading_text_color;
$modal_title_alignment = $_SESSION['theme']['modal_title_alignment']['text'] ?? 'left';
$modal_title_margin = $_SESSION['theme']['modal_title_margin']['text'] ?? '0 0 15px 0';
$modal_close_color = $_SESSION['theme']['modal_close_color']['text'] ?? '#aaa';
$modal_close_color_hover = $_SESSION['theme']['modal_close_color_hover']['text'] ?? '#000';
$modal_close_corner_radius = $_SESSION['theme']['modal_close_corner_radius']['text'] ?? '0 0 0 5px';
$modal_close_background_color = $_SESSION['theme']['modal_close_background_color']['text'] ?? '#fff';
$modal_close_background_color_hover = $_SESSION['theme']['modal_close_background_color_hover']['text'] ?? '#fff';
$modal_message_color = $_SESSION['theme']['modal_message_color']['text'] ?? '#444';
$modal_message_alignment = $_SESSION['theme']['modal_message_alignment']['text'] ?? 'left';
$modal_message_margin = $_SESSION['theme']['modal_message_margin']['text'] ?? '0 0 20px 0';
$custom_css_code = $_SESSION['theme']['custom_css_code']['text'] ?? null;

/***********************************************************************************************************************************************/


//parse fonts (add surrounding single quotes to each font name)
if (!empty($_SESSION['theme'])) {
	foreach ($_SESSION['theme'] as $subcategory => $type) {
		if (substr($subcategory, -5) == '_font') {
			$font_string = $type['text'];
			if (!empty($font_string)) {
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
				if (sizeof($fonts) == 1 && strtolower($fonts[0]) != 'arial') { $fonts[] = 'Arial'; } //fall back font
				$_SESSION['theme'][$subcategory]['text'] = "'".implode("','", $fonts)."'";
			}
		}
		unset($fonts, $tmp_array);
	}
}

//determine which background image/color settings to use (login or standard)
$background_images_enabled = false;
if (!empty($_SESSION['username'])) {
	//logged in - use standard background images/colors
	if (!empty($_SESSION['theme']['background_image_enabled']) && $_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['background_image'])) {
		$background_images_enabled = true;
		$background_images = $_SESSION['theme']['background_image'];
	}
	else {
		$background_colors[0] = $_SESSION['theme']['background_color'][0] ?? null;
		$background_colors[1] = $_SESSION['theme']['background_color'][1] ?? null;
	}
}
else {
	//not logged in - try using login background images/colors
	if (isset($_SESSION['theme']['login_background_image_enabled']['boolean']) && $_SESSION['theme']['login_background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['login_background_image'])) {
		$background_images_enabled = true;
		$background_images = $_SESSION['theme']['login_background_image'];
	}
	else if (!empty($_SESSION['theme']['login_background_color'][0]) || !empty($_SESSION['theme']['login_background_color'][1])) {
		$background_colors[0] = $_SESSION['theme']['login_background_color'][0] ?? null;
		$background_colors[1] = $_SESSION['theme']['login_background_color'][1] ?? null;
	}
	else {
		//otherwise, use standard background images/colors
		if (!empty($_SESSION['theme']['background_image_enabled']['boolean']) && $_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['background_image'])) {
			$background_images_enabled = true;
			$background_images = $_SESSION['theme']['background_image'];
		}
		else {
			$background_colors[0] = $_SESSION['theme']['background_color'][0] ?? null;
			$background_colors[1] = $_SESSION['theme']['background_color'][1] ?? null;
		}
	}
}

//check for background image
if ($background_images_enabled) {
	//background image is enabled
	$image_extensions = array('jpg','jpeg','png','gif');

	if (count($background_images) > 0) {

		if ((!isset($_SESSION['background_image'])) or empty($_SESSION['background_image'])) {
			$_SESSION['background_image'] = $background_images[array_rand($background_images)];
			$background_image = $_SESSION['background_image'];
		}

		//background image(s) specified, check if source is file or folder
		if (in_array(strtolower(pathinfo($background_image, PATHINFO_EXTENSION)), $image_extensions)) {
			$image_source = 'file';
		}
		else {
			$image_source = 'folder';
		}

		//is source (file/folder) local or remote
		if (substr($background_image, 0, 4) == 'http') {
			$source_path = $background_image;
		}
		else if (substr($background_image, 0, 1) == '/') { //
			//use project path as root
			$source_path = PROJECT_PATH.$background_image;
		}
		else {
			//use theme images/backgrounds folder as root
			$source_path = PROJECT_PATH.'/themes/default/images/backgrounds/'.$background_image;
		}

	}
	else {
		//not set, so use default backgrounds folder and images
		$image_source = 'folder';
		$source_path = PROJECT_PATH.'/themes/default/images/backgrounds';
	}

	if ($image_source == 'folder') {
		if (file_exists($_SERVER["DOCUMENT_ROOT"].$source_path)) {
			//retrieve a random background image
			$dir_list = opendir($_SERVER["DOCUMENT_ROOT"].$source_path);
			$v_background_array = array();
			$x = 0;
			while (false !== ($file = readdir($dir_list))) {
				if ($file != "." AND $file != ".."){
					$new_path = $dir.'/'.$file;
					$level = explode('/',$new_path);
					if (in_array(strtolower(pathinfo($new_path, PATHINFO_EXTENSION)), $image_extensions)) {
						$v_background_array[] = $new_path;
					}
					if ($x > 100) { break; };
					$x++;
				}
			}
			if (empty($_SESSION['background_image']) && !empty($v_background_array)) {
				$_SESSION['background_image'] = PROJECT_PATH.$source_path.$v_background_array[array_rand($v_background_array, 1)];
			}
		}
		else {
			$_SESSION['background_image'] = '';
		}

	}
	else if ($image_source == 'file') {
		$_SESSION['background_image'] = $source_path;
	}
}

//check for background color
else if (!empty($background_colors[0]) || !empty($background_colors[1])) { //background color 1 or 2 is enabled

	if (!empty($background_colors[0]) && empty($background_colors[1])) { // use color 1
		$background_color = "background: ".$background_colors[0].";";
	}
	else if (empty($background_colors[0]) && !empty($background_colors[1])) { // use color 2
		$background_color = "background: ".$background_colors[1].";";
	}
	else if (!empty($background_colors[0]) && !empty($background_colors[1]) && isset($_SESSION['theme']['background_radial_gradient']['text'])) { // radial gradient
		$background_color = "background: ".$background_colors[0].";\n";
		$background_color .= "background: -ms-radial-gradient(center, circle, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		$background_color .= "background: radial-gradient(circle at center, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
	}
	else if (!empty($background_colors[0]) && !empty($background_colors[1])) { // vertical gradient
		$background_color = "background: ".$background_colors[0].";\n";
		$background_color .= "background: -ms-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		$background_color .= "background: -moz-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		$background_color .= "background: -o-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		$background_color .= "background: -webkit-gradient(linear, left top, left bottom, color-stop(0, ".$background_colors[0]."), color-stop(1, ".$background_colors[1]."));\n";
		$background_color .= "background: -webkit-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		$background_color .= "background: linear-gradient(to bottom, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
	}
}
else { //default: white
	$background_color = "background: #ffffff;\n";
}
?>

	html {
		height: 100%;
		width: 100%;
		}

	body {
		z-index: 1;
		position: absolute;
		margin: 0;
		padding: 0;
		overflow: auto;
		-ms-overflow-style: scrollbar; /* stops ie10+ from displaying auto-hiding scroll bar on top of the body content (the domain selector, specifically) */
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		text-align: center;
		<?php
		if (!empty($_SESSION['background_image'])) {
			echo "background-image: url('".$_SESSION['background_image']."');\n";
			echo "background-size: 100% 100%;\n";
			echo "background-position: top;\n";
		}
		else {
			echo $background_color;
		}
		?>
		background-repeat: no-repeat;
		background-attachment: fixed;
		webkit-background-size:cover;
		-moz-background-size:cover;
		-o-background-size:cover;
		background-size:cover;
		}

	pre {
		white-space: pre-wrap;
		color: <?=$pre_text_color?>;
		}

	div#footer {
		display: inline-block;
		width: 100%;
		background: <?=$footer_background_color?>;
		text-align: center;
		vertical-align: middle;
		margin-bottom: 60px;
		padding: 8px;
		<?php $br = format_border_radius($footer_border_radius, '0 0 4px 4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	div#footer_login {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 0;
		width: 100%;
		background: <?=$footer_background_color?>;
		text-align: center;
		vertical-align: middle;
		padding: 8px;
		}

	.footer {
		font-size: 11px;
		font-family: arial;
		line-height: 14px;
		color: <?=$footer_color?>;
		white-space: nowrap;
		}

	.footer > a:hover {
		color: <?=$footer_color?>;
		}

/* MENU: BEGIN ******************************************************************/

	/* help bootstrap v4 menu be scrollable on mobile */
	@media screen and (max-width: 575px) {
		.navbar-collapse {
			max-height: calc(100vh - 60px);
			overflow-y: auto;
			}
	}

	/* main menu container */
	nav.navbar {
		<?php if ($menu_main_background_image) { ?>
			background-image: url("<?=$menu_main_background_image?>");
			background-position: 0px 0px;
			background-repeat: repeat-x;
		<?php } else { ?>
			background: <?=$menu_main_background_color?>;
		<?php } ?>
		-webkit-box-shadow: <?=$menu_main_shadow_color?>;
		-moz-box-shadow: <?=$menu_main_shadow_color?>;
		box-shadow: <?=$menu_main_shadow_color?>;
		border-color: <?=$menu_main_border_color?>;
		border-width: <?=$menu_main_border_size?>;
		-moz-border-radius: <?=$menu_main_border_radius?>;
		-webkit-border-radius: <?=$menu_main_border_radius?>;
		-khtml-border-radius: <?=$menu_main_border_radius?>;
		border-radius: <?=$menu_main_border_radius?>;
		padding: 0;
		}

	/* main menu logo */
	img.navbar-logo {
		border: none;
		height: 27px;
		width: auto;
		padding: 0 10px 0 7px;
		margin-top: -2px;
		cursor: pointer;
		}

	/* menu brand text */
	div.navbar-brand > a.navbar-brand-text {
		color: <?=$menu_brand_text_color?>;
		font-size: <?=$menu_brand_text_size?>;
		white-space: nowrap;
		}

	/* menu brand text hover */
	div.navbar-brand > a.navbar-brand-text:hover {
		color: <?=$menu_brand_text_color_hover?>;
		text-decoration: none;
		}

	/* main menu item */
	ul.navbar-nav > li.nav-item > a.nav-link {
		font-family: <?=$menu_main_text_font?>;
		font-size: <?=$menu_main_text_size?>;
		color: <?=$menu_main_text_color?>;
		padding: 15px 10px 14px 10px; !important;
		}

	ul.navbar-nav > li.nav-item:hover > a.nav-link,
	ul.navbar-nav > li.nav-item:focus > a.nav-link,
	ul.navbar-nav > li.nav-item:active > a.nav-link {
		color: <?=$menu_main_text_color_hover?>;
		background: <?=$menu_main_background_color_hover?>;
		}

	.navbar .navbar-nav > li > a > span.fas {
		margin: 1px 2px 0 0;
		}

	@media(min-width: 768px) {
		.dropdown:hover .dropdown-menu {
			display: block;
			}
		}

	/* sub menu container */
	ul.navbar-nav > li.nav-item > ul.dropdown-menu {
		margin-top: 0;
		padding-top: 0;
		padding-bottom: 10px;
		border-color: <?=$menu_sub_border_color?>;
		border-width: <?=$menu_sub_border_size?>;
		background: <?=$menu_sub_background_color?>;
		-webkit-box-shadow: <?=$menu_sub_shadow_color?>;
		-moz-box-shadow: <?=$menu_sub_shadow_color?>;
		box-shadow: <?=$menu_sub_shadow_color?>;
		<?php $br = format_border_radius($menu_sub_border_radius, '0 0 4px 4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	/* sub menu container (multiple columns) */
	@media(min-width: 576px) {
		ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column {
			width: 330px;
			}
		}

	/* sub menu item */
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column > div.row > div > ul.multi-column-dropdown > li.nav-item > a.nav-link {
		font-family: <?=$menu_sub_text_font?>;
		color: <?=$menu_sub_text_color?>;
		font-size: <?=$menu_sub_text_size?>;
		margin: 0;
		padding: 3px 14px !important;
		}

	ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column > div.row > div > ul.multi-column-dropdown {
		list-style-type: none;
		padding-left: 0;
		}

	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:hover,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:focus,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:active,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column > div.row > div > ul.multi-column-dropdown > li.nav-item > a.nav-link:hover,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column > div.row > div > ul.multi-column-dropdown > li.nav-item > a.nav-link:focus,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu.multi-column > div.row > div > ul.multi-column-dropdown > li.nav-item > a.nav-link:active {
		color: <?=$menu_sub_text_color_hover?>;
		background: <?=$menu_sub_background_color_hover?>;
		outline: none;
		}

	a.nav-link {
		text-align: left !important;
		}

	/* sub menu item icon */
	ul.dropdown-menu > li.nav-item > a.nav-link > span.fas {
		display: inline-block;
		font-size: 8pt;
		margin: 0 0 0 8px;
		opacity: 0.30;
		}

	/* header domain/user name */
	ul.navbar-nav > li.nav-item > a.header_user,
	ul.navbar-nav > li.nav-item > a.header_domain {
		font-family: <?=$menu_main_text_font?>;
		font-size: <?=$menu_main_text_size?>;
		color: <?=$menu_main_text_color?>;
		padding: 10px;
		}

	ul.navbar-nav > li.nav-item:hover > a.header_user,
	ul.navbar-nav > li.nav-item:focus > a.header_user,
	ul.navbar-nav > li.nav-item:active > a.header_user {
		color: <?=$header_user_color_hover?>;
		}

	ul.navbar-nav > li.nav-item:hover > a.header_domain,
	ul.navbar-nav > li.nav-item:focus > a.header_domain,
	ul.navbar-nav > li.nav-item:active > a.header_domain {
		color: <?=$header_domain_color_hover?>;
		}

	/* logout icon */
	a.logout_icon {
		color: <?=$logout_icon_color?>;
		padding: 14px 10px;
		}

	a.logout_icon:hover,
	a.logout_icon:focus,
	a.logout_icon:active {
		color: <?=$logout_icon_color_hover?>;
		background: <?=$menu_main_background_color_hover?>;
		}

	a#header_logout_icon {
		display: inline-block;
		font-size: 11pt;
		padding-left: 5px;
		padding-right: 5px;
		margin-left: 5px;
		margin-right: 5px;
		}

	/* xs menu toggle button */
/*
	.navbar-inverse .navbar-toggle {
		background: transparent;
		border: none;
		padding: 16px 7px 17px 20px;
		margin: 0 8px;
		}

	.navbar-inverse .navbar-toggle:hover,
	.navbar-inverse .navbar-toggle:focus,
	.navbar-inverse .navbar-toggle:active {
		background: transparent;
		}
*/

	button.navbar-toggler {
		min-height: 50px;
		}

	button.navbar-toggler > span.fas.fa-bars {
		color: <?=$menu_main_toggle_color?>;
		}

	button.navbar-toggler > span.fas.fa-bars:hover {
		color: <?=$menu_main_toggle_color_hover?>;
		}

/* SIDE MENU: Begin ***********************************************************/

	/* side menu container */
	div#menu_side_container {
		z-index: 99900;
		position: fixed;
		top: 0;
		left: 0;
		width: <?php echo in_array($menu_side_state, ['expanded','hidden']) ? $menu_side_width_expanded : $menu_side_width_contracted; ?>px;
		height: 100%;
		overflow: auto;
		<?php if ($menu_main_background_image) { ?>
			background-image: url("<?=$menu_main_background_image?>");
			background-position: 0px 0px;
			background-repeat: repeat-y;
		<?php } else { ?>
			background: <?=$menu_main_background_color?>;
		<?php } ?>
		-webkit-box-shadow: <?=$menu_main_shadow_color?>;
		-moz-box-shadow: <?=$menu_main_shadow_color?>;
		box-shadow: <?=$menu_main_shadow_color?>;
		border-color: <?=$menu_main_border_color?>;
		border-width: <?=$menu_main_border_size?>;
		-moz-border-radius: <?=$menu_main_border_radius?>;
		-webkit-border-radius: <?=$menu_main_border_radius?>;
		-khtml-border-radius: <?=$menu_main_border_radius?>;
		border-radius: <?=$menu_main_border_radius?>;
		}

	/* menu side logo */
	a.menu_brand_image {
		display: inline-block;
		text-align: center;
		padding: 15px 20px;
		}

	a.menu_brand_image:hover {
		text-decoration: none;
		}

	img#menu_brand_image_contracted {
		border: none;
		width: auto;
		max-height: 20px;
		max-width: 20px;
		margin-left: -1px;
		}

	img#menu_brand_image_expanded {
		border: none;
		height: auto;
		max-width: 145px;
		max-height: 35px;
		margin-left: -7px;
		}

	/* menu brand text */
	a.menu_brand_text {
		display: inline-block;
		padding: 10px 20px;
		color: <?=$menu_brand_text_color?>;
		font-weight: 600;
		white-space: nowrap;
		}

	a.menu_brand_text:hover {
		color: <?=$menu_brand_text_color_hover?>;
		text-decoration: none;
		}

	/* menu side control container */
	div#menu_side_control_container {
		position: -webkit-sticky;
		position: sticky;
		z-index: 99901;
		top: 0;
		padding: 0;
		min-height: 75px;
		text-align: left;
		<?php if ($menu_main_background_image) { ?>
			background-image: url("<?=$menu_main_background_image?>");
			background-position: 0px 0px;
			background-repeat: repeat-y;
		<?php } else { ?>
			background: <?=$menu_main_background_color?>;
		<?php } ?>
		border-color: <?=$menu_main_border_color?>;
		border-width: <?=$menu_main_border_size?>;
		-moz-border-radius: <?=$menu_main_border_radius?>;
		-webkit-border-radius: <?=$menu_main_border_radius?>;
		-khtml-border-radius: <?=$menu_main_border_radius?>;
		border-radius: <?=$menu_main_border_radius?>;
		}

	div#menu_side_container > a.menu_side_item_main,
	div#menu_side_container > div > a.menu_side_item_main,
	div#menu_side_container > div#menu_side_control_container a.menu_side_item_main {
		display: block;
		width: 100%;
		padding: 10px 20px;
		text-align: left;
		font-family: <?=$menu_main_text_font?>;
		font-size: <?=$menu_main_text_size?>;
		color: <?=$menu_main_text_color?>;
		cursor: pointer;
		}

	div#menu_side_container > a.menu_side_item_main:hover,
	div#menu_side_container > a.menu_side_item_main:focus,
	div#menu_side_container > a.menu_side_item_main:active,
	div#menu_side_container > div > a.menu_side_item_main:hover,
	div#menu_side_container > div > a.menu_side_item_main:focus,
	div#menu_side_container > div > a.menu_side_item_main:active,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:hover,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:focus,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:active {
		color: <?=$menu_main_text_color_hover?>;
		background: <?=$menu_main_background_color_hover?>;
		text-decoration: none;
		}

	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon {
		color: <?=$menu_main_icon_color?>;
	}

	div#menu_side_container > a.menu_side_item_main:hover > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main:focus > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main:active > i.menu_side_item_icon {
		color: <?=$menu_main_icon_color_hover?>;
	}

	a.menu_side_item_sub {
		display: block;
		width: 100%;
		padding: 5px 20px 5px 45px;
		text-align: left;
		background: <?=$menu_sub_background_color?>;
		font-family: <?=$menu_sub_text_font?>;
		font-size: <?=$menu_sub_text_size?>;
		color: <?=$menu_sub_text_color?>;
		cursor: pointer;
		}

	@media (max-width: 575.98px) {
		a.menu_side_item_sub {
			padding: 8px 20px 8px 45px;
			}
	}

	a.menu_side_item_sub:hover,
	a.menu_side_item_sub:focus,
	a.menu_side_item_sub:active {
		color: <?=$menu_sub_text_color_hover?>;
		background: <?=$menu_sub_background_color_hover?>;
		text-decoration: none;
		}

	a.menu_side_toggle {
		padding: 10px;
		cursor: pointer;
		}

	div#content_container {
		padding: 0;
		padding-top: 0px;
		text-align: center;
		}

	@media (max-width: 575.98px) {
		div#content_container {
			width: 100%;
			}
	}
	@media (min-width: 576px) {
		div#content_container {
			<?php
			if ($menu_side_state == 'expanded') {
				$content_container_width = $menu_side_width_expanded;
			}
			else if ($menu_side_state == 'hidden') {
				$content_container_width = 0;
			}
			else {
				$content_container_width = $menu_side_width_contracted;
			}
			?>
			width: calc(100% - <?=$content_container_width?>px);
			float: right;
			}
	}

/* BODY/HEADER BAR *****************************************************************/

	<?php if ($menu_style == 'side') { ?>
		div#body_header {
			padding: 10px 10px 15px 10px;
			height: 50px;
			background-color: <?=$body_header_background_color?>
			}
	<?php } else { ?>
		div#body_header {
			padding: 10px;
			margin-top: 5px;
			height: 40px;
			}
	<?php } ?>

	div#body_header_brand_image {
		display: inline-block;
		margin-left: 10px;
		}

	div#body_header_brand_image > a:hover {
		text-decoration: none;
		}

	img#body_header_brand_image {
		border: none;
		margin-top: -4px;
		height: auto;
		max-width: 145px;
		max-height: 35px;
		}

	div#body_header_brand_text {
		display: inline-block;
		margin: 3px 0 0 10px;
		}

	div#body_header_brand_text > a {
		color: <?=$body_header_brand_text_color?>;
		font-size: <?=$body_header_brand_text_size?>;
		font-weight: 600;
		text-decoration: none;
		}

	div#body_header_brand_text > a:hover {
		color: <?=$body_header_brand_text_color_hover?>;
		text-decoration: none;
		}

/* BUTTONS ********************************************************************/

	/* buttons */
	input.btn,
	input.button,
	button.btn-default {
		height: <?=$button_height?>;
		padding: <?=$button_padding?>;
		border: <?=$button_border_size?> solid <?=$button_border_color?>;
		<?php $br = format_border_radius($button_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		<?php
		$color_1 = $button_background_color;
		$color_2 = $button_background_color_bottom;
		?>
		background: <?=$color_1?>;
		background-image: -ms-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -moz-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -o-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?=$color_1?>), color-stop(1, <?=$color_2?>));
		background-image: -webkit-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: linear-gradient(to bottom, <?=$color_1?> 0%, <?=$color_2?> 100%);
		<?php unset($color_1, $color_2); ?>
		font-family: <?=$button_text_font?>;
		text-align: center;
		text-transform: uppercase;
		color: <?=$button_text_color?>;
		font-weight: <?=$button_text_weight?>;
		font-size: <?=$button_text_size?>;
		vertical-align: middle;
		white-space: nowrap;
		}

	input.btn:hover,
	input.btn:active,
	input.btn:focus,
	input.button:hover,
	input.button:active,
	input.button:focus,
	button.btn-default:hover,
	button.btn-default:active,
	button.btn-default:focus {
		cursor: pointer;
		border-color: <?=$button_border_color_hover?>;
		<?php
		$color_1 = $button_background_color_hover;
		$color_2 = $button_background_color_bottom_hover;
		?>
		background: <?=$color_1?>;
		background-image: -ms-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -moz-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -o-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?=$color_1?>), color-stop(1, <?=$color_2?>));
		background-image: -webkit-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: linear-gradient(to bottom, <?=$color_1?> 0%, <?=$color_2?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?=$button_text_color_hover?>;
		}

	/* remove (along with icons in theme/default/config.php) after transition to button class */
	button.btn-icon {
		margin: 0 2px;
		white-space: nowrap;
		}

	/* control icons (define after the default bootstrap btn-default class) */
	button.list_control_icon,
	button.list_control_icon_disabled {
		width: 24px;
		height: 24px;
		padding: 2px;
		margin: 1px;
		border: <?=$button_border_size?> solid <?=$button_border_color?>;
		<?php $br = format_border_radius($button_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		<?php
		$color_1 = $button_background_color;
		$color_2 = $button_background_color_bottom;
		?>
		background: <?=$color_1?>;
		background-image: -ms-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -moz-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -o-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?=$color_1?>), color-stop(1, <?=$color_2?>));
		background-image: -webkit-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: linear-gradient(to bottom, <?=$color_1?> 0%, <?=$color_2?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?=$button_text_color?>;
		font-size: 10.5pt;
		text-align: center;
		-moz-opacity: 0.3;
		opacity: 0.3;
		}

	button.list_control_icon:hover,
	button.list_control_icon:active,
	button.list_control_icon:focus {
		cursor: pointer;
		border-color: <?=$button_border_color_hover?>;
		<?php
		$color_1 = $button_background_color_hover;
		$color_2 = $button_background_color_bottom_hover;
		?>
		background: <?=$color_1?>;
		background-image: -ms-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -moz-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -o-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?=$color_1?>), color-stop(1, <?=$color_2?>));
		background-image: -webkit-linear-gradient(top, <?=$color_1?> 0%, <?=$color_2?> 100%);
		background-image: linear-gradient(to bottom, <?=$color_1?> 0%, <?=$color_2?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?=$button_text_color_hover?>;
		-moz-opacity: 1.0;
		opacity: 1.0;
		}

	<?php if (in_array($button_icons, ['always','auto'])) { ?>
		button:not(.btn-link) > span.button-label.pad {
			margin-left: 6px;
			}
	<?php } ?>

	a.disabled,
	button.btn.disabled {
		outline: none; /* hides the dotted outline of the anchor tag on focus/active */
		cursor: default;
		}

/* DISPLAY BREAKPOINTS ****************************************************************/

	/* screen = extra small */
	@media (max-width: 575.98px) {
		.hide-xs,
		.hide-sm-dn,
		.hide-md-dn,
		.hide-lg-dn {
			display: none;
			}

		.show-xs,
		.show-xs-inline,
		.show-sm-dn,
		.show-sm-dn-inline,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-xs-block,
		.show-sm-dn-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-xs-inline-block,
		.show-sm-dn-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-xs-table-cell,
		.show-sm-dn-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = small */
	@media (min-width: 576px) and (max-width: 767.98px) {
		.hide-sm,
		.hide-sm-dn,
		.hide-md-dn,
		.hide-lg-dn,
		.hide-sm-up {
			display: none;
			}

		.show-sm,
		.show-sm-dn,
		.show-sm-dn-inline,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-sm-block,
		.show-sm-dn-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-sm-inline-block,
		.show-sm-dn-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-sm-table-cell,
		.show-sm-dn-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = medium */
	@media (min-width: 768px) and (max-width: 991.98px) {
		.hide-md,
		.hide-md-dn,
		.hide-lg-dn,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-md,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-md-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-md-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-md-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = large */
	@media (min-width: 992px) and (max-width: 1199.98px) {
		.hide-lg,
		.hide-lg-dn,
		.hide-lg-up,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-lg,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-lg-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-lg-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-lg-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen >= extra large */
	@media (min-width: 1200px) {
		.hide-xl,
		.hide-lg-up,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-xl,
		.show-xl-inline {
			display: inline;
			}

		.show-xl-block {
			display: block;
			}

		.show-xl-inline-block {
			display: inline-block;
			}

		.show-xl-table-cell {
			display: table-cell;
			}
	}

	/* hide button labels on medium and smaller screens (only if icons present) */
	@media (max-width: 991.98px) {
		button:not(.btn-link) > span.button-label.hide-md-dn {
			display: none;
			}
	}

/* ICONS *********************************************************************/

	span.icon_body {
		width: 16px;
		height: 16px;
		color: <?=$body_icon_color?>;
		border: 0;
		}

	span.icon_body:hover {
		color: <?=$body_icon_color_hover?>;
		}

/* DOMAIN SELECTOR ***********************************************************/

	#domains_container {
		z-index: 99990;
		position: absolute;
		right: 0;
		top: 0;
		bottom: 0;
		width: 360px;
		overflow: hidden;
		display: none;
		}

	#domains_block {
		position: absolute;
		right: -300px;
		top: 0;
		bottom: 0;
		width: 340px;
		padding: 20px 20px 100px 20px;
		font-family: arial, san-serif;
		font-size: 10pt;
		overflow: hidden;
		background: <?=$domain_selector_background_color?>;
		-webkit-box-shadow: <?=$domain_selector_shadow_color?>;
		-moz-box-shadow: <?=$domain_selector_shadow_color?>;
		box-shadow: <?=$domain_selector_shadow_color?>;
		}

	#domains_header {
		position: relative;
		width: 300px;
		height: 55px;
		margin-bottom: 20px;
		text-align: left;
		}

	#domains_header > a#domains_title {
		font-weight: 600;
		font-size: <?=$heading_text_size?>;
		font-family: <?=$heading_text_font?>;
		color: <?=$domain_selector_title_color?>;
		}

	#domains_header > a#domains_title:hover {
		text-decoration: none;
		color: <?=$domain_selector_title_color_hover?>;
		}

	#domains_list {
		position: relative;
		overflow: auto;
		width: 300px;
		height: 100%;
		padding: 1px;
		background: <?=$domain_selector_list_background_color?>;
		border: 1px solid <?=$domain_selector_list_border_color?>;
		}

	div.domains_list_item, div.domains_list_item_active, div.domains_list_item_inactive {
		text-align: left;
		border-bottom: 1px solid <?=$domain_selector_list_divider_color?>;
		padding: 5px 8px 8px 8px;
		overflow: hidden;
		white-space: nowrap;
		cursor: pointer;
		}

	div.domains_list_item span.domain_list_item_description,
	div.domains_list_item_active span.domain_list_item_description,
	div.domains_list_item_inactive span.domain_list_item_description,

	div.domains_list_item_active span.domain_active_list_item_description,
	div.domains_list_item_inactive span.domain_inactive_list_item_description {
		font-size: 11px;
		}

	div.domains_list_item span.domain_list_item_description,
	div.domains_list_item_active span.domain_list_item_description,
	div.domains_list_item_inactive span.domain_list_item_description {
		color: #999;
		}

	div.domains_list_item_active a {
		color: <?=$domain_active_text_color?>;
		}

	div.domains_list_item_inactive a {
		color: <?=$domain_inactive_text_color?>;
		}

	div.domains_list_item_active span.domain_active_list_item_description {
		color: <?=$domain_active_desc_text_color?>;
		}

	div.domains_list_item_inactive span.domain_inactive_list_item_description {
		color: <?=$domain_inactive_desc_text_color?>;
		}

	div.domains_list_item:hover a,
	div.domains_list_item:hover span {
		color: #5082ca;
		}

	div.domains_list_item_active:hover a,
	div.domains_list_item_active:hover span {
		color: <?=$domain_active_text_color_hover?>;
	}

	div.domains_list_item_inactive:hover a,
	div.domains_list_item_inactive:hover span {
		color: <?=$domain_inactive_text_color_hover?>;
	}

/* DOMAIN SELECTOR: END ********************************************************/

	#default_login {
		position: fixed;
		top: <?=$login_body_top?>;
		left: <?=$login_body_left?>;
		-moz-transform: translate(-50%, -50%);
		-webkit-transform: translate(-50%, -50%);
		-khtml-transform: translate(-50%, -50%);
		transform: translate(-50%, -50%);
		padding: <?=$login_body_padding?>;
		width: <?=$login_body_width?>;
		background: <?=$login_body_background_color?>;
		<?php $br = format_border_radius($login_body_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-style: <?=$login_body_border_style?>;
		border-width: <?=$login_body_border_size?>;
		border-color: <?=$login_body_border_color?>;
		-webkit-box-shadow: <?=$login_body_shadow_color?>;
		-moz-box-shadow: <?=$login_body_shadow_color?>;
		box-shadow: <?=$login_body_shadow_color?>;
		}

	#login_logo {
		text-decoration: none;
		}

	a.login_link {
		color: <?=$login_link_text_color?> !important;
		font-size: <?=$login_link_text_size?>;
		font-family: <?=$login_link_text_font?>;
		text-decoration: none;
		}

	a.login_link:hover {
		color: <?=$login_link_text_color_hover?> !important;
		cursor: pointer;
		text-decoration: none;
		}

	.login_text {
		color: <?=$login_text_color?> !important;
		font-size: <?=$login_text_size?>;
		font-family: <?=$login_text_font?>;
		text-decoration: none;
		}

	<?php
	//determine body padding & margins (overides on main_content style below) based on menu selection
		switch ($menu_style) {
			case 'inline': $body_top_style = "margin-top: -8px;"; break;
			case 'static': $body_top_style = "margin-top: -5px;"; break;
			case 'fixed':
				switch ($menu_position) {
					case 'bottom': $body_top_style = "margin-top: 30px;"; break;
					case 'top':
					default: $body_top_style = "margin-top: 65px;"; break;
				}
		}
	?>

	#main_content {
		display: inline-block;
		width: 100%;
		<?php
		if (isset($background_images) || !empty($background_colors[0]) || !empty($background_colors[1])) {
			?>
			background: <?=$body_color?>;
			background-attachment: fixed;
			<?php $br = format_border_radius($body_border_radius, '4px'); ?>
			-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			<?php unset($br); ?>
			-webkit-box-shadow: <?=$body_shadow_color?>;
			-moz-box-shadow: <?=$body_shadow_color?>;
			box-shadow: <?=$body_shadow_color?>;
			padding: 20px;
			<?php
		}
		else {
			?>padding: 5px 10px 10px 10px;<?php
		}
		echo $body_top_style;
		?>
		text-align: left;
		color: <?=$body_text_color?>;
		font-size: <?=$body_text_size?>;
		font-family: <?=$body_text_font?>;
		}

	/* default body padding */
	.container-fluid {
		width: <?=$body_width?>;
		}

	/* maximize viewport usage on xs displays */
	@media(min-width: 0px) and (max-width: 767px) {
		.container-fluid {
			width: 100%;
			}

		#main_content {
			padding: 8px;
			}
		}

/* GENERAL ELEMENTS *****************************************************************/

	img {
		border: none;
		}

	.title, b {
		color: <?=$heading_text_color?>;
		font-size: <?=$heading_text_size?>;
		font-family: <?=$heading_text_font?>;
		font-weight: bold
		}

	a,
	button.btn.btn-link {
		color: <?=$text_link_color?>;
		text-decoration: none;
		}

	a:hover,
	button.btn.btn-link:hover {
		color: <?=$text_link_color_hover?>;
		text-decoration: none;
		}

	button.btn {
		margin-left: 2px;
		margin-right: 2px;
		}

	button.btn.btn-link {
		margin: 0;
		margin-top: -2px;
		padding: 0;
		border: none;
		font-size: inherit;
		font-family: inherit;
		}

	button.btn > span.fas.fa-spin {
		display: inline-block;
		}

	form {
		margin: 0;
		}

	form.inline {
		display: inline-block;
		}

	/* style placeholder text (for browsers that support the attribute) - note: can't stack, each must be seperate */
	::-webkit-input-placeholder { color: <?=$input_text_placeholder_color?> } /* chrome/opera/safari */
	::-moz-placeholder { color: <?=$input_text_placeholder_color?> } /* ff 19+ */
	:-moz-placeholder { color: <?=$input_text_placeholder_color?> } /* ff 18- */
	:-ms-input-placeholder { color: <?=$input_text_placeholder_color?> } /* ie 10+ */
	::placeholder { color: <?=$input_text_placeholder_color?> } /* official standard */

	select.txt,
	textarea.txt,
	input[type=text].txt,
	input[type=number].txt,
	input[type=password].txt,
	label.txt,
	select.formfld,
	textarea.formfld,
	input[type=text].formfld,
	input[type=number].formfld,
	input[type=url].formfld,
	input[type=password].formfld,
	label.formfld {
		font-family: <?=$input_text_font?>;
		font-size: <?=$input_text_size?>;
		color: <?=$input_text_color?>;
		text-align: left;
		height: 28px;
		padding: 4px 6px;
		margin: 1px;
		border-width: <?=$input_border_size?>;
		border-style: solid;
		border-color: <?=$input_border_color?>;
		background: <?=$input_background_color?>;
		<?php
		if (!empty($input_shadow_inner_color)) { $shadows[] = $input_shadow_inner_color; }
		if (!empty($input_shadow_outer_color)) { $shadows[] = $input_shadow_outer_color; }
		if (!empty($shadows)) {
			?>
			-webkit-box-shadow: <?php echo implode(',', $shadows); ?>;
			-moz-box-shadow:  <?php echo implode(',', $shadows); ?>;
			box-shadow:  <?php echo implode(',', $shadows); ?>;
			<?php
		}
		unset($shadows);
		?>
		<?php $br = format_border_radius($input_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		vertical-align: middle;
		}

	textarea.txt,
	input[type=text].txt,
	input[type=number].txt,
	input[type=password].txt,
	textarea.formfld,
	input[type=text].formfld,
	input[type=number].formfld,
	input[type=url].formfld,
	input[type=password].formfld {
		transition: width 0.25s;
		-moz-transition: width 0.25s;
		-webkit-transition: width 0.25s;
		}

	select.txt,
	select.formfld {
		padding: 4px 2px;
		}

	/* firefox only - adjust left padding */
	@-moz-document url-prefix() {
		select.txt,
		select.formfld {
			padding-left: 6px;
			}
		}

	textarea.txt:hover,
	input[type=text].txt:hover,
	input[type=number].txt:hover,
	input[type=password].txt:hover,
	label.txt:hover,
	textarea.formfld:hover,
	input[type=text].formfld:hover,
	input[type=number].formfld:hover,
	input[type=url].formfld:hover,
	input[type=password].formfld:hover,
	label.formfld:hover {
		border-color: <?=$input_border_color_hover?>;
		}

	textarea.txt:focus,
	input[type=text].txt:focus,
	input[type=number].txt:focus,
	input[type=password].txt:focus,
	label.txt:focus,
	textarea.formfld:focus,
	input[type=text].formfld:focus,
	input[type=number].formfld:focus,
	input[type=url].formfld:focus,
	input[type=password].formfld:focus,
	label.formfld:focus {
		border-color: <?=$input_border_color_focus?>;
		/* first clear */
			-webkit-box-shadow: none;
			-moz-box-shadow: none;
			box-shadow: none;
		/* then set */
			<?php
			if (!empty($input_shadow_inner_color_focus)) { $shadows[] = $input_shadow_inner_color_focus; }
			if (!empty($input_shadow_outer_color_focus)) { $shadows[] = $input_shadow_outer_color_focus; }
			if (!empty($shadows)) {
				?>
				-webkit-box-shadow: <?php echo implode(',', $shadows); ?>;
				-moz-box-shadow:  <?php echo implode(',', $shadows); ?>;
				box-shadow:  <?php echo implode(',', $shadows); ?>;
				<?php
			}
			unset($shadows);
			?>
		}

	textarea.txt,
	textarea.formfld {
		resize: both;
		}

	input.login {
		font-family: <?=$login_input_text_font?>;
		font-size: <?=$login_input_text_size?>;
		color: <?=$login_input_text_color?>;
		border-width: <?=$login_input_border_size?>;
		border-color: <?=$login_input_border_color?>;
		background: <?=$login_input_background_color?>;
		/* first clear */
			-webkit-box-shadow: none;
			-moz-box-shadow: none;
			box-shadow: none;
		/* then set */
			<?php
			if (!empty($login_input_shadow_inner_color)) { $shadows[] = $login_input_shadow_inner_color; }
			if (!empty($login_input_shadow_outer_color)) { $shadows[] = $login_input_shadow_outer_color; }
			if (!empty($shadows)) {
				?>
				-webkit-box-shadow: <?php echo implode(',', $shadows); ?>;
				-moz-box-shadow:  <?php echo implode(',', $shadows); ?>;
				box-shadow:  <?php echo implode(',', $shadows); ?>;
				<?php
			}
			unset($shadows);
			?>
		<?php $br = format_border_radius($login_input_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	input.login:hover {
		border-color: <?=$login_input_border_color_hover?>;
		}

	input.login:focus {
		border-color: <?=$login_input_border_color_hover_focus?>;
		/* first clear */
			-webkit-box-shadow: none;
			-moz-box-shadow: none;
			box-shadow: none;
		/* then set */
			<?php
			if (!empty($login_input_shadow_inner_color_focus)) { $shadows[] = $login_input_shadow_inner_color_focus; }
			if (!empty($login_input_shadow_outer_color_focus)) { $shadows[] = $login_input_shadow_outer_color_focus; }
			if (!empty($shadows)) {
				?>
				-webkit-box-shadow: <?php echo implode(',', $shadows); ?>;
				-moz-box-shadow:  <?php echo implode(',', $shadows); ?>;
				box-shadow:  <?php echo implode(',', $shadows); ?>;
				<?php
			}
			unset($shadows);
			?>
		}

	/* style placeholder text (for browsers that support the attribute) - note: can't stack, each must be seperate */
	input.login::-webkit-input-placeholder { color: <?=$login_input_text_placeholder_color?>; } /* chrome/opera/safari */
	input.login::-moz-placeholder { color: <?=$login_input_text_placeholder_color?>; } /* ff 19+ */
	input.login:-moz-placeholder { color: <?=$login_input_text_placeholder_color?>; } /* ff 18- */
	input.login:-ms-input-placeholder { color: <?=$login_input_text_placeholder_color?>; } /* ie 10+ */
	input.login::placeholder { color: <?=$login_input_text_placeholder_color?>; } /* official standard */

	input[type=password].formfld_highlight_bad,
	input[type=password].formfld_highlight_bad:hover,
	input[type=password].formfld_highlight_bad:active,
	input[type=password].formfld_highlight_bad:focus {
		border-color: #aa2525;
		-webkit-box-shadow: 0 0 3px #aa2525 inset;
		-moz-box-shadow: 0 0 3px #aa2525 inset;
		box-shadow: 0 0 3px #aa2525 inset;
		}

	input[type=password].formfld_highlight_good,
	input[type=password].formfld_highlight_good:hover,
	input[type=password].formfld_highlight_good:active,
	input[type=password].formfld_highlight_good:focus {
		border-color: #2fb22f;
		-webkit-box-shadow: 0 0 3px #2fb22f inset;
		-moz-box-shadow: 0 0 3px #2fb22f inset;
		box-shadow: 0 0 3px #2fb22f inset;
		}

	/* removes spinners (increment/decrement controls) inside input fields */
	input[type=number] { -moz-appearance: textfield; }
	::-webkit-inner-spin-button { -webkit-appearance: none; }
	::-webkit-outer-spin-button { -webkit-appearance: none; }

	/* disables text input clear 'x' in IE 10+, slows down autosizeInput jquery script */
	input[type=text]::-ms-clear {
		display: none;
	}

	/* expand list search input on focus */
	input[type=text].list-search {
		width: 70px;
		min-width: 70px;
		margin-left: 15px;
		-webkit-transition: all .5s ease;
		-moz-transition: all .5s ease;
		transition: all .5s ease;
		}

	input[type=text].list-search:focus {
		width: 150px;
		}

	input.fileinput {
		padding: 1px;
		display: inline;
		}

	input[type=checkbox] {
		margin-top: 2px;
		}

	label {
		font-weight: normal;
		vertical-align: middle;
		}

	label input[type=checkbox],
	label input[type=radio] {
		vertical-align: -2px;
		margin: 0;
		padding: 0;
		}

	span.playback_progress_bar {
		background-color: #b90004;
		width: 17px;
		height: 4px;
		margin-bottom: 3px;
		display: block;
		-moz-border-radius: 0 0 6px 6px;
		-webkit-border-radius: 0 0 6px 6px;
		-khtml-border-radius: 0 0 6px 6px;
		border-radius: 0 0 6px 6px;
		-webkit-box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		-moz-box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		}

	td.vtable.playback_progress_bar_background,
	table.list tr.list-row td.playback_progress_bar_background {
		padding: 0;
		border-bottom: none;
		background-image: -ms-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -moz-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -o-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, rgba(0,0,0,0.10)), color-stop(1, transparent));
		background-image: -webkit-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 100%);
		overflow: hidden;
		}

	div.pwstrength_progress {
		display: none;
		}

	div.pwstrength_progress > div.progress {
		max-width: 200px;
		height: 6px;
		margin: 1px 0 0 1px;
		background: <?=$pwstrength_background_color?>;
		<?php $br = format_border_radius($input_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	div.pwstrength_progress_password_reset > div.progress {
		margin: 0 auto 4px auto;
		width: 200px;
		max-width: 200px;
		background: <?=$login_input_background_color?>;
		border-width: <?=$login_input_border_size?>;
		border-color: <?=$login_input_border_color?>;
		}

/* TOGGLE SWITCH *******************************************************/

	.switch { /* container */
		position: relative;
		display: inline-block;
		width: 50px;
		<?php if ($input_toggle_style == 'switch_square') { ?>
			height: 28px;
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_round') { ?>
			height: 26px;
		<?php } ?>
		margin: 1px;
		<?php $br = format_border_radius($input_border_radius, '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	.switch > input {
		display: none;
		}

	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: <?=$input_toggle_switch_background_color_false?>;
		<?php if ($input_toggle_style == 'switch_square') { ?>
			<?php $br = format_border_radius($input_border_radius, '3px'); ?>
			-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			<?php unset($br); ?>
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_round') { ?>
			border-radius: 22px;
		<?php } ?>
		-webkit-transition: .2s;
		transition: .2s;
		}

	.slider:before { /* when disabled */
		position: absolute;
		<?php if ($input_toggle_switch_handle_symbol === 'true') { ?>
			text-align: center;
			<?php if ($input_toggle_style == 'switch_square') { ?>
				padding-top: 3px;
			<?php } else if ($input_toggle_style == 'switch_round') { ?>
				padding-top: 2px;
			<?php } ?>
			content: 'O';
			color: <?=$input_toggle_switch_background_color_false?>;
		<?php } else { ?>
			content: '';
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_square') { ?>
			height: 24px;
			width: 24px;
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_round') { ?>
			height: 22px;
			width: 22px;
		<?php } ?>
		top: 2px;
		left: 2px;
		bottom: 2px;
		background: <?=$input_toggle_switch_handle_color?>;
		<?php if ($input_toggle_style == 'switch_square') { ?>
			<?php $br = format_border_radius($input_border_radius, '3px'); ?>
			-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			<?php unset($br); ?>
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_round') { ?>
			border-radius: 50%;
		<?php } ?>
		-webkit-transition: .2s;
		transition: .2s;
		}

	input:checked + .slider { /* when enabled */
		background: <?=$input_toggle_switch_background_color_true?>;
		}

	input:focus + .slider { /* when focused, required for switch movement */
		}

	input:checked + .slider:before { /* distance switch moves horizontally */
		<?php if ($input_toggle_switch_handle_symbol === 'true') { ?>
			text-align: center;
			<?php if ($input_toggle_style == 'switch_square') { ?>
				padding-top: 2px;
			<?php } else if ($input_toggle_style == 'switch_round') { ?>
				padding-top: 1px;
			<?php } ?>
			content: '|';
			color: <?=$input_toggle_switch_background_color_true?>;
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_square') { ?>
			-webkit-transform: translateX(22px);
			-ms-transform: translateX(22px);
			transform: translateX(22px);
		<?php } ?>
		<?php if ($input_toggle_style == 'switch_round') { ?>
			-webkit-transform: translateX(24px);
			-ms-transform: translateX(24px);
			transform: translateX(24px);
		<?php } ?>
		}

/* TABLES *****************************************************************/

	table {
		border-collapse: separate;
		border-spacing: 0;
		}

	th {
		padding: 4px 7px 4px 0;
		padding: 4px 7px;
		text-align: left;
		color: <?=$table_heading_text_color?>;
		font-size: <?=$table_heading_text_size?>;
		font-family: <?=$table_heading_text_font?>;
		background: <?=$table_heading_background_color?>;
		border-bottom: 1px solid <?=$table_heading_border_color?>;
		}

	th a, th a:visited, th a:active {
		color: <?=$table_heading_text_color?>;
		text-decoration: none;
		}

	th a:hover {
		color: <?=$table_heading_text_color?>;
		text-decoration: none;
		}

	td {
		color: <?=$body_text_color?>;
		font-size: <?=$body_text_size?>;
		font-family: <?=$body_text_font?>;
		}

	table.tr_hover tr {
		cursor: default;
		}

	table.tr_hover tr:hover td,
	table.tr_hover tr:hover td a {
		color: <?=$text_link_color_hover?>;
		cursor: pointer;
		}

	table.tr_hover tr.tr_link_void:hover td {
		color: <?=$table_row_text_color?>;
		cursor: default;
		}

	table.tr_hover tr td.tr_link_void {
		cursor: default;
		}

	td.list_control_icons {
		width: 52px;
		padding: none;
		padding-left: 2px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	td.list_control_icon {
		width: 26px;
		padding: none;
		padding-left: 2px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	/* form: label/field format */
	.vncell { /* form_label */
		background: <?=$form_table_label_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?=$form_table_label_background_color?>;
		border-bottom: 1px solid <?=$form_table_label_border_color?>;
		padding: <?=$form_table_label_padding?>;
		text-align: right;
		color: <?=$form_table_label_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		vertical-align: top;
		}

	.vncellreq { /* form_label_required */
		background: <?=$form_table_label_required_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?=$form_table_label_required_border_color?>;
		border-bottom: 1px solid <?=$form_table_label_border_color?>;
		padding: <?=$form_table_label_padding?>;
		text-align: right;
		color: <?=$form_table_label_required_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		font-weight: <?=$form_table_label_required_text_weight?>;
		vertical-align: top;
		}

	.vtable { /* form_field */
		background: <?=$form_table_field_background_color?>;
		<?php $br = format_border_radius($form_table_field_border_radius, '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?=$form_table_field_border_color?>;
		padding: <?=$form_table_field_padding?>;
		text-align: left;
		vertical-align: middle;
		color: <?=$form_table_field_text_color?>;
		font-family: <?=$form_table_field_text_font?>;
		font-size: <?$form_table_field_text_size?>;
		}

	/* form: heading/row format */
	.vncellcol { /* form_heading */
		background: <?=$form_table_label_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 3px solid <?=$form_table_label_background_color?>;
		padding: <?=$form_table_heading_padding?>;
		text-align: left;
		color: <?=$form_table_label_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		}

	.vncellcolreq { /* form_heading_required */
		background: <?=$form_table_label_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 3px solid <?=$form_table_label_required_border_color?>;
		padding: <?=$form_table_heading_padding?>;
		text-align: left;
		color: <?=$form_table_label_required_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		font-weight: <?=$form_table_label_required_text_weight?>;
		}

	.vtablerow { /* form_row */
		<?php
		// determine cell height by padding
		$total_vertical_padding = 6; //default px
		if ($form_table_row_padding) {
			$form_table_row_padding = str_replace('px', '', $form_table_row_padding);
			$form_table_row_paddings = explode(' ', $form_table_row_padding);
			switch (sizeof($form_table_row_paddings)) {
				case 4: $total_vertical_padding = ($form_table_row_paddings[0] + $form_table_row_paddings[2]); break;
				default: $total_vertical_padding = ($form_table_row_paddings[0] * 2);
			}
		}
		?>
		height: <?php echo (30 + $total_vertical_padding); ?>px;
		background: <?=$form_table_field_background_color?>;
		<?php $br = format_border_radius($form_table_field_border_radius, '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?=$form_table_field_border_color?>;
		padding: <?=($form_table_row_padding ?? ($total_vertical_padding / 2).'px 0')?>;
		text-align: left;
		vertical-align: middle;
		color: <?=$form_table_field_text_color?>;
		font-family: <?=$form_table_field_text_font?>;
		font-size: <?=$form_table_field_text_size?>;
		}

	.vtablerow > label {
		margin-left: 0.6em;
		margin-right: 0.6em;
		margin-bottom: 2px;
		}

	.row_style0 {
		border-bottom: 1px solid <?=$table_row_border_color?>;
		background: <?=$table_row_background_color_dark?>;
		color: <?=$table_row_text_color?>;
		font-family: <?=$table_row_text_font?>;
		font-size: <?=$table_row_text_size?>;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style1 {
		border-bottom: 1px solid <?=$table_row_border_color?>;
		background: <?=$table_row_background_color_light?>;
		color: <?=$table_row_text_color?>;
		font-family: <?=$table_row_text_font?>;
		font-size: <?=$table_row_text_size?>;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style_slim {
		padding-top: 0;
		padding-bottom: 0;
		white-space: nowrap;
		}

	.row_stylebg {
		border-bottom: 1px solid <?=$table_row_border_color?>;
		background: <?=$table_row_background_color_medium?>;
		color: <?=$table_row_text_color?>;
		font-family: <?=$table_row_text_font?>;
		font-size: <?$table_row_text_size?>;
		text-align: left;
		padding: 4px 7px;
		}

/* RESPONSE MESSAGE STACK *******************************************************/

	#message_container {
		z-index: 99998;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		padding: 0;
		}

	.message_text {
		z-index: 99999;
		margin: 0 auto;
		padding: 15px;
		text-align: center;
		font-family: arial, san-serif;
		font-size: 10pt;
		display: block;
		color: <?=$message_default_color?>;
		background: <?-$message_default_background_color?>;
		box-shadow: inset 0px 7px 8px -10px <?=$message_default_color?>;
		border-bottom: solid 1px <?=$message_default_color?>;
		opacity: 0;
		}

	.message_mood_positive {
		color: <?=$message_positive_color?>;
		background: <?=$message_positive_background_color?>;
		box-shadow: inset 0px 7px 8px -10px <?=$message_positive_color?>;
		border-bottom: solid 1px <?=$message_positive_color?>;
		}

	.message_mood_negative {
		color: <?=$message_negative_color?>;
		background: <?=$message_negative_background_color?>;
		box-shadow: inset 0px 7px 8px -10px <?=$message_negative_color?>;
		border-bottom: solid 1px <?=$message_negative_color?>;
		}

	.message_mood_alert {
		color: <?=$message_alert_color?>;
		background: <?=$message_alert_background_color?>;
		box-shadow: inset 0px 7px 8px -10px <?=$message_alert_color?>;
		border-bottom: solid 1px <?=$message_alert_color?>;
		}

/* OPERATOR PANEL ****************************************************************/

	div.op_ext {
		float: left;
		width: 235px;
		margin: 0px 8px 8px 0px;
		padding: 0px;
		border-style: solid;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		-moz-box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		border-width: 1px 3px;
		border-color: <?=$operator_panel_border_color?>;
		background-color: <?=$form_table_label_background_color?>;
		cursor: default;
		}

	div.off_ext {
		position: relative;
		float: left;
		width: 235px;
		margin: 0px 8px 8px 0px;
		padding: 0px;
		border-style: solid;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		-moz-box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		box-shadow: 0 0 3px <?=$form_table_field_background_color?>;
		border-width: 1px 3px;
		border-color: <?=$operator_panel_border_color?>;
		background-color: <?=$form_table_label_background_color?>;
		cursor: not-allowed;
		opacity: 0.5;
		}
		
		div.off_ext:after {
			position: absolute;
			content: "";
			z-index: 10;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px;
			border-radius: 5px;
			display: block;
			height: 100%;
			top: 0;
			left: 0;
			right: 0;
			background: <?=$form_table_field_background_color?>;
			opacity: 0.5;
		}

	div.op_state_active {
		background-color: #baf4bb;
		border-width: 1px 3px;
		border-color: #77d779;
		}

	div.op_state_ringing {
		background-color: #a8dbf0;
		border-width: 1px 3px;
		border-color: #41b9eb;
		}

	div.op_valet_park_active {
		border-width: 1px 3px;
		background-color: #B9A6FC;
		border-color: #B9A6FC;
		}

	table.op_ext, table.off_ext {
		width: 100%;
		height: 70px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: <?=$operator_panel_sub_background_color?>;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	td.op_ext_icon {
		vertical-align: middle;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	img.op_ext_icon {
		cursor: move;
		width: 39px;
		height: 42px;
		border: none;
		}

	td.op_ext_info {
		text-align: left;
		vertical-align: top;
		font-family: arial;
		font-size: 10px;
		overflow: auto;
		width: 100%;
		padding: 3px 5px 3px 7px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: <?=$operator_panel_main_background_color?>;
		}

	td.op_state_ringing {
		background-color: #d1f1ff;
		}

	td.op_state_active {
		background-color: #e1ffe2;
		}

	td.op_valet_park_active {
		background-color: #ECE3FF;
		}

	table.op_valet_park_active {
		background-color: #B9A6FC;
		}

	table.op_state_ringing {
		background-color: #a8dbf0;
		}

	table.op_state_active {
		background-color: #baf4bb;
		}

	.op_user_info {
		font-family: arial;
		font-size: 10px;
		display: inline-block;
		}

	.op_user_info strong {
		color: #3164AD;
		}

	.op_caller_info {
		display: block;
		margin-top: 4px;
		font-family: arial;
		font-size: 10px;
		}

	.op_call_info {
		display: inline-block;
		padding: 0px;
		font-family: arial;
		font-size: 10px;
		}

	#op_btn_status_available {
		background-image: -moz-linear-gradient(top, #8ec989 0%, #2d9c38 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #8ec989), color-stop(1, #2d9c38));
		background-color: #2d9c38;
		border: 1px solid #006200;
		}

	#op_btn_status_available_on_demand {
		background-image: -moz-linear-gradient(top, #abd0aa 0%, #629d62 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #abd0aa), color-stop(1, #629d62));
		background-color: #629d62;
		border: 1px solid #619c61;
		}

	#op_btn_status_on_break {
		background-image: -moz-linear-gradient(top, #ddc38b 0%, #be8e2c 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #ddc38b), color-stop(1, #be8e2c));
		background-color: #be8e2c;
		border: 1px solid #7d1b00;
		}

	#op_btn_status_do_not_disturb {
		background-image: -moz-linear-gradient(top, #cc8984 0%, #960d10 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #cc8984), color-stop(1, #960d10));
		background-color: #960d10;
		border: 1px solid #5b0000;
		}

	#op_btn_status_logged_out {
		background-image: -moz-linear-gradient(top, #cacac9 0%, #8d8d8b 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #cacac9), color-stop(1, #8d8d8b));
		background-color: #8d8d8b;
		border: 1px solid #5d5f5a;
		}

/* DASHBOARD **********************************************************************/

	/* login message */
	div.login_message {
		border: 1px solid #bae0ba;
		background-color: #eeffee;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
		padding: 20px;
		margin-bottom: 15px;
		}

	/* hud boxes */
	div.hud_box {
		height: auto;
		vertical-align: top;
		text-align: center;
		<?php
		$color_edge = $dashboard_detail_background_color_edge;
		$color_center = $dashboard_detail_background_color_center;
		?>
		background: <?=$color_center?>;
		background-image: -ms-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -moz-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -o-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -webkit-gradient(linear, left, right, color-stop(0, <?=$color_edge?>), color-stop(0.30, <?=$color_center?>), color-stop(0.70, <?=$color_center?>), color-stop(1, <?=$color_edge?>));
		background-image: -webkit-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: linear-gradient(to right, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		<?php unset($color_edge, $color_center); ?>
		<?php $br = format_border_radius($dashboard_border_radius, '5px'); ?>
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border: 1px solid <?=$dashboard_border_color?>;
		overflow: hidden;
		margin: -1px;
		}

	div.hud_box:hover {
		border: 1px solid <?=$dashboard_border_color_hover?>;
		}

	span.hud_title {
		display: block;
		width: 100%;
		font-family: <?=$dashboard_heading_text_font?>;
		text-shadow: 0px 1px 2px <?=$dashboard_heading_text_shadow_color?>;
		letter-spacing: -0.02em;
		color: <?=$dashboard_heading_text_color?>;
		font-size: <?=$dashboard_heading_text_size?>;
		<?php
		//calculate line height based on font size
		$font_size = strtolower($dashboard_heading_text_size);
		$tmp = str_replace(' ', '', $font_size);
		$tmp = str_replace('pt', '', $tmp);
		$tmp = str_replace('px', '', $tmp);
		$tmp = str_replace('em', '', $tmp);
		$tmp = str_replace('%', '', $tmp);
		$font_size_number = $tmp;
		$line_height_number = (int) floor($font_size_number * 2.5);
		?>
		line-height: <?php echo ($line_height_number > 0) ? str_replace($font_size_number, $line_height_number, $font_size) : '26.25pt'; ?>;
		text-align: center;
		background: <?=$dashboard_heading_background_color?>;
		border-bottom: 1px solid <?php echo color_adjust($dashboard_heading_background_color, 0.2); ?>;
		overflow: hidden;
		}

	span.hud_title:hover {
		color: <?=$dashboard_heading_text_color_hover?>;
		text-shadow: 0px 1px 2px <?=$dashboard_heading_text_shadow_color?>;
		background: <?=$dashboard_heading_background_color_hover?>;
		cursor: pointer;
		}

	span.hud_stat {
		display: block;
		clear: both;
		text-align: center;
		text-shadow: 0px 2px 2px <?=$dashboard_number_text_shadow_color?>;
		width: 100%;
		color: <?=$dashboard_number_text_color?>;
		font-family: <?=$dashboard_number_text_font?>;
		font-size: <?=$dashboard_number_text_size?>;
		<?php
		//calculate line height based on font size
		$font_size = strtolower($dashboard_number_text_size);
		$tmp = str_replace(' ', '', $font_size);
		$tmp = str_replace('pt', '', $tmp);
		$tmp = str_replace('px', '', $tmp);
		$tmp = str_replace('em', '', $tmp);
		$tmp = str_replace('%', '', $tmp);
		$font_size_number = $tmp;
		$line_height_number = (int) floor($font_size_number * 1.28);
		?>
		line-height: <?php echo ($line_height_number > 0) ? str_replace($font_size_number, $line_height_number, $font_size) : '77pt'; ?>;
		font-weight: normal;
		background: <?=$dashboard_number_background_color?>;
		border-top: 1px solid <?php echo color_adjust($dashboard_number_background_color, 0.2); ?>;
		overflow: hidden;
		<?php
		//calculate font padding
		$font_size = strtolower($dashboard_heading_text_size);
		$tmp = str_replace(' ', '', $font_size);
		$tmp = str_replace('pt', '', $tmp);
		$tmp = str_replace('px', '', $tmp);
		$tmp = str_replace('em', '', $tmp);
		$tmp = str_replace('%', '', $tmp);
		$font_size_number = $tmp;
		$padding_top_bottom = (int) floor((100-$tmp) * 0.25);
		?>
		padding-top: <?php echo $padding_top_bottom.'px' ?>;
		padding-bottom: <?php echo $padding_top_bottom.'px' ?>;
		}

	span.hud_stat:hover {
		color: <?=$dashboard_number_text_color_hover?>;
		text-shadow: 0px 2px 2px <?=$dashboard_number_text_shadow_color_hover?>;
		background: <?=$dashboard_number_background_color_hover?>;
		cursor: pointer;
		}

	span.hud_stat_title {
		display: block;
		clear: both;
		width: 100%;
		height: 30px;
		cursor: default;
		text-align: center;
		text-shadow: 0px 1px 1px <?=$dashboard_number_title_text_shadow_color?>;
		color: <?=$dashboard_number_title_text_color?>;
		font-size: <?=$dashboard_number_title_text_size?>;
		padding-top: 4px;
		white-space: nowrap;
		letter-spacing: -0.02em;
		font-weight: normal;
		font-family: <?=$dashboard_number_title_text_font?>;
		background: <?=$dashboard_number_background_color?>;
		border-bottom: 1px solid <?php echo color_adjust($dashboard_number_background_color, -0.2); ?>;
		margin: 0;
		overflow: hidden;
		}

	span.hud_stat:hover + span.hud_stat_title {
		color: <?=$dashboard_number_text_color_hover?>;
		text-shadow: 0px 1px 1px <?=$dashboard_number_text_shadow_color_hover?>;
		background: <?=$dashboard_number_background_color_hover?>;
		}

	div.hud_details {
		/*
		-moz-box-shadow: inset 0 7px 7px -7px <?=$dashboard_detail_shadow_color?>, inset 0 -8px 12px -10px <?=$dashboard_detail_shadow_color?>;
		-webkit-box-shadow: inset 0 7px 7px -7px <?=$dashboard_detail_shadow_color?>, inset 0 -8px 12px -10px <?=$dashboard_detail_shadow_color?>;
		box-shadow: inset 0 7px 7px -7px <?=$dashboard_detail_shadow_color?>, inset 0 -8px 12px -10px <?=$dashboard_detail_shadow_color?>;
		*/
		padding-bottom: 15px;
		overflow-y: auto;
		}

	@media(min-width: 0px) and (max-width: 1199px) {
		div.hud_details {
			display: none;
			height: auto;
			}
		}

	@media(min-width: 1200px) {
		div.hud_details {
			height: 350px;
			display: block;
			}
		}

	th.hud_heading {
		text-align: left;
		font-size: <?=$dashboard_detail_heading_text_size?>;
		font-family: <?=$table_heading_text_font?>
		color: <?=$table_heading_text_color?>;
		padding-top: 3px;
		<?php
		$color_edge = $dashboard_detail_background_color_edge;
		$color_center = $dashboard_detail_background_color_center;
		?>
		background: <?=$color_center?>;
		background-image: -ms-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -moz-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -o-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: -webkit-gradient(linear, left, right, color-stop(0, <?=$color_edge?>), color-stop(0.30, <?=$color_center?>), color-stop(0.70, <?=$color_center?>), color-stop(1, <?=$color_edge?>));
		background-image: -webkit-linear-gradient(left, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		background-image: linear-gradient(to right, <?=$color_edge?> 0%, <?=$color_center?> 30%, <?=$color_center?> 70%, <?=$color_edge?> 100%);
		<?php unset($color_edge, $color_center); ?>
		}

	th.hud_heading:first-of-type {
		<?php $br = format_border_radius($dashboard_border_radius, '5px'); ?>
		-webkit-border-top-left-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?>;
		-moz-border-top-left-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?>;
		border-top-left-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?>;
		<?php unset($br); ?>
		}

	th.hud_heading:first-of-type {
		<?php $br = format_border_radius($dashboard_border_radius, '5px'); ?>
		-webkit-border-top-left-radius: <?php echo $br['tr']['n'].$br['tr']['u']; ?>;
		-moz-border-top-left-radius: <?php echo $br['tr']['n'].$br['tr']['u']; ?>;
		border-top-left-radius: <?php echo $br['tr']['n'].$br['tr']['u']; ?>;
		<?php unset($br); ?>
		}

	td.hud_text {
		font-size: <?=$dashboard_detail_row_text_size?>;
		color: <?=$table_row_text_color?>;
		text-align: left;
		vertical-align: middle;
		}

	td.hud_text.input {
		margin: 0;
		padding-top: 0;
		padding-bottom: 0;
		white-space: nowrap;
		}

	span.hud_expander {
		display: block;
		clear: both;
		background: <?=$dashboard_footer_background_color?>;
		padding: 4px 0;
		text-align: center;
		width: 100%;
		height: 25px;
		font-size: 13px;
		line-height: 5px;
		color: <?=$dashboard_footer_dots_color?>;
		border-top: 1px solid <?php echo color_adjust($dashboard_footer_background_color, 0.2); ?>;
		}

	span.hud_expander:hover {
		color: <?=$dashboard_footer_dots_color_hover?>;
		background: <?=$dashboard_footer_background_color_hover?>;
		cursor: pointer;
		}

/* PLUGINS ********************************************************************/

	/* bootstrap colorpicker  */
	.colorpicker-2x .colorpicker-saturation {
		width: 200px;
		height: 200px;
		}

	.colorpicker-2x .colorpicker-hue,
	.colorpicker-2x .colorpicker-alpha {
		width: 30px;
		height: 200px;
		}

	.colorpicker-2x .colorpicker-color,
	.colorpicker-2x .colorpicker-color div{
		height: 30px;
		}

	/* jquery ui autocomplete styles */
	.ui-widget {
		margin: 0px;
		padding: 0px;
		}

	.ui-autocomplete {
		cursor: default;
		position: absolute;
		max-height: 200px;
		overflow-y: auto;
		overflow-x: hidden;
		white-space: nowrap;
		width: auto;
		border: 1px solid #c0c0c0;
		}

	.ui-menu, .ui-menu .ui-menu-item {
		width: 350px;
		}

	.ui-menu .ui-menu-item a {
		text-decoration: none;
		cursor: pointer;
		border-color: #fff;
		background-image: none;
		background-color: #fff;
		white-space: nowrap;
		font-family: arial;
		font-size: 12px;
		color: #444;
		}

	.ui-menu .ui-menu-item a:hover {
		color: #5082ca;
		border: 1px solid white;
		background-image: none;
		background-color: #fff;
		}

/* CSS GRID ********************************************************************/

	div.grid {
		width: 100%;
		display: grid;
		grid-gap: 0;
		}

	div.grid > div.box.contact-details {
		padding: 15px;
		border: 1px solid <?=$table_row_border_color?>;
		border-radius: 5px;
		background: <?=$table_row_background_color_dark?>;
		}

	div.grid.contact-details {
		grid-template-columns: 50px auto;
		}

	div.grid > div.box {
		padding: 0;
		padding-bottom: 5px;
		}

	div.grid > div.box.contact-details-label {
		font-size: 87%;
		letter-spacing: -0.03em;
		vertical-align: middle;
		white-space: nowrap;
		}

	div.form_grid {
		width: 100%;
		display: grid;
		grid-gap: 0;
		grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
		}

	div.form_set {
		width: 100%;
		display: grid;
		grid_gap: 0;
		grid-template-columns: 150px minmax(200px, 1fr);
		}

	div.form_set > .label {
		background: <?=$form_table_label_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?=$form_table_label_background_color?>;
		border-bottom: 1px solid <?=$form_table_label_border_color?>;
		padding: <?=$form_table_label_padding?>;
		text-align: right;
		color: <?=$form_table_label_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		white-space: nowrap;
		vertical-align: top;
		}

	div.form_set > .label.required {
		background: <?=$form_table_label_required_background_color?>;
		<?php $br = format_border_radius($form_table_label_border_radius, '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?=$form_table_label_required_border_color?>;
		border-bottom: 1px solid <?=$form_table_label_border_color?>;
		padding: <?=$form_table_label_padding?>;
		text-align: right;
		color: <?=$form_table_label_required_text_color?>;
		font-family: <?=$form_table_label_text_font?>;
		font-size: <?=$form_table_label_text_size?>;
		font-weight: <?=$form_table_label_required_text_weight?>;
		white-space: nowrap;
		vertical-align: top;
		}

	div.form_set > .field {
		background: <?=$form_table_field_background_color?>;
		<?php $br = format_border_radius($form_table_field_border_radius, '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?=$form_table_field_border_color?>;
		padding: <?=$form_table_field_padding?>;
		text-align: left;
		vertical-align: middle;
		color: <?=$form_table_field_text_color?>;
		font-family: <?=$form_table_field_text_font?>;
		font-size: <?=$form_table_field_text_size?>;
		position: relative;
		}

	div.form_set > .field.no-wrap {
		white-space: nowrap;
		}

/* LIST ACTION BAR *************************************************************/

	div.action_bar {
		position: -webkit-sticky;
		position: sticky;
		z-index: 5;
		<?php
		switch ($menu_style) {
			case 'side':
				$action_bar_top = '0';
				break;
			case 'inline':
			case 'static':
				$action_bar_top = '-1px';
				break;
			case 'fixed':
			default:
				$action_bar_top = '49px';
		}
		?>
		top: <?php echo $action_bar_top; ?>;
		text-align: right;
		border-top: <?=$action_bar_border_top?>;
		border-right: <?=$action_bar_border_right?>;
		border-bottom: <?=$action_bar_border_bottom?>;
		border-left: <?=$action_bar_border_left?>;
		border-radius: <?=$action_bar_border_radius?>;
		background: <?=$action_bar_background?>;
		box-shadow: <?=$action_bar_shadow?>;
		padding: 10px;
		margin: -10px -10px 10px -10px;
		-webkit-transition: all .2s ease;
		-moz-transition: all .2s ease;
		transition: all .2s ease;
		}

	div.action_bar.scroll {
		border-top: <?=$action_bar_border_top_scroll?>;
		border-right: <?=$action_bar_border_right_scroll?>;
		border-bottom: <?=$action_bar_border_bottom_scroll?>;
		border-left: <?=$action_bar_border_left_scroll?>;
		border-radius: <?=$action_bar_border_radius_scroll?>;
		background: <?=$action_bar_background_scroll?>;
		box-shadow: <?=$action_bar_shadow_scroll?>;
		}

	div.action_bar.sub {
		position: static;
		}

	div.action_bar > div.heading {
		float: left;
		}

	div.action_bar > div.actions {
		float: right;
		white-space: nowrap;
		}

	div.action_bar > div.actions > div.unsaved {
		display: inline-block;
		margin-right: 30px;
		color: #b00;
		}

	/* used primarily in contacts */
	div.action_bar.shrink {
		margin-bottom: 0;
		padding-bottom: 0;
		}

	div.action_bar.shrink > div.heading > b {
		font-size: 100%;
		}

	.warning_bar {
		width: 100%;
		text-align: center;
		border: 2px dashed #c00;
		padding: 10px 20px;
		margin-bottom: 16px;
		color: #e00;
		background: #fafafa;
		font-size: 1.4em;
	}

/* LIST ************************************************************************/

	.list {
		width: 100%;
		empty-cells: show;
		}

	.list tr {
		cursor: default;
		}

	.list tr:hover td:not(.no-link),
	.list tr:hover td:not(.no-link) a {
		color: <?=$text_link_color_hover?>;
		cursor: pointer;
		}

	.list-header > th {
		padding: <?=$table_heading_padding?>;
		text-align: left;
		color: <?=$table_heading_text_color?>;
		font-size: <?=$table_heading_text_size?>;
		font-family: <?=$table_heading_text_font?>;
		background: <?=$table_heading_background_color?>;
		border-bottom: 1px solid <?=$table_heading_border_color?>;
		}

	.list-header > th.shrink {
		width: 1%;
		}

	.list-header > th > a.default-color {
		color: <?=$text_link_color?>;
		}

	.list-header > th > a.default-color:hover {
		color: <?=$text_link_color_hover?>;
		}

	.list-row:nth-child(odd) > :not(.action-button) {
		background: <?=$table_row_background_color_light?>;
		}

	.list-row:nth-child(even) > :not(.action-button) {
		background: <?=$table_row_background_color_dark?>;
		}

	.list-row > td:not(.action-button) {
		border-bottom: 1px solid <?=$table_row_border_color?>;
		color: <?=$table_row_text_color?>;
		font-family: <?=$table_row_text_font?>;
		font-size: <?=$table_row_text_size?>;
		text-align: left;
		vertical-align: middle;
		}

	.list-row > :not(.checkbox) {
		padding: <?=$table_row_padding?>;
		}

	.list-row > td.description {
		background: <?=$table_row_background_color_medium?> !important;
		}

	.list-header > .checkbox,
	.list-row > .checkbox {
		width: 1%;
		text-align: center !important;
		cursor: default !important;
		}

	.list-row > .checkbox {
		padding: 3px 7px 1px 7px;
		}

	.list-row > .button {
		margin: 0;
		padding-top: 1px;
		padding-bottom: 1px;
		white-space: nowrap;
		}

	.list-row > .input {
		margin: 0;
		padding-top: 0;
		padding-bottom: 0;
		white-space: nowrap;
		}

	.list-row > .overflow {
		max-width: 50px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		}

	.list-header > .action-button,
	.list-row > .action-button {
		width: 1px;
		white-space: nowrap;
		background: none;
		padding: 0;
		}

	.list-header > .center,
	.list-row > .center {
		text-align: center !important;
		}

	.list-header > .right,
	.list-row > .right {
		text-align: right !important;
		}

	.list-header > .middle,
	.list-row > .middle {
		vertical-align: middle !important;
		}

	.list-header > .no-wrap,
	.list-row > .no-wrap {
		white-space: nowrap;
		}

/* EDIT ********************************************************************************/

	td.edit_delete_checkbox_all {
		text-align: center;
		width: 50px;
		}

	td.edit_delete_checkbox_all input[type=checkbox] {
		vertical-align: middle;
		margin-top: -2px;
		}

	td.edit_delete_checkbox_all > span:nth-child(2) {
		display: none;
		}

/* CURSORS ***********************************************************************/

	.cursor-default { cursor: default; }
	.cursor-help { cursor: help; }
	.cursor-pointer { cursor: pointer; }
	.cursor-denied { cursor: not-allowed; }

/* WIDTH HELPERS **********************************************************************/

	.pct-5 { width: 5%; }
	.pct-10 { width: 10%; }
	.pct-15 { width: 15%; }
	.pct-20 { width: 20%; }
	.pct-25 { width: 25%; }
	.pct-30 { width: 30%; }
	.pct-35 { width: 35%; }
	.pct-40 { width: 40%; }
	.pct-45 { width: 45%; }
	.pct-50 { width: 50%; }
	.pct-55 { width: 55%; }
	.pct-60 { width: 60%; }
	.pct-65 { width: 65%; }
	.pct-70 { width: 70%; }
	.pct-75 { width: 75%; }
	.pct-80 { width: 80%; }
	.pct-85 { width: 85%; }
	.pct-90 { width: 90%; }
	.pct-95 { width: 95%; }
	.pct-100 { width: 100%; }

/* SIDE PADDING & MARGIN HELPERS **********************************************************************/

	.pl-1 { padding-left: 1px !important; }		.pr-1 { padding-right: 1px !important; }
	.pl-2 { padding-left: 2px !important; }		.pr-2 { padding-right: 2px !important; }
	.pl-3 { padding-left: 3px !important; }		.pr-3 { padding-right: 3px !important; }
	.pl-4 { padding-left: 4px !important; }		.pr-4 { padding-right: 4px !important; }
	.pl-5 { padding-left: 5px !important; }		.pr-5 { padding-right: 5px !important; }
	.pl-6 { padding-left: 6px !important; }		.pr-6 { padding-right: 6px !important; }
	.pl-7 { padding-left: 7px !important; }		.pr-7 { padding-right: 7px !important; }
	.pl-8 { padding-left: 8px !important; }		.pr-8 { padding-right: 8px !important; }
	.pl-9 { padding-left: 9px !important; }		.pr-9 { padding-right: 9px !important; }
	.pl-10 { padding-left: 10px !important; }	.pr-10 { padding-right: 10px !important; }
	.pl-11 { padding-left: 11px !important; }	.pr-11 { padding-right: 11px !important; }
	.pl-12 { padding-left: 12px !important; }	.pr-12 { padding-right: 12px !important; }
	.pl-13 { padding-left: 13px !important; }	.pr-13 { padding-right: 13px !important; }
	.pl-14 { padding-left: 14px !important; }	.pr-14 { padding-right: 14px !important; }
	.pl-15 { padding-left: 15px !important; }	.pr-15 { padding-right: 15px !important; }
	.pl-20 { padding-left: 20px !important; }	.pr-20 { padding-right: 20px !important; }
	.pl-25 { padding-left: 25px !important; }	.pr-25 { padding-right: 25px !important; }
	.pl-30 { padding-left: 30px !important; }	.pr-30 { padding-right: 30px !important; }
	.pl-35 { padding-left: 35px !important; }	.pr-35 { padding-right: 35px !important; }
	.pl-40 { padding-left: 40px !important; }	.pr-40 { padding-right: 40px !important; }
	.pl-45 { padding-left: 45px !important; }	.pr-45 { padding-right: 45px !important; }
	.pl-50 { padding-left: 50px !important; }	.pr-50 { padding-right: 50px !important; }

	.ml-1 { margin-left: 1px !important; }		.mr-1 { margin-right: 1px !important; }
	.ml-2 { margin-left: 2px !important; }		.mr-2 { margin-right: 2px !important; }
	.ml-3 { margin-left: 3px !important; }		.mr-3 { margin-right: 3px !important; }
	.ml-4 { margin-left: 4px !important; }		.mr-4 { margin-right: 4px !important; }
	.ml-5 { margin-left: 5px !important; }		.mr-5 { margin-right: 5px !important; }
	.ml-6 { margin-left: 6px !important; }		.mr-6 { margin-right: 6px !important; }
	.ml-7 { margin-left: 7px !important; }		.mr-7 { margin-right: 7px !important; }
	.ml-8 { margin-left: 8px !important; }		.mr-8 { margin-right: 8px !important; }
	.ml-9 { margin-left: 9px !important; }		.mr-9 { margin-right: 9px !important; }
	.ml-10 { margin-left: 10px !important; }	.mr-10 { margin-right: 10px !important; }
	.ml-11 { margin-left: 11px !important; }	.mr-11 { margin-right: 11px !important; }
	.ml-12 { margin-left: 12px !important; }	.mr-12 { margin-right: 12px !important; }
	.ml-13 { margin-left: 13px !important; }	.mr-13 { margin-right: 13px !important; }
	.ml-14 { margin-left: 14px !important; }	.mr-14 { margin-right: 14px !important; }
	.ml-15 { margin-left: 15px !important; }	.mr-15 { margin-right: 15px !important; }
	.ml-20 { margin-left: 20px !important; }	.mr-20 { margin-right: 20px !important; }
	.ml-25 { margin-left: 25px !important; }	.mr-25 { margin-right: 25px !important; }
	.ml-30 { margin-left: 30px !important; }	.mr-30 { margin-right: 30px !important; }
	.ml-35 { margin-left: 35px !important; }	.mr-35 { margin-right: 35px !important; }
	.ml-40 { margin-left: 40px !important; }	.mr-40 { margin-right: 40px !important; }
	.ml-45 { margin-left: 45px !important; }	.mr-45 { margin-right: 45px !important; }
	.ml-50 { margin-left: 50px !important; }	.mr-50 { margin-right: 50px !important; }

/* MODAL ************************************************************************/

	.modal-window {
		z-index: 999999;
		position: fixed;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		opacity: 0;
		pointer-events: none;
		-webkit-transition: all <?=$modal_transition_seconds?>s;
		-moz-transition: all <?=$modal_transition_seconds?>s;
		transition: all <?=$modal_transition_seconds?>s;
		background: <?=$modal_shade_color?>;
		}

	.modal-window > div {
		position: relative;
		padding: <?=$modal_padding?>;
		background: <?=$modal_background_color?>;
		overflow: auto;
		}

	@media(min-width: 0px) and (max-width: 699px) {
		.modal-window > div {
			width: 100%;
			min-width: 200px;
			margin: 50px auto;
			border-radius: 0;
			}
		}

	@media(min-width: 700px) {
		.modal-window > div {
			width: <?=$modal_width?>;
			margin: 10% auto;
			border-radius: <?=$modal_corner_radius?>;
			box-shadow: <?=$modal_shadow?>;
			}
		}

	.modal-window .modal-title {
		display: block;
		font-weight: bold;
		font-size: 120%;
		font-family: <?=$modal_title_font?>;
		color: <?=$modal_title_color?>;
		text-align: <?=$modal_title_alignment?>;
		margin: <?=$modal_title_margin?>;
		}

	.modal-close {
		color: <?=$modal_close_color?>;
		line-height: 50px;
		font-size: 150%;
		position: absolute;
		top: 0;
		right: 0;
		width: 50px;
		text-align: center;
		text-decoration: none !important;
		cursor: pointer;
		border-radius: <?=$modal_close_corner_radius?>;
		background: <?=$modal_close_background_color?>;
		}

	.modal-close:hover {
		color: <?=$modal_close_color_hover?>;
		background: <?=$modal_close_background_color_hover?>;
		}

	.modal-window .modal-message {
		display: block;
		color: <?=$modal_message_color?>;
		text-align: <?=$modal_message_alignment?>;
		margin: <?=$modal_message_margin?>;
		}

	.modal-actions {
		display: block;
		text-align: left;
		}

/* ACE EDITOR *******************************************************************/

	div#editor {
		resize: vertical;
		overflow: auto;
		}

<?php

//output custom css
echo $custom_css_code;

?>
