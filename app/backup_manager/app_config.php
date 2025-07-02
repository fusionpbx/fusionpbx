<?php
//------------------------------------------------------------------------------
// app_config.php
// FusionPBX Backup Manager App Configuration
//------------------------------------------------------------------------------
//application details
$apps[$x]['name'] = "Backup Manager";
$apps[$x]['uuid'] = "b45cc3a6-1f4e-4f9d-8c69-3a065e1d2d1a";
$apps[$x]['category'] = "Advanced";
$apps[$x]['subcategory'] = "";
$apps[$x]['version'] = "1.0";
$apps[$x]['license'] = "Mozilla Public License 1.1";
$apps[$x]['url'] = "https://github.com/yourrepo/fusionpbx-backup_manager";
$apps[$x]['description']['en-us'] = "Backup and Restore Manager for FusionPBX";

// Permissions
$y=0;
$apps[$x]['permissions'][$y]['name'] = "backup_manager_backup";
$apps[$x]['permissions'][$y]['uuid'] = "6d8f234e-0be9-4c29-b5f1-9e1234567890";
$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
$y++;
$apps[$x]['permissions'][$y]['name'] = "backup_manager_restore";
$apps[$x]['permissions'][$y]['uuid'] = "aedf4567-1b2c-3d4e-5f6a-7b8901cdef23";
$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

// Default settings
$y = 0;
$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "c1e0b8a2-b7d2-4eaa-8d2a-001122334455";
$apps[$x]['default_settings'][$y]['default_setting_category'] = "backup_manager";
$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "auto_backup_enabled";
$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
$apps[$x]['default_settings'][$y]['default_setting_value'] = "false";
$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
$y++;
$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "d2e0b8a2-b7d2-4eaa-8d2a-001122334455";
$apps[$x]['default_settings'][$y]['default_setting_category'] = "backup_manager";
$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "auto_backup_frequency";
$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
$apps[$x]['default_settings'][$y]['default_setting_value'] = "daily";
$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
$y++;
$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "e3e0b8a2-b7d2-4eaa-8d2a-001122334455";
$apps[$x]['default_settings'][$y]['default_setting_category'] = "backup_manager";
$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "auto_backup_keep";
$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
$apps[$x]['default_settings'][$y]['default_setting_value'] = "7";
$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";


