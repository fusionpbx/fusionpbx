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


