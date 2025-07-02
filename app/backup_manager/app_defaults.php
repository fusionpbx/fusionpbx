<?php
// process once on install
if ($domains_processed == 1) {
    // insert default settings if they do not exist
    $defaults = [
        ['uuid'=>'c1e0b8a2-b7d2-4eaa-8d2a-001122334455','category'=>'backup_manager','subcategory'=>'auto_backup_enabled','name'=>'boolean','value'=>'false'],
        ['uuid'=>'d2e0b8a2-b7d2-4eaa-8d2a-001122334455','category'=>'backup_manager','subcategory'=>'auto_backup_frequency','name'=>'text','value'=>'daily'],
        ['uuid'=>'e3e0b8a2-b7d2-4eaa-8d2a-001122334455','category'=>'backup_manager','subcategory'=>'auto_backup_keep','name'=>'numeric','value'=>'7'],
    ];
    $p = permissions::new();
    $p->add('default_setting_add','temp');
    $p->add('default_setting_edit','temp');
    foreach ($defaults as $row) {
        $sql = "select count(*) from v_default_settings where default_setting_uuid = :uuid";
        $params = ['uuid'=>$row['uuid']];
        $count = $database->select($sql, $params, 'column');
        if ($count == 0) {
            $array['default_settings'][0] = [
                'default_setting_uuid'=>$row['uuid'],
                'default_setting_category'=>$row['category'],
                'default_setting_subcategory'=>$row['subcategory'],
                'default_setting_name'=>$row['name'],
                'default_setting_value'=>$row['value'],
                'default_setting_enabled'=>'true'
            ];
            $database->app_name = 'backup_manager';
            $database->app_uuid = 'b45cc3a6-1f4e-4f9d-8c69-3a065e1d2d1a';
            $database->save($array);
            unset($array);
        }
    }
    $p->delete('default_setting_add','temp');
    $p->delete('default_setting_edit','temp');
}
?>
