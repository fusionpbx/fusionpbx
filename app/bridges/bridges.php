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
      Portions created by the Initial Developer are Copyright (C) 2018 - 2019
      the Initial Developer. All Rights Reserved.

      Contributor(s):
      Mark J Crane <markjcrane@fusionpbx.com>
     */

//includes
    require_once "root.php";
    require_once "resources/require.php";
    require_once "resources/check_auth.php";
    require_once "resources/paging.php";

//check permissions
    if (permission_exists('bridge_view')) {
        //access granted
    } else {
        echo "access denied";
        exit;
    }

//add multi-lingual support
    $language = new text;
    $text = $language->get();

//get the http post data
    if (is_array($_POST['bridges'])) {
        $action = $_POST['action'];
        $search = $_POST['search'];
        $bridges = $_POST['bridges'];
    }

//process the http post data by action
    if ($action != '' && is_array($bridges) && @sizeof($bridges) != 0) {
        switch ($action) {
            case 'copy':
                if (permission_exists('bridge_add')) {
                    $obj = new bridges;
                    $obj->copy($bridges);
                }
                break;
            case 'toggle':
                if (permission_exists('bridge_edit')) {
                    $obj = new bridges;
                    $obj->toggle($bridges);
                }
                break;
            case 'delete':
                if (permission_exists('bridge_delete')) {
                    $obj = new bridges;
                    $obj->delete($bridges);
                }
                break;
        }

        header('Location: bridges.php' . ($search != '' ? '?search=' . urlencode($search) : null));
        exit;
    }

//get order and order by
    $order_by = $_GET["order_by"];
    $order = $_GET["order"];

//add the search string
    if (isset($_GET["search"])) {
        $search = strtolower($_GET["search"]);
        $sql_search = " (";
        $sql_search .= "	lower(bridge_name) like :search ";
        $sql_search .= "	or lower(bridge_destination) like :search ";
        $sql_search .= "	or lower(bridge_enabled) like :search ";
        $sql_search .= "	or lower(bridge_description) like :search ";
        $sql_search .= ") ";
        $parameters['search'] = '%' . $search . '%';
    }

//get the count
    $sql = "select count(bridge_uuid) from v_bridges ";
    if ($_GET['show'] == "all" && permission_exists('bridge_all')) {
        if (isset($sql_search)) {
            $sql .= "where " . $sql_search;
        }
    } else {
        $sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
        if (isset($sql_search)) {
            $sql .= "and " . $sql_search;
        }
        $parameters['domain_uuid'] = $domain_uuid;
    }
    $database = new database;
    $num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
    $rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
    $param = $search ? "&search=" . $search : null;
    $param = ($_GET['show'] == 'all' && permission_exists('bridge_all')) ? "&show=all" : null;
    $page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
    list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
    list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
    $offset = $rows_per_page * $page;

//get the list
    $sql = str_replace('count(bridge_uuid)', '*', $sql);
    $sql .= order_by($order_by, $order, 'bridge_name', 'asc');
    $sql .= limit_offset($rows_per_page, $offset);
    $database = new database;
    $bridges = $database->select($sql, $parameters, 'all');
    unset($sql, $parameters);

//create token
    $object = new token;
    $token = $object->create($_SERVER['PHP_SELF']);

//create the template object to use for rendering
    $smarty = new bridge_template();

// set table headers using full html codes so they are clickable and provide sorting
// with some more effort, you could move this entirely to the template
    if ($_GET['show'] == 'all' && permission_exists('bridge_all')) {
        $header_names[] = th_order_by('domain_name', $text['label-domain'], $order_by, $order);
    }
    $header_names[] = th_order_by('bridge_name', $text['label-bridge_name'], $order_by, $order);
    $header_names[] = th_order_by('bridge_destination', $text['label-bridge_destination'], $order_by, $order);
    $header_names[] = th_order_by('bridge_enabled', $text['label-bridge_enabled'], $order_by, $order, null, "class='center'");
    $header_names[] = "<th class='hide-sm-dn'>" . $text['label-bridge_description'] . "</th>";
    
    $smarty->assign('headers', $header_names);
    
//title
    $smarty->assign('heading', $text['title-bridges']);

//add button
    if (permission_exists('bridge_add')) {
        $smarty->assign('button_add', button::create(['type' => 'button', 'label' => $text['button-add'], 'icon' => $_SESSION['theme']['button_icon_add'], 'id' => 'btn_add', 'link' => 'bridge_edit.php']));
    }

// Notice the modal is assigned here with the button creation instead of using multiple if statements later
//copy button
    if (permission_exists('bridge_add') && $bridges) {
        $smarty->assign('checkbox', true);
        $smarty->assign('button_copy', button::create(['type' => 'button', 'label' => $text['button-copy'], 'icon' => $_SESSION['theme']['button_icon_copy'], 'id' => 'btn_copy', 'name' => 'btn_copy', 'style' => 'display: none;', 'onclick' => "modal_open('modal-copy','btn_copy');"]));
        $smarty->assign('modal_copy', modal::create(['id' => 'modal-copy', 'type' => 'copy', 'actions' => button::create(['type' => 'button', 'label' => $text['button-continue'], 'icon' => 'check', 'id' => 'btn_copy', 'style' => 'float: right; margin-left: 15px;', 'collapse' => 'never', 'onclick' => "modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]));
    }

//edit button
    if (permission_exists('bridge_edit') && $bridges) {
        $smarty->assign('checkbox', true);
        $smarty->assign('button_edit', button::create(['type' => 'button', 'label' => $text['button-toggle'], 'icon' => $_SESSION['theme']['button_icon_toggle'], 'id' => 'btn_toggle', 'name' => 'btn_toggle', 'style' => 'display: none;', 'onclick' => "modal_open('modal-toggle','btn_toggle');"]));
        $smarty->assign('modal_edit', modal::create(['id' => 'modal-toggle', 'type' => 'toggle', 'actions' => button::create(['type' => 'button', 'label' => $text['button-continue'], 'icon' => 'check', 'id' => 'btn_toggle', 'style' => 'float: right; margin-left: 15px;', 'collapse' => 'never', 'onclick' => "modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]));
    }

//delete button
    if (permission_exists('bridge_delete') && $bridges) {
        $smarty->assign('checkbox', true);
        $smarty->assign('button_delete', button::create(['type' => 'button', 'label' => $text['button-delete'], 'icon' => $_SESSION['theme']['button_icon_delete'], 'id' => 'btn_delete', 'name' => 'btn_delete', 'style' => 'display: none;', 'onclick' => "modal_open('modal-delete','btn_delete');"]));
        $smarty->assign('modal_delete',modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]));
    }

//showall button
    if (permission_exists('bridge_all')) {
        $smarty->assign('button_showall', button::create(['type' => 'button', 'label' => $text['button-show_all'], 'icon' => $_SESSION['theme']['button_icon_all'], 'link' => '?show=all']));
    }

//search button
    $smarty->assign('search_button',button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']));
    $smarty->assign('search_value',$search);

//edit page
    $smarty->assign('edit_page', 'bridge_edit.php');
    
//data list
    $smarty->assign('rows', $bridges);

//token
    $smarty->assign('token', $token);

//paging
    $smarty->assign('paging_controls_mini',$paging_controls_mini);
    $smarty->assign('paging_controls',$paging_controls);

//set the title for the browser
    $document['title'] = $text['title-bridges'];
//display the header
    require_once "resources/header.php";
//display the page
    $smarty->display('bridges.tpl');
//include the footer
    require_once "resources/footer.php";

    