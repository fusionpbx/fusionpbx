@extends('layouts.app', ['page_title' => 'Users'])

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                            @if ($user->exists)
                                <li class="breadcrumb-item active">Edit User</li>
                            @else
                                <li class="breadcrumb-item active">Create New User</li>
                            @endif
                        </ol>
                    </div>
                    @if ($user->exists)
                        <h4 class="page-title">Edit User ({{ $user->user_adv_fields->first_name ?? '' }}
                            {{ $user->user_adv_fields->last_name ?? '' }})</h4>
                    @else
                        <h4 class="page-title">Create New User</h4>
                    @endif
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-body">

                        <ul class="nav nav-pills bg-nav-pills nav-justified mb-3" id="userNavTabs">
                            <li class="nav-item">
                                <a href="#profile" data-bs-toggle="tab" aria-expanded="true"
                                    class="nav-link rounded-0  active">
                                    <i class="mdi mdi-account-circle d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Profile</span>
                                </a>
                            </li>

                            @if (userCheckPermission('user_setting_view') && $user->exists)
                                <li class="nav-item">
                                    <a href="#setting" data-bs-toggle="tab" aria-expanded="false"
                                        class="nav-link rounded-0">
                                        <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                                        <span class="d-none d-md-block">Settings</span>
                                    </a>
                                </li>
                            @endif
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane show  active" id="profile">

                                <!-- Body Content-->
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h4 class="mt-2">Basic information</h4>

                                        <p class="text-muted mb-4">Provide the basic information about the user or contact.
                                        </p>
                                        @if ($user->exists)
                                            <form method="POST" id="user_form" action="{{ route('users.update', $user) }}">
                                                @method('put')
                                            @else
                                                <form method="POST" id="user_form" action="{{ route('users.store') }}">
                                        @endif
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="first_name" class="form-label">First Name <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" type="text"
                                                        value="{{ isset($user->user_adv_fields['first_name']) ? $user->user_adv_fields['first_name'] : '' }}"
                                                        placeholder="Enter your first name" id="first_name"
                                                        name="first_name" />
                                                    <div class="text-danger error_message first_name_err"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="last_name" class="form-label">Last Name</label>
                                                    <input class="form-control"
                                                        value="{{ isset($user->user_adv_fields['last_name']) ? $user->user_adv_fields['last_name'] : '' }}"
                                                        type="text" placeholder="Enter your last name" id="last_name"
                                                        name="last_name" />
                                                    <div class="text-danger error_message last_name_err"></div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->


                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email Address <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" value="{{ $user->user_email ?? '' }}"
                                                        type="email" placeholder="Enter your email" id="user_email"
                                                        name="user_email" />
                                                    <div class="text-danger error_message user_email_err"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="groups-select" class="form-label">Roles <span
                                                            class="text-danger">*</span></label>
                                                    <!-- Multiple Select -->
                                                    <select class="select2 form-control select2-multiple"
                                                        data-toggle="select2" multiple="multiple"
                                                        data-placeholder="Choose ..." id="groups-select"
                                                        @if (!userCheckPermission('user_group_edit')) disabled @endif name="groups[]">

                                                        @foreach ($all_groups as $group)
                                                            <option value="{{ $group->group_uuid }}"
                                                                @if (isset($user_groups) && $user_groups->contains($group)) selected @endif>
                                                                {{ ucfirst($group->group_name) }}

                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="text-danger error_message groups_err"></div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->

                                        <input class="form-control"
                                            value="{{ $user->domain->domain_uuid ?? Session::get('domain_uuid') }}"
                                            type="text" placeholder="" id="domain_uuid" name="domain_uuid" hidden />

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Time Zone <span
                                                            class="text-danger">*</span></label>
                                                    <input type="hidden" id="time_zone_val"
                                                        value="{{ isset($user_time_zone->user_setting_value) ? $user_time_zone->user_setting_value : '' }}">
                                                    <select data-toggle="select2" title="Time Zone" name="time_zone"
                                                        id="time_zone">

                                                        <option value=""></option>
                                                        <optgroup label="Africa">
                                                            <option value="Africa/Abidjan">Africa/Abidjan</option>
                                                            <option value="Africa/Accra">Africa/Accra</option>
                                                            <option value="Africa/Addis_Ababa">Africa/Addis_Ababa</option>
                                                            <option value="Africa/Algiers">Africa/Algiers</option>
                                                            <option value="Africa/Asmara">Africa/Asmara</option>
                                                            <option value="Africa/Bamako">Africa/Bamako</option>
                                                            <option value="Africa/Bangui">Africa/Bangui</option>
                                                            <option value="Africa/Banjul">Africa/Banjul</option>
                                                            <option value="Africa/Bissau">Africa/Bissau</option>
                                                            <option value="Africa/Blantyre">Africa/Blantyre</option>
                                                            <option value="Africa/Brazzaville">Africa/Brazzaville</option>
                                                            <option value="Africa/Bujumbura">Africa/Bujumbura</option>
                                                            <option value="Africa/Cairo">Africa/Cairo</option>
                                                            <option value="Africa/Casablanca">Africa/Casablanca</option>
                                                            <option value="Africa/Ceuta">Africa/Ceuta</option>
                                                            <option value="Africa/Conakry">Africa/Conakry</option>
                                                            <option value="Africa/Dakar">Africa/Dakar</option>
                                                            <option value="Africa/Dar_es_Salaam">Africa/Dar_es_Salaam
                                                            </option>
                                                            <option value="Africa/Djibouti">Africa/Djibouti</option>
                                                            <option value="Africa/Douala">Africa/Douala</option>
                                                            <option value="Africa/El_Aaiun">Africa/El_Aaiun</option>
                                                            <option value="Africa/Freetown">Africa/Freetown</option>
                                                            <option value="Africa/Gaborone">Africa/Gaborone</option>
                                                            <option value="Africa/Harare">Africa/Harare</option>
                                                            <option value="Africa/Johannesburg">Africa/Johannesburg
                                                            </option>
                                                            <option value="Africa/Juba">Africa/Juba</option>
                                                            <option value="Africa/Kampala">Africa/Kampala</option>
                                                            <option value="Africa/Khartoum">Africa/Khartoum</option>
                                                            <option value="Africa/Kigali">Africa/Kigali</option>
                                                            <option value="Africa/Kinshasa">Africa/Kinshasa</option>
                                                            <option value="Africa/Lagos">Africa/Lagos</option>
                                                            <option value="Africa/Libreville">Africa/Libreville</option>
                                                            <option value="Africa/Lome">Africa/Lome</option>
                                                            <option value="Africa/Luanda">Africa/Luanda</option>
                                                            <option value="Africa/Lubumbashi">Africa/Lubumbashi</option>
                                                            <option value="Africa/Lusaka">Africa/Lusaka</option>
                                                            <option value="Africa/Malabo">Africa/Malabo</option>
                                                            <option value="Africa/Maputo">Africa/Maputo</option>
                                                            <option value="Africa/Maseru">Africa/Maseru</option>
                                                            <option value="Africa/Mbabane">Africa/Mbabane</option>
                                                            <option value="Africa/Mogadishu">Africa/Mogadishu</option>
                                                            <option value="Africa/Monrovia">Africa/Monrovia</option>
                                                            <option value="Africa/Nairobi">Africa/Nairobi</option>
                                                            <option value="Africa/Ndjamena">Africa/Ndjamena</option>
                                                            <option value="Africa/Niamey">Africa/Niamey</option>
                                                            <option value="Africa/Nouakchott">Africa/Nouakchott</option>
                                                            <option value="Africa/Ouagadougou">Africa/Ouagadougou</option>
                                                            <option value="Africa/Porto-Novo">Africa/Porto-Novo</option>
                                                            <option value="Africa/Sao_Tome">Africa/Sao_Tome</option>
                                                            <option value="Africa/Tripoli">Africa/Tripoli</option>
                                                            <option value="Africa/Tunis">Africa/Tunis</option>
                                                            <option value="Africa/Windhoek">Africa/Windhoek</option>
                                                        </optgroup>
                                                        <optgroup label="America">
                                                            <option value="America/Adak">America/Adak</option>
                                                            <option value="America/Anchorage">America/Anchorage</option>
                                                            <option value="America/Anguilla">America/Anguilla</option>
                                                            <option value="America/Antigua">America/Antigua</option>
                                                            <option value="America/Araguaina">America/Araguaina</option>
                                                            <option value="America/Argentina/Buenos_Aires">
                                                                America/Argentina/Buenos_Aires</option>
                                                            <option value="America/Argentina/Catamarca">
                                                                America/Argentina/Catamarca</option>
                                                            <option value="America/Argentina/Cordoba">
                                                                America/Argentina/Cordoba</option>
                                                            <option value="America/Argentina/Jujuy">America/Argentina/Jujuy
                                                            </option>
                                                            <option value="America/Argentina/La_Rioja">
                                                                America/Argentina/La_Rioja</option>
                                                            <option value="America/Argentina/Mendoza">
                                                                America/Argentina/Mendoza</option>
                                                            <option value="America/Argentina/Rio_Gallegos">
                                                                America/Argentina/Rio_Gallegos</option>
                                                            <option value="America/Argentina/Salta">America/Argentina/Salta
                                                            </option>
                                                            <option value="America/Argentina/San_Juan">
                                                                America/Argentina/San_Juan</option>
                                                            <option value="America/Argentina/San_Luis">
                                                                America/Argentina/San_Luis</option>
                                                            <option value="America/Argentina/Tucuman">
                                                                America/Argentina/Tucuman</option>
                                                            <option value="America/Argentina/Ushuaia">
                                                                America/Argentina/Ushuaia</option>
                                                            <option value="America/Aruba">America/Aruba</option>
                                                            <option value="America/Asuncion">America/Asuncion</option>
                                                            <option value="America/Atikokan">America/Atikokan</option>
                                                            <option value="America/Bahia">America/Bahia</option>
                                                            <option value="America/Bahia_Banderas">America/Bahia_Banderas
                                                            </option>
                                                            <option value="America/Barbados">America/Barbados</option>
                                                            <option value="America/Belem">America/Belem</option>
                                                            <option value="America/Belize">America/Belize</option>
                                                            <option value="America/Blanc-Sablon">America/Blanc-Sablon
                                                            </option>
                                                            <option value="America/Boa_Vista">America/Boa_Vista</option>
                                                            <option value="America/Bogota">America/Bogota</option>
                                                            <option value="America/Boise">America/Boise</option>
                                                            <option value="America/Cambridge_Bay">America/Cambridge_Bay
                                                            </option>
                                                            <option value="America/Campo_Grande">America/Campo_Grande
                                                            </option>
                                                            <option value="America/Cancun">America/Cancun</option>
                                                            <option value="America/Caracas">America/Caracas</option>
                                                            <option value="America/Cayenne">America/Cayenne</option>
                                                            <option value="America/Cayman">America/Cayman</option>
                                                            <option value="America/Chicago">America/Chicago</option>
                                                            <option value="America/Chihuahua">America/Chihuahua</option>
                                                            <option value="America/Costa_Rica">America/Costa_Rica</option>
                                                            <option value="America/Creston">America/Creston</option>
                                                            <option value="America/Cuiaba">America/Cuiaba</option>
                                                            <option value="America/Curacao">America/Curacao</option>
                                                            <option value="America/Danmarkshavn">America/Danmarkshavn
                                                            </option>
                                                            <option value="America/Dawson">America/Dawson</option>
                                                            <option value="America/Dawson_Creek">America/Dawson_Creek
                                                            </option>
                                                            <option value="America/Denver">America/Denver</option>
                                                            <option value="America/Detroit">America/Detroit</option>
                                                            <option value="America/Dominica">America/Dominica</option>
                                                            <option value="America/Edmonton">America/Edmonton</option>
                                                            <option value="America/Eirunepe">America/Eirunepe</option>
                                                            <option value="America/El_Salvador">America/El_Salvador
                                                            </option>
                                                            <option value="America/Fort_Nelson">America/Fort_Nelson
                                                            </option>
                                                            <option value="America/Fortaleza">America/Fortaleza</option>
                                                            <option value="America/Glace_Bay">America/Glace_Bay</option>
                                                            <option value="America/Goose_Bay">America/Goose_Bay</option>
                                                            <option value="America/Grand_Turk">America/Grand_Turk</option>
                                                            <option value="America/Grenada">America/Grenada</option>
                                                            <option value="America/Guadeloupe">America/Guadeloupe</option>
                                                            <option value="America/Guatemala">America/Guatemala</option>
                                                            <option value="America/Guayaquil">America/Guayaquil</option>
                                                            <option value="America/Guyana">America/Guyana</option>
                                                            <option value="America/Halifax">America/Halifax</option>
                                                            <option value="America/Havana">America/Havana</option>
                                                            <option value="America/Hermosillo">America/Hermosillo</option>
                                                            <option value="America/Indiana/Indianapolis">
                                                                America/Indiana/Indianapolis</option>
                                                            <option value="America/Indiana/Knox">America/Indiana/Knox
                                                            </option>
                                                            <option value="America/Indiana/Marengo">America/Indiana/Marengo
                                                            </option>
                                                            <option value="America/Indiana/Petersburg">
                                                                America/Indiana/Petersburg</option>
                                                            <option value="America/Indiana/Tell_City">
                                                                America/Indiana/Tell_City</option>
                                                            <option value="America/Indiana/Vevay">America/Indiana/Vevay
                                                            </option>
                                                            <option value="America/Indiana/Vincennes">
                                                                America/Indiana/Vincennes</option>
                                                            <option value="America/Indiana/Winamac">America/Indiana/Winamac
                                                            </option>
                                                            <option value="America/Inuvik">America/Inuvik</option>
                                                            <option value="America/Iqaluit">America/Iqaluit</option>
                                                            <option value="America/Jamaica">America/Jamaica</option>
                                                            <option value="America/Juneau">America/Juneau</option>
                                                            <option value="America/Kentucky/Louisville">
                                                                America/Kentucky/Louisville</option>
                                                            <option value="America/Kentucky/Monticello">
                                                                America/Kentucky/Monticello</option>
                                                            <option value="America/Kralendijk">America/Kralendijk</option>
                                                            <option value="America/La_Paz">America/La_Paz</option>
                                                            <option value="America/Lima">America/Lima</option>
                                                            <option value="America/Los_Angeles">America/Los_Angeles
                                                            </option>
                                                            <option value="America/Lower_Princes">America/Lower_Princes
                                                            </option>
                                                            <option value="America/Maceio">America/Maceio</option>
                                                            <option value="America/Managua">America/Managua</option>
                                                            <option value="America/Manaus">America/Manaus</option>
                                                            <option value="America/Marigot">America/Marigot</option>
                                                            <option value="America/Martinique">America/Martinique</option>
                                                            <option value="America/Matamoros">America/Matamoros</option>
                                                            <option value="America/Mazatlan">America/Mazatlan</option>
                                                            <option value="America/Menominee">America/Menominee</option>
                                                            <option value="America/Merida">America/Merida</option>
                                                            <option value="America/Metlakatla">America/Metlakatla</option>
                                                            <option value="America/Mexico_City">America/Mexico_City
                                                            </option>
                                                            <option value="America/Miquelon">America/Miquelon</option>
                                                            <option value="America/Moncton">America/Moncton</option>
                                                            <option value="America/Monterrey">America/Monterrey</option>
                                                            <option value="America/Montevideo">America/Montevideo</option>
                                                            <option value="America/Montserrat">America/Montserrat</option>
                                                            <option value="America/Nassau">America/Nassau</option>
                                                            <option value="America/New_York">America/New_York</option>
                                                            <option value="America/Nipigon">America/Nipigon</option>
                                                            <option value="America/Nome">America/Nome</option>
                                                            <option value="America/Noronha">America/Noronha</option>
                                                            <option value="America/North_Dakota/Beulah">
                                                                America/North_Dakota/Beulah</option>
                                                            <option value="America/North_Dakota/Center">
                                                                America/North_Dakota/Center</option>
                                                            <option value="America/North_Dakota/New_Salem">
                                                                America/North_Dakota/New_Salem</option>
                                                            <option value="America/Nuuk">America/Nuuk</option>
                                                            <option value="America/Ojinaga">America/Ojinaga</option>
                                                            <option value="America/Panama">America/Panama</option>
                                                            <option value="America/Pangnirtung">America/Pangnirtung
                                                            </option>
                                                            <option value="America/Paramaribo">America/Paramaribo</option>
                                                            <option value="America/Phoenix">America/Phoenix</option>
                                                            <option value="America/Port-au-Prince">America/Port-au-Prince
                                                            </option>
                                                            <option value="America/Port_of_Spain">America/Port_of_Spain
                                                            </option>
                                                            <option value="America/Porto_Velho">America/Porto_Velho
                                                            </option>
                                                            <option value="America/Puerto_Rico">America/Puerto_Rico
                                                            </option>
                                                            <option value="America/Punta_Arenas">America/Punta_Arenas
                                                            </option>
                                                            <option value="America/Rainy_River">America/Rainy_River
                                                            </option>
                                                            <option value="America/Rankin_Inlet">America/Rankin_Inlet
                                                            </option>
                                                            <option value="America/Recife">America/Recife</option>
                                                            <option value="America/Regina">America/Regina</option>
                                                            <option value="America/Resolute">America/Resolute</option>
                                                            <option value="America/Rio_Branco">America/Rio_Branco</option>
                                                            <option value="America/Santarem">America/Santarem</option>
                                                            <option value="America/Santiago">America/Santiago</option>
                                                            <option value="America/Santo_Domingo">America/Santo_Domingo
                                                            </option>
                                                            <option value="America/Sao_Paulo">America/Sao_Paulo</option>
                                                            <option value="America/Scoresbysund">America/Scoresbysund
                                                            </option>
                                                            <option value="America/Sitka">America/Sitka</option>
                                                            <option value="America/St_Barthelemy">America/St_Barthelemy
                                                            </option>
                                                            <option value="America/St_Johns">America/St_Johns</option>
                                                            <option value="America/St_Kitts">America/St_Kitts</option>
                                                            <option value="America/St_Lucia">America/St_Lucia</option>
                                                            <option value="America/St_Thomas">America/St_Thomas</option>
                                                            <option value="America/St_Vincent">America/St_Vincent</option>
                                                            <option value="America/Swift_Current">America/Swift_Current
                                                            </option>
                                                            <option value="America/Tegucigalpa">America/Tegucigalpa
                                                            </option>
                                                            <option value="America/Thule">America/Thule</option>
                                                            <option value="America/Thunder_Bay">America/Thunder_Bay
                                                            </option>
                                                            <option value="America/Tijuana">America/Tijuana</option>
                                                            <option value="America/Toronto">America/Toronto</option>
                                                            <option value="America/Tortola">America/Tortola</option>
                                                            <option value="America/Vancouver">America/Vancouver</option>
                                                            <option value="America/Whitehorse">America/Whitehorse</option>
                                                            <option value="America/Winnipeg">America/Winnipeg</option>
                                                            <option value="America/Yakutat">America/Yakutat</option>
                                                            <option value="America/Yellowknife">America/Yellowknife
                                                            </option>
                                                        </optgroup>
                                                        <optgroup label="Antarctica">
                                                            <option value="Antarctica/Casey">Antarctica/Casey</option>
                                                            <option value="Antarctica/Davis">Antarctica/Davis</option>
                                                            <option value="Antarctica/DumontDUrville">
                                                                Antarctica/DumontDUrville</option>
                                                            <option value="Antarctica/Macquarie">Antarctica/Macquarie
                                                            </option>
                                                            <option value="Antarctica/Mawson">Antarctica/Mawson</option>
                                                            <option value="Antarctica/McMurdo">Antarctica/McMurdo</option>
                                                            <option value="Antarctica/Palmer">Antarctica/Palmer</option>
                                                            <option value="Antarctica/Rothera">Antarctica/Rothera</option>
                                                            <option value="Antarctica/Syowa">Antarctica/Syowa</option>
                                                            <option value="Antarctica/Troll">Antarctica/Troll</option>
                                                            <option value="Antarctica/Vostok">Antarctica/Vostok</option>
                                                        </optgroup>
                                                        <optgroup label="Arctic">
                                                            <option value="Arctic/Longyearbyen">Arctic/Longyearbyen
                                                            </option>
                                                        </optgroup>
                                                        <optgroup label="Asia">
                                                            <option value="Asia/Aden">Asia/Aden</option>
                                                            <option value="Asia/Almaty">Asia/Almaty</option>
                                                            <option value="Asia/Amman">Asia/Amman</option>
                                                            <option value="Asia/Anadyr">Asia/Anadyr</option>
                                                            <option value="Asia/Aqtau">Asia/Aqtau</option>
                                                            <option value="Asia/Aqtobe">Asia/Aqtobe</option>
                                                            <option value="Asia/Ashgabat">Asia/Ashgabat</option>
                                                            <option value="Asia/Atyrau">Asia/Atyrau</option>
                                                            <option value="Asia/Baghdad">Asia/Baghdad</option>
                                                            <option value="Asia/Bahrain">Asia/Bahrain</option>
                                                            <option value="Asia/Baku">Asia/Baku</option>
                                                            <option value="Asia/Bangkok">Asia/Bangkok</option>
                                                            <option value="Asia/Barnaul">Asia/Barnaul</option>
                                                            <option value="Asia/Beirut">Asia/Beirut</option>
                                                            <option value="Asia/Bishkek">Asia/Bishkek</option>
                                                            <option value="Asia/Brunei">Asia/Brunei</option>
                                                            <option value="Asia/Chita">Asia/Chita</option>
                                                            <option value="Asia/Choibalsan">Asia/Choibalsan</option>
                                                            <option value="Asia/Colombo">Asia/Colombo</option>
                                                            <option value="Asia/Damascus">Asia/Damascus</option>
                                                            <option value="Asia/Dhaka">Asia/Dhaka</option>
                                                            <option value="Asia/Dili">Asia/Dili</option>
                                                            <option value="Asia/Dubai">Asia/Dubai</option>
                                                            <option value="Asia/Dushanbe">Asia/Dushanbe</option>
                                                            <option value="Asia/Famagusta">Asia/Famagusta</option>
                                                            <option value="Asia/Gaza">Asia/Gaza</option>
                                                            <option value="Asia/Hebron">Asia/Hebron</option>
                                                            <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh</option>
                                                            <option value="Asia/Hong_Kong">Asia/Hong_Kong</option>
                                                            <option value="Asia/Hovd">Asia/Hovd</option>
                                                            <option value="Asia/Irkutsk">Asia/Irkutsk</option>
                                                            <option value="Asia/Jakarta">Asia/Jakarta</option>
                                                            <option value="Asia/Jayapura">Asia/Jayapura</option>
                                                            <option value="Asia/Jerusalem">Asia/Jerusalem</option>
                                                            <option value="Asia/Kabul">Asia/Kabul</option>
                                                            <option value="Asia/Kamchatka">Asia/Kamchatka</option>
                                                            <option value="Asia/Karachi">Asia/Karachi</option>
                                                            <option value="Asia/Kathmandu">Asia/Kathmandu</option>
                                                            <option value="Asia/Khandyga">Asia/Khandyga</option>
                                                            <option value="Asia/Kolkata">Asia/Kolkata</option>
                                                            <option value="Asia/Krasnoyarsk">Asia/Krasnoyarsk</option>
                                                            <option value="Asia/Kuala_Lumpur">Asia/Kuala_Lumpur</option>
                                                            <option value="Asia/Kuching">Asia/Kuching</option>
                                                            <option value="Asia/Kuwait">Asia/Kuwait</option>
                                                            <option value="Asia/Macau">Asia/Macau</option>
                                                            <option value="Asia/Magadan">Asia/Magadan</option>
                                                            <option value="Asia/Makassar">Asia/Makassar</option>
                                                            <option value="Asia/Manila">Asia/Manila</option>
                                                            <option value="Asia/Muscat">Asia/Muscat</option>
                                                            <option value="Asia/Nicosia">Asia/Nicosia</option>
                                                            <option value="Asia/Novokuznetsk">Asia/Novokuznetsk</option>
                                                            <option value="Asia/Novosibirsk">Asia/Novosibirsk</option>
                                                            <option value="Asia/Omsk">Asia/Omsk</option>
                                                            <option value="Asia/Oral">Asia/Oral</option>
                                                            <option value="Asia/Phnom_Penh">Asia/Phnom_Penh</option>
                                                            <option value="Asia/Pontianak">Asia/Pontianak</option>
                                                            <option value="Asia/Pyongyang">Asia/Pyongyang</option>
                                                            <option value="Asia/Qatar">Asia/Qatar</option>
                                                            <option value="Asia/Qostanay">Asia/Qostanay</option>
                                                            <option value="Asia/Qyzylorda">Asia/Qyzylorda</option>
                                                            <option value="Asia/Riyadh">Asia/Riyadh</option>
                                                            <option value="Asia/Sakhalin">Asia/Sakhalin</option>
                                                            <option value="Asia/Samarkand">Asia/Samarkand</option>
                                                            <option value="Asia/Seoul">Asia/Seoul</option>
                                                            <option value="Asia/Shanghai">Asia/Shanghai</option>
                                                            <option value="Asia/Singapore">Asia/Singapore</option>
                                                            <option value="Asia/Srednekolymsk">Asia/Srednekolymsk</option>
                                                            <option value="Asia/Taipei">Asia/Taipei</option>
                                                            <option value="Asia/Tashkent">Asia/Tashkent</option>
                                                            <option value="Asia/Tbilisi">Asia/Tbilisi</option>
                                                            <option value="Asia/Tehran">Asia/Tehran</option>
                                                            <option value="Asia/Thimphu">Asia/Thimphu</option>
                                                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                                                            <option value="Asia/Tomsk">Asia/Tomsk</option>
                                                            <option value="Asia/Ulaanbaatar">Asia/Ulaanbaatar</option>
                                                            <option value="Asia/Urumqi">Asia/Urumqi</option>
                                                            <option value="Asia/Ust-Nera">Asia/Ust-Nera</option>
                                                            <option value="Asia/Vientiane">Asia/Vientiane</option>
                                                            <option value="Asia/Vladivostok">Asia/Vladivostok</option>
                                                            <option value="Asia/Yakutsk">Asia/Yakutsk</option>
                                                            <option value="Asia/Yangon">Asia/Yangon</option>
                                                            <option value="Asia/Yekaterinburg">Asia/Yekaterinburg</option>
                                                            <option value="Asia/Yerevan">Asia/Yerevan</option>
                                                        </optgroup>
                                                        <optgroup label="Atlantic">
                                                            <option value="Atlantic/Azores">Atlantic/Azores</option>
                                                            <option value="Atlantic/Bermuda">Atlantic/Bermuda</option>
                                                            <option value="Atlantic/Canary">Atlantic/Canary</option>
                                                            <option value="Atlantic/Cape_Verde">Atlantic/Cape_Verde
                                                            </option>
                                                            <option value="Atlantic/Faroe">Atlantic/Faroe</option>
                                                            <option value="Atlantic/Madeira">Atlantic/Madeira</option>
                                                            <option value="Atlantic/Reykjavik">Atlantic/Reykjavik</option>
                                                            <option value="Atlantic/South_Georgia">Atlantic/South_Georgia
                                                            </option>
                                                            <option value="Atlantic/St_Helena">Atlantic/St_Helena</option>
                                                            <option value="Atlantic/Stanley">Atlantic/Stanley</option>
                                                        </optgroup>
                                                        <optgroup label="Australia">
                                                            <option value="Australia/Adelaide">Australia/Adelaide</option>
                                                            <option value="Australia/Brisbane">Australia/Brisbane</option>
                                                            <option value="Australia/Broken_Hill">Australia/Broken_Hill
                                                            </option>
                                                            <option value="Australia/Darwin">Australia/Darwin</option>
                                                            <option value="Australia/Eucla">Australia/Eucla</option>
                                                            <option value="Australia/Hobart">Australia/Hobart</option>
                                                            <option value="Australia/Lindeman">Australia/Lindeman</option>
                                                            <option value="Australia/Lord_Howe">Australia/Lord_Howe
                                                            </option>
                                                            <option value="Australia/Melbourne">Australia/Melbourne
                                                            </option>
                                                            <option value="Australia/Perth">Australia/Perth</option>
                                                            <option value="Australia/Sydney">Australia/Sydney</option>
                                                        </optgroup>
                                                        <optgroup label="Europe">
                                                            <option value="Europe/Amsterdam">Europe/Amsterdam</option>
                                                            <option value="Europe/Andorra">Europe/Andorra</option>
                                                            <option value="Europe/Astrakhan">Europe/Astrakhan</option>
                                                            <option value="Europe/Athens">Europe/Athens</option>
                                                            <option value="Europe/Belgrade">Europe/Belgrade</option>
                                                            <option value="Europe/Berlin">Europe/Berlin</option>
                                                            <option value="Europe/Bratislava">Europe/Bratislava</option>
                                                            <option value="Europe/Brussels">Europe/Brussels</option>
                                                            <option value="Europe/Bucharest">Europe/Bucharest</option>
                                                            <option value="Europe/Budapest">Europe/Budapest</option>
                                                            <option value="Europe/Busingen">Europe/Busingen</option>
                                                            <option value="Europe/Chisinau">Europe/Chisinau</option>
                                                            <option value="Europe/Copenhagen">Europe/Copenhagen</option>
                                                            <option value="Europe/Dublin">Europe/Dublin</option>
                                                            <option value="Europe/Gibraltar">Europe/Gibraltar</option>
                                                            <option value="Europe/Guernsey">Europe/Guernsey</option>
                                                            <option value="Europe/Helsinki">Europe/Helsinki</option>
                                                            <option value="Europe/Isle_of_Man">Europe/Isle_of_Man</option>
                                                            <option value="Europe/Istanbul">Europe/Istanbul</option>
                                                            <option value="Europe/Jersey">Europe/Jersey</option>
                                                            <option value="Europe/Kaliningrad">Europe/Kaliningrad</option>
                                                            <option value="Europe/Kiev">Europe/Kiev</option>
                                                            <option value="Europe/Kirov">Europe/Kirov</option>
                                                            <option value="Europe/Lisbon">Europe/Lisbon</option>
                                                            <option value="Europe/Ljubljana">Europe/Ljubljana</option>
                                                            <option value="Europe/London">Europe/London</option>
                                                            <option value="Europe/Luxembourg">Europe/Luxembourg</option>
                                                            <option value="Europe/Madrid">Europe/Madrid</option>
                                                            <option value="Europe/Malta">Europe/Malta</option>
                                                            <option value="Europe/Mariehamn">Europe/Mariehamn</option>
                                                            <option value="Europe/Minsk">Europe/Minsk</option>
                                                            <option value="Europe/Monaco">Europe/Monaco</option>
                                                            <option value="Europe/Moscow">Europe/Moscow</option>
                                                            <option value="Europe/Oslo">Europe/Oslo</option>
                                                            <option value="Europe/Paris">Europe/Paris</option>
                                                            <option value="Europe/Podgorica">Europe/Podgorica</option>
                                                            <option value="Europe/Prague">Europe/Prague</option>
                                                            <option value="Europe/Riga">Europe/Riga</option>
                                                            <option value="Europe/Rome">Europe/Rome</option>
                                                            <option value="Europe/Samara">Europe/Samara</option>
                                                            <option value="Europe/San_Marino">Europe/San_Marino</option>
                                                            <option value="Europe/Sarajevo">Europe/Sarajevo</option>
                                                            <option value="Europe/Saratov">Europe/Saratov</option>
                                                            <option value="Europe/Simferopol">Europe/Simferopol</option>
                                                            <option value="Europe/Skopje">Europe/Skopje</option>
                                                            <option value="Europe/Sofia">Europe/Sofia</option>
                                                            <option value="Europe/Stockholm">Europe/Stockholm</option>
                                                            <option value="Europe/Tallinn">Europe/Tallinn</option>
                                                            <option value="Europe/Tirane">Europe/Tirane</option>
                                                            <option value="Europe/Ulyanovsk">Europe/Ulyanovsk</option>
                                                            <option value="Europe/Uzhgorod">Europe/Uzhgorod</option>
                                                            <option value="Europe/Vaduz">Europe/Vaduz</option>
                                                            <option value="Europe/Vatican">Europe/Vatican</option>
                                                            <option value="Europe/Vienna">Europe/Vienna</option>
                                                            <option value="Europe/Vilnius">Europe/Vilnius</option>
                                                            <option value="Europe/Volgograd">Europe/Volgograd</option>
                                                            <option value="Europe/Warsaw">Europe/Warsaw</option>
                                                            <option value="Europe/Zagreb">Europe/Zagreb</option>
                                                            <option value="Europe/Zaporozhye">Europe/Zaporozhye</option>
                                                            <option value="Europe/Zurich">Europe/Zurich</option>
                                                        </optgroup>
                                                        <optgroup label="Indian">
                                                            <option value="Indian/Antananarivo">Indian/Antananarivo
                                                            </option>
                                                            <option value="Indian/Chagos">Indian/Chagos</option>
                                                            <option value="Indian/Christmas">Indian/Christmas</option>
                                                            <option value="Indian/Cocos">Indian/Cocos</option>
                                                            <option value="Indian/Comoro">Indian/Comoro</option>
                                                            <option value="Indian/Kerguelen">Indian/Kerguelen</option>
                                                            <option value="Indian/Mahe">Indian/Mahe</option>
                                                            <option value="Indian/Maldives">Indian/Maldives</option>
                                                            <option value="Indian/Mauritius">Indian/Mauritius</option>
                                                            <option value="Indian/Mayotte">Indian/Mayotte</option>
                                                            <option value="Indian/Reunion">Indian/Reunion</option>
                                                        </optgroup>
                                                        <optgroup label="Pacific">
                                                            <option value="Pacific/Apia">Pacific/Apia</option>
                                                            <option value="Pacific/Auckland">Pacific/Auckland</option>
                                                            <option value="Pacific/Bougainville">Pacific/Bougainville
                                                            </option>
                                                            <option value="Pacific/Chatham">Pacific/Chatham</option>
                                                            <option value="Pacific/Chuuk">Pacific/Chuuk</option>
                                                            <option value="Pacific/Easter">Pacific/Easter</option>
                                                            <option value="Pacific/Efate">Pacific/Efate</option>
                                                            <option value="Pacific/Enderbury">Pacific/Enderbury</option>
                                                            <option value="Pacific/Fakaofo">Pacific/Fakaofo</option>
                                                            <option value="Pacific/Fiji">Pacific/Fiji</option>
                                                            <option value="Pacific/Funafuti">Pacific/Funafuti</option>
                                                            <option value="Pacific/Galapagos">Pacific/Galapagos</option>
                                                            <option value="Pacific/Gambier">Pacific/Gambier</option>
                                                            <option value="Pacific/Guadalcanal">Pacific/Guadalcanal
                                                            </option>
                                                            <option value="Pacific/Guam">Pacific/Guam</option>
                                                            <option value="Pacific/Honolulu">Pacific/Honolulu</option>
                                                            <option value="Pacific/Kiritimati">Pacific/Kiritimati</option>
                                                            <option value="Pacific/Kosrae">Pacific/Kosrae</option>
                                                            <option value="Pacific/Kwajalein">Pacific/Kwajalein</option>
                                                            <option value="Pacific/Majuro">Pacific/Majuro</option>
                                                            <option value="Pacific/Marquesas">Pacific/Marquesas</option>
                                                            <option value="Pacific/Midway">Pacific/Midway</option>
                                                            <option value="Pacific/Nauru">Pacific/Nauru</option>
                                                            <option value="Pacific/Niue">Pacific/Niue</option>
                                                            <option value="Pacific/Norfolk">Pacific/Norfolk</option>
                                                            <option value="Pacific/Noumea">Pacific/Noumea</option>
                                                            <option value="Pacific/Pago_Pago">Pacific/Pago_Pago</option>
                                                            <option value="Pacific/Palau">Pacific/Palau</option>
                                                            <option value="Pacific/Pitcairn">Pacific/Pitcairn</option>
                                                            <option value="Pacific/Pohnpei">Pacific/Pohnpei</option>
                                                            <option value="Pacific/Port_Moresby">Pacific/Port_Moresby
                                                            </option>
                                                            <option value="Pacific/Rarotonga">Pacific/Rarotonga</option>
                                                            <option value="Pacific/Saipan">Pacific/Saipan</option>
                                                            <option value="Pacific/Tahiti">Pacific/Tahiti</option>
                                                            <option value="Pacific/Tarawa">Pacific/Tarawa</option>
                                                            <option value="Pacific/Tongatapu">Pacific/Tongatapu</option>
                                                            <option value="Pacific/Wake">Pacific/Wake</option>
                                                            <option value="Pacific/Wallis">Pacific/Wallis</option>
                                                        </optgroup>
                                                        <optgroup label="UTC">
                                                            <option value="UTC">UTC</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Language <span
                                                            class="text-danger">*</span></label>
                                                    <select data-toggle="select2" title="Language" id="language"
                                                        name="language">
                                                        @foreach ($languages as $language)
                                                            <option
                                                                {{ isset($user_language->user_setting_value) ? ($language->code == $user_language->user_setting_value ? 'selected' : '') : '' }}
                                                                value="{{ $language->code }}">{{ $language->language }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->



                                        <div class="row">
                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Enabled </label>
                                                    <a href="#" data-bs-toggle="popover" data-bs-placement="top"
                                                        data-bs-trigger="focus"
                                                        data-bs-content="This deactivates the user">
                                                        <i class="uil uil-info-circle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="mb-3 text-sm-end">
                                                    <input type="hidden" name="user_enabled" value="false">
                                                    <input type="checkbox" id="user_enabled-switch" name="user_enabled"
                                                        @if ($user->user_enabled == 'true') checked @endif
                                                        data-switch="primary" />
                                                    <label for="user_enabled-switch" data-on-label="On"
                                                        data-off-label="Off"></label>
                                                    <div class="text-danger error_message user_enabled_err"></div>

                                                </div>
                                            </div>
                                        </div> <!-- end row -->


                                        @if (isSuperAdmin())
                                            <div class="row" id="domain_select_row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="domain_select" class="form-label">Select Domains that
                                                            user is allowed to manage <span class="text-danger">*</label>
                                                        <!-- Multiple Select -->
                                                        <select class="select2 form-control select2-multiple"
                                                            data-toggle="select2" multiple="multiple"
                                                            data-placeholder="Choose ..." id="domain_select"
                                                            name="domains[]">
                                                            @foreach ($all_domains as $domain)
                                                                <option value="{{ $domain->domain_uuid }}"
                                                                    @if (isset($assigned_domains) && $assigned_domains->contains($domain)) selected @endif>
                                                                    @if (isset($domain->domain_description))
                                                                        {{ $domain->domain_description }}
                                                                    @else
                                                                        {{ $domain->domain_name }}
                                                                    @endif

                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error_message reseller_domain_err"></div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="domain_groups_select" class="form-label">Select Domain
                                                            Groups that user is allowed to manage <span
                                                                class="text-danger">*</label>
                                                        <!-- Multiple Select -->
                                                        <select class="select2 form-control select2-multiple"
                                                            data-toggle="select2" multiple="multiple"
                                                            data-placeholder="Choose ..." id="domain_groups_select"
                                                            name="domain_groups[]">
                                                            @foreach ($all_domain_groups as $domain_group)
                                                                <option value="{{ $domain_group->domain_group_uuid }}"
                                                                    @if (isset($assigned_domain_groups) && $assigned_domain_groups->contains($domain_group)) selected @endif>
                                                                    {{ $domain_group->group_name }}

                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="text-danger error_message reseller_domain_err"></div>
                                                    </div>
                                                </div>
                                            </div> <!-- end row -->
                                        @endif

                                        @if (isSuperAdmin())
                                            @stack ('api')
                                        @endif

                                        <div class="row mt-4">
                                            <div class="col-sm-12">
                                                <div class="text-sm-end">
                                                    <input type="hidden" name="user_uuid"
                                                        value="{{ $user->user_uuid }}">
                                                    {{-- <input type="hidden" name="contact_id" value="{{base64_encode($contact['contact_uuid'])}}"> --}}
                                                    <a href="{{ Route('users.index') }}"
                                                        class="btn btn-light me-2">Cancel</a>
                                                    <button id="submitFormButton" class="btn btn-success"
                                                        type="submit">Save</button>
                                                    {{-- <button class="btn btn-success" type="submit">Save</button> --}}
                                                </div>
                                            </div> <!-- end col -->
                                        </div>

                                        </form>
                                    </div>
                                </div> <!-- end row-->

                            </div>
                            @if (userCheckPermission('user_setting_view') && $user->exists)
                                <div class="tab-pane " id="setting">
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="text-xl-end mt-xl-0 mt-2">
                                                <button class="btn btn-success mb-2 me-2" id="add_setting_button">Add
                                                    Setting</button>
                                                <a href="javascript:confirmDeleteAction('{{ route('users.settings.destroy', ':id') }}');"
                                                    id="deleteMultipleActionButton"
                                                    class="btn btn-danger mb-2 me-2 disabled">Delete Selected</a>
                                            </div>
                                        </div><!-- end col-->
                                    </div>


                                    <div class="table-responsive">
                                        <table class="table table-centered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 20px;">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input"
                                                                id="selectallCheckbox">
                                                            <label class="form-check-label"
                                                                for="customCheck1">&nbsp;</label>
                                                        </div>
                                                    </th>
                                                    <th>Category</th>
                                                    <th>Subcategory</th>
                                                    <th>Type</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                    <th>Description</th>
                                                    <th style="width: 125px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                @foreach ($settings as $key => $setting)
                                                    <tr id="id{{ $setting->user_setting_uuid }}">
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox" name="action_box[]"
                                                                    value="{{ $setting->user_setting_uuid }}"
                                                                    class="form-check-input action_checkbox">
                                                                <label class="form-check-label">&nbsp;</label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            {{ $setting['user_setting_category'] }}
                                                        </td>
                                                        <td>
                                                            {{ $setting['user_setting_subcategory'] }}
                                                        </td>
                                                        <td>
                                                            {{ $setting['user_setting_name'] }}
                                                        </td>
                                                        <td>
                                                            {{ $setting['user_setting_value'] }}
                                                        </td>
                                                        <td>
                                                            @if ($setting['user_setting_enabled'] == 't')
                                                                <h5><span class="badge bg-success"></i>Enabled</span></h5>
                                                            @else
                                                                <h5><span class="badge bg-warning">Disabled</span></h5>
                                                            @endif
                                                        </td>

                                                        <td>
                                                            {{ $setting['user_setting_description'] }}
                                                        </td>
                                                        <td>
                                                            {{-- Action Buttons --}}
                                                            <div id="tooltip-container-actions">
                                                                <a href="javascript:confirmDeleteAction('{{ route('users.settings.destroy', ':id') }}','{{ $setting->user_setting_uuid }}');"
                                                                    class="action-icon">
                                                                    <i class="mdi mdi-delete"
                                                                        data-bs-container="#tooltip-container-actions"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-placement="bottom" title="Delete"></i>
                                                                </a>
                                                            </div>
                                                            {{-- End of action buttons --}}
                                                        </td>
                                                    </tr>
                                                @endforeach

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>




                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col -->
        </div>
        <!-- end row-->

    </div> <!-- container -->

    <div class="modal fade" id="settingModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myLargeModalLabel">Add Setting</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <form id="setting_form" method="post" action="javascript:void(0)">
                        @CSRF
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_setting_category" class="form-label">Category <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" type="text" value=""
                                        placeholder="Enter category" name="user_setting_category"
                                        id="user_setting_category" />
                                    <div class="text-danger error_message user_setting_category_err"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_setting_subcategory" class="form-label">Subcategory <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" value="" type="text"
                                        placeholder="Enter your subcategory" id="user_setting_subcategory"
                                        name="user_setting_subcategory" />
                                    <div class="text-danger error_message user_setting_subcategory_err"></div>
                                </div>
                            </div>
                        </div> <!-- end row -->

                        <div class="row">

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_setting_name" class="form-label">Type<span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" value="" type="text" placeholder="Enter type"
                                        id="user_setting_name" name="user_setting_name" />
                                    <div class="text-danger error_message user_setting_name_err"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_setting_value" class="form-label">Value <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" value="" type="text" placeholder="Enter value"
                                        id="user_setting_value" name="user_setting_value" />
                                    <div class="text-danger error_message user_setting_value_err"></div>
                                </div>
                            </div>

                        </div> <!-- end row -->

                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Enabled <span class="text-danger">*</label>
                                    {{-- <a href="#"  data-bs-toggle="popover" data-bs-placement="top" data-bs-trigger="focus"
                                                        data-bs-content="This deactivates the user">
                                                        <i class="uil uil-info-circle"></i> --}}
                                    </a>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="mb-3 text-sm-end">
                                    <input type="hidden" name="user_setting_enabled" value="f">
                                    <input type="checkbox" id="user_setting_enabled-switch" checked
                                        name="user_setting_enabled" data-switch="primary" />
                                    <label for="user_setting_enabled-switch" data-on-label="On"
                                        data-off-label="Off"></label>
                                    <div class="text-danger error_message user_setting_enabled_err"></div>

                                </div>
                            </div>
                        </div> <!-- end row -->


                        <div class="row">
                            <div class="col-md-12">
                                <label for="user_setting_description" class="form-label">Description</label>
                                <textarea class="form-control" name="user_setting_description" id="user_setting_description"></textarea>
                                <div class="text-danger error_message user_setting_description_err"></div>
                            </div>
                        </div>


                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <div class="text-sm-end">
                                    <input type="hidden" name="user_id" value="{{ $user->user_uuid }}">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                                        aria-hidden="true">Close</button>
                                    <button id="submitSettingFormButton" class="btn btn-danger" type="submit">Save
                                    </button>
                                </div>
                            </div> <!-- end col -->
                        </div>

                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection


@push('scripts')
    <script>
        var setting_validation;
        document.addEventListener('DOMContentLoaded', function() {
            //Assign a value to Language field
            $('#time_zone').val($('#time_zone_val').val());
            $('#time_zone').trigger('change');
            $('#groups-select').trigger('change');
            resellerDomainSelectUpdate();

            $('a[data-bs-toggle="tab"]').on('show.bs.tab', function(e) {
                localStorage.setItem('activeTab', $(e.target).attr('href'));
            });

            var activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                $('#userNavTabs a[href="' + activeTab + '"]').tab('show');
            }

            $('#groups-select').change(function() {
                resellerDomainSelectUpdate();
            });

            function resellerDomainSelectUpdate() {
                // Get all groups with domain select permission
                var domain_select_groups = "{{ $domain_select_groups }}";
                domain_select_groups = jQuery.parseJSON(domain_select_groups.replace(/&quot;/g, '"'));

                var reseller_select_show = false;
                $('#groups-select option:selected').each(function() {
                    if (domain_select_groups.includes($(this).val())) {
                        reseller_select_show = true;
                    }
                });
                if (reseller_select_show) {
                    $('#domain_select_row').show();
                } else {
                    $('#domain_select_row').hide();
                }
            }


            $('#submitSettingFormButton').on('click', function(e) {
                e.preventDefault();
                $('.loading').show();

                var url = '{{ route('users.settings.store', ':id') }}';
                url = url.replace(':id', "{{ $user->user_uuid }}");

                $.ajax({
                        type: 'POST',
                        url: url,
                        cache: false,
                        data: $("#setting_form").serialize(),
                    })
                    .done(function(response) {
                        //console.log(response);
                        $('#settingModal').modal('hide');
                        // $('.loading').hide();

                        if (response.error) {
                            printErrorMsg(response.error);

                        } else {
                            $.NotificationApp.send("Success", response.message, "top-right", "#10c469",
                                "success");
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    })
                    .fail(function(response) {
                        $('#settingModal').modal('hide');
                        $('.loading').hide();
                        printErrorMsg(response.error);
                    });
            })


            $('#submitFormButton').on('click', function(e) {
                e.preventDefault();
                $('.loading').show();

                //Reset error messages
                $('.error_message').text("");

                $.ajax({
                        type: "POST",
                        url: $('#user_form').attr('action'),
                        cache: false,
                        data: $("#user_form").serialize(),
                    })
                    .done(function(response) {
                        //console.log(response);
                        $('.loading').hide();

                        if (response.error) {
                            printErrorMsg(response.error);

                        } else {
                            $.NotificationApp.send("Success", response.message, "top-right", "#10c469",
                                "success");
                            setTimeout(function() {
                                window.location.href = "{{ route('users.index') }}";
                            }, 1000);

                        }
                    })
                    .fail(function(response) {
                        $('.loading').hide();
                        printErrorMsg(response.responseText);
                    });

            })

            $('#add_setting_button').on('click', function(e) {
                //setting_validation.resetForm();
                $('#settingModal').modal('show');
            });

        });
    </script>
@endpush
