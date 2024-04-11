<?php

$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])["document.root"]);

require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('check_compare_db')) {
    //access granted
} else {
    echo "access denied";
    exit;
}

//check compare status


?>

<script style='display: none'>
    function statusCompare() {
        let checkBD = '';
        checkBD += '<ul class="navbar-nav check-compare" style="align-items: center;cursor: pointer;" id="check_compare_status">';
        checkBD += '    <i class="fas fa-solid fa-server"></i>';
        checkBD += '    <div class="header_messages_note checking-compare-text" id="checking-compare-text" title="" href="" style="font-family: arial;font-size: 10.25pt;color: #fff;padding: 15px 10px 14px 0px;">';
        checkBD += '        Checking';
        checkBD += '    </div>';
        checkBD += '</ul>';
        $('#check_compare_status').remove();
        $('#main_navbar').children('.ml-auto').before(checkBD);
        $('#check_compare_status').on('click', (function () {
            console.log('check_compare_status');
            if ($('#check_compare_content').is(':hidden')) {
                $('#check_compare_content').fadeIn();
            } else {
                $('#check_compare_content').fadeOut();
            }
        }));
    }

    function compareService(h1, h2) {
        $.ajax({
            url: `/core/auto/compare_dbs_new.php?h1=${h1}&h2=${h2}`,
            type: "get",
            async: true,
            success: function (response) {
                const errorExist = response.toLowerCase().match('error') && true;
                const successExist = errorExist ? false : response.toLowerCase().match('success') && true;
                const compareExist = response.toLowerCase().match('compare') ? true : false;
                if (successExist == true && compareExist) {
                    $('#check_compare_status').addClass('check-green');
                    $('#checking-compare-text').text('DB OK');
                }
                if (errorExist == true && compareExist) {
                    $('#check_compare_status').addClass('check-red');
                    $('#checking-compare-text').text('DB FAIL');
                }
                if (errorExist == undefined && successExist == undefined && compareExist == false) {
                    $('#check_compare_status').addClass('check-yellow');
                    $('#checking-compare-text').text('Service is not working');
                }
                if (compareExist == false) {
                    $('#checking-compare-text').text('Incorrect settings');
                }
                const lines = response?.split('<br>');
                const errorLines = lines?.map((line) => {
                    if (line?.match(/Error(.*?)\[/)) {
                        const host1 = line.match(/\](.*?):/)[1].trim();
                        const host2 = line.match(/==>(.*?):/)[1].trim();
                        const table = lines[lines.indexOf(line) - 1]?.split(' ')[2].split(':').pop();
                        const variable = line.match(/\[(.*?)]/)?.pop();
                        const value = line.match(/](.*?)\:(.*?)==>/)?.pop();
                        const error = {
                            compare: `<span style="font-size: 9pt;font-weight: bold;">` + host1 + '</span>' + ' -> ' + '<span style="font-size: 9pt;font-weight: bold;">' + host2 + '</span>',
                            table,
                            variable,
                            value
                        };
                        return error;
                    }
                }).filter(item => item);

                if (errorLines?.length > 0) {
                    // $('#checking-compare-text').append(' ' + (errorLines?.length ? ('<span style="color: #ffffffa3;border: 1px solid #f12c2c94;border-radius: 10px;padding: 0px 5px;margin: 0px 5px;">' + errorLines?.length + '</span>') : ''));
                    $('#check_compare_status').append('<div class="check_compare_content" id="check_compare_content"></div>');
                    let compare_table_error = '';
                    compare_table_error += '<table class="table">';
                    // compare_table_error += '  <thead>';
                    // compare_table_error += '    <tr>';
                    // compare_table_error += '      <th scope="col">Console</th>';
                    // // compare_table_error += '      <th scope="col">Variable</th>';
                    // // compare_table_error += '      <th scope="col">Value</th>';
                    // compare_table_error += '    </tr>';
                    // compare_table_error += '  </thead>';
                    compare_table_error += '  <tbody>';
                    errorLines?.map((item) => {
                        compare_table_error += `    <tr>`;
                        compare_table_error += `      <td style="font-size: 9pt;">${item.compare} <b style="margin: 0px 0px 0px 5px;font-size: 9pt;border: 1px solid #690000db;border-radius: 6px;padding: 1px 3px;">Error</b> <span style="font-weight: bold;text-transform: uppercase;margin: 0px 3px 0px 3px;font-size: 9pt;">${item.variable}</span><span style="background: #a94848e8;color: white;font-weight: bold;padding: 3px 5px;border-radius: 8px;margin: 0px 5px;">${item?.variable?.toLowerCase() == 'notnull' ? (item.value == 1 ? 'true' : 'false') : item.value}</span> <span style="font-weight: bold;">in</span> <span style="background: #22283fc2;color: white;font-weight: bold;padding: 3px 8px;border-radius: 8px;margin: 0px 5px;">${item.table}</span></td>`;
                        // compare_table_error += `      <td>${item.variable}</td>`;
                        // compare_table_error += `      <td>${item.value}</td>`;
                        compare_table_error += `    </tr>`;
                    });
                    compare_table_error += '  </tbody>';
                    compare_table_error += '</table>';
                    $('#check_compare_content').html(compare_table_error);
                };
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // $('#check_compare_status').addClass('check-yellow');
                $('#checking-compare-text').text('Not Found');
            }
        });

    }
</script>

<?php


$refresh_default = 600; //seconds
$refresh = (is_numeric($_SESSION['data_base_replication_check']['refresh_time']['numeric']) ? $_SESSION['data_base_replication_check']['refresh_time']['numeric'] : $refresh_default) * 1000;


if (is_numeric($_SESSION['data_base_replication_check']['refresh_time']['numeric']) && is_string($_SESSION['data_base_replication_check']['node_1']['text']) && is_string($_SESSION['data_base_replication_check']['node_2']['text'])) {
    echo "<div  style=\"display: none;\">";
    echo "<script>statusCompare();compareService('" . $_SESSION['data_base_replication_check']['node_1']['text'] . "', '" . $_SESSION['data_base_replication_check']['node_2']['text'] . "');";
    echo "
    setInterval(function() {
        compareService('" . $_SESSION['data_base_replication_check']['node_1']['text'] . "', '" . $_SESSION['data_base_replication_check']['node_2']['text'] . "');
     }, " . $refresh . ");";
    echo "</script>";
    echo "</div>";
}

?>
<style>
    .check_compare_content {
        display: none;
        position: fixed;
        top: calc(7vh);
        width: auto;
        border-radius: 5px;
        background: white;
        overflow-y: scroll;
        height: calc(100vh - 80px);
        text-align: left;
        padding: 0px 5px;
        margin-left: auto;
        margin-right: auto;
        left: calc(50vw - 25%);
        z-index: 1001;
        box-shadow: rgb(0 0 0 / 60%) 0px 4px 28px, rgb(0 0 0 / 9%) 0px 10px 10px;
    }

    /* #check_compare_status:hover .check_compare_content {
        z-index: 10;
        opacity: 1;
        visibility: visible;
        transition: all 0.5s cubic-bezier(0.75, -0.02, 0.2, 0.97);
    } */
</style>