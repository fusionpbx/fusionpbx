<?php

return [
    'login' => 'user_all',
    'users.index' => 'user_view',
    'users.create' => 'user_add',
    'users.store' => 'user_add',
    'users.show' => 'user_view',
    'users.edit' => 'user_edit',
    'users.update' => 'user_edit',
    'users.destroy' => 'user_delete',
    'users.import' => 'user_import',
    'users.importAction' => 'user_import',

    'groups.index' => 'group_view',
    'groups.create' => 'group_add',
    'groups.store' => 'group_add',
    'groups.edit' => 'group_edit',
    'groups.update' => 'group_edit',
    'groups.destroy' => 'group_delete',

    'permissions.index' => 'permission_view',
    'permissions.create' => 'permission_add',
    'permissions.update' => 'permission_edit',
    'permissions.store' => 'permission_add',
    'permissions.edit' => 'permission_edit',
    'permissions.destroy' => 'permission_delete',


    'permissions.index' => 'group_permission_view',
    'permissions.update' => 'group_permission_add',
    'permissions.update'=> 'group_permission_edit',
    'permissions.show' => 'group_permission_view',
    'permissions.update'=> 'group_permission_delete',

    'gateways.index' => 'gateway_view',
    'gateways.create' => 'gateway_add',
    'gateways.store' => 'gateway_add',
    'gateways.edit' => 'gateway_edit',
    'gateways.copy' => 'gateway_add',
    'gateways.update' => 'gateway_edit',
    'gateways.destroy' => 'gateway_delete',

    'sipprofiles.index' => 'sip_profile_view',
    'sipprofiles.create' => 'sip_profile_add',
    'sipprofiles.store' => 'sip_profile_add',
    'sipprofiles.edit' => 'sip_profile_edit',
    'sipprofiles.update' => 'sip_profile_edit',
    'sipprofiles.destroy' => 'sip_profile_delete',
    'sipprofiles.copy' => 'sip_profile_add',  

    'sipprofiles.domains.create' => 'sip_profile_domain_add',
    'sipprofiles.domains.edit' => 'sip_profile_domain_edit',
    'sipprofiles.domains.destroy' => 'sip_profile_domain_delete',
    'sipprofiles.domains.view' => 'sip_profile_domain_view',
    

    'sipprofiles.settings.create' => 'sip_profile_setting_add',
    'sipprofiles.settings.edit' => 'sip_profile_setting_edit',
    'sipprofiles.settings.destroy' => 'sip_profile_setting_delete',
    'sipprofiles.settings.view' => 'sip_profile_setting_view',
];
