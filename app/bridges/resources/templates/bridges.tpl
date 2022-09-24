{$fusionpbx_header}
<body>
    <div class='action_bar' id='action_bar'>
        <div class='heading'><b>{$heading} ( {$rows|count} )</b></div>
        <div class='actions'>
            {$button_reload}{$button_add}{$button_copy}{$button_toggle}{$button_delete}
        </div>
        <form id='form_search' class='inline' method='get'>
            {$button_showall}
            {if $search_button != ""}
            <input type='text' class='txt list-search' name='search' id='search' value="{$search_value|escape}" placeholder="{$search_placeholder}" onkeydown=''>
            {$search_button}
            {/if}
        </form>
    </div>
        {$modal_add}
        {$modal_copy}
        {$modal_toggle}
        {$modal_delete}
        <form id='form_list' method='post'>
            <input type='hidden' id='action' name='action' value='{$action_value}'>
            <input type='hidden' name='search' value="{$search_value}">
            <table class='list'>
                <tr class='list-header'>
                    {if $checkbox}
                        <th class="checkbox">
                            <input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' />
                        </th>
                    {/if}
                    {foreach $headers as $header}
                        {$header}
                    {/foreach}
                </tr>
                {foreach $rows as $row}
                    <tr class='list-row' href='{$edit_page}?id={$row.bridge_uuid}'>
                        {if $checkbox}
                            <td class="checkbox">
                                <input type="checkbox" name="bridges[{$row@index}][checked]" id="checkbox_{$row@index}" value="true" onclick="checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }" />
                                <input type="hidden" name="bridges[{$row@index}][uuid]" value="{$row.bridge_uuid|escape}" />
                            </td>
                        {/if}
                        <td>{$row.bridge_name}</td>
                        <td>{$row.bridge_destination}</td>
                        <td>{$row.bridge_enabled}</td>
                        <td>{$row.bridge_description}</td>
                 </tr>
                {/foreach}
            </table>
            <br>
            {if $paging_controls != ""}<div align='center'>{$paging_controls}</div>{/if}
            <input type='hidden' name='{$token.name}' value='{$token.hash}'>
        </form>
</body>
{$fusionpbx_footer}

