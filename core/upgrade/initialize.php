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
  Tim Fry <tim@voipstratus.com>
 */

	/*
	 * This is designed to make an empty postgresql fusionpbx database usuable with core/upgrade/upgrade.php
	 */

	// read the environment file from /etc/fusionpbx/config.conf
	$settings = parse_ini_file('/etc/fusionpbx/config.conf');

	// database connection and type (dsn)
	define('DB_TYPE', $settings['database.0.type']);
	define('DB_HOST', $settings['database.0.host']);
	define('DB_PORT', $settings['database.0.port']);
	define('DB_NAME', $settings['database.0.name']);
	define('DB_USERNAME', $settings['database.0.username']);
	define('DB_PASSWORD', $settings['database.0.password']);

	if(empty($settings['init.domain.name'])) $settings['init.domain.name'] = 'localhost';
	if(empty($settings['init.admin.name']))  $settings['init.admin.name'] = 'admin';
	if(empty($settings['init.admin.password'])) $settings['init.admin.password'] = 'password';

	// initial settings to use for admin login and password
	define('DOMAIN_NAME',    $settings['init.domain.name']);
	define('ADMIN_NAME',     $settings['init.admin.name']);
	define('ADMIN_PASSWORD', $settings['init.admin.password']);
	
	// directory structure
	define('BASE_DIR',$settings['document.root']);
	define('CORE_DIR',BASE_DIR . '/core');
	define('APP_DIR' ,BASE_DIR . '/app');

	//set include path
	set_include_path(BASE_DIR);

	//load the uuid function
	require BASE_DIR .'/resources/functions.php';

	function connect() {
		$tries = 0;
		while($tries++ < 10) {
			//test for v_domains to exist
			try {
				$con = new \PDO(DB_TYPE.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USERNAME, DB_PASSWORD);
				return $con;
			} catch (Exception $ex) {
				sleep(1);
			}
		}
		die('Unable to connect after 10 tries');
	}

	// checks for a table to exist or not assuming this is a postgres connection
	function has_table($con, $table_name, $schema = "public") {
		$statement = $con->prepare("SELECT COUNT(*)"
			. " FROM information_schema.tables"
			. " WHERE table_schema LIKE '$schema' AND"
			. " table_type LIKE 'BASE_TABLE' AND"
			. " table_name = :table_name"
			. " LIMIT 1");
		$success = $statement->execute(['table_name' => $table_name]);
		if ($success !== false) {
			$result = $statement->fetchAll(PDO::FETCH_COLUMN);
			if (!empty($result) && count($result) > 0 && $result[0] === 1)
				return true;
		}
		return false;
	}

	function group_exists($con, $group_name, $schema = 'public') {
		$statement = $con->prepare("SELECT COUNT(*)"
			. " FROM $schema.v_groups"
			. " WHERE group_name = :group_name");
		$success = $statement->execute(['group_name' => $group_name]);
		if($success !== false) {
			$result = $statement->fetchAll(PDO::FETCH_COLUMN);
			if(!empty($result) && count($result) > 0 && $result[0] >= 1) {
				return true;
			}
		}
		return false;
	}

	function group_uuid($con, $group_name, $schema = 'public') {
		$statement = $con->prepare("SELECT g.group_uuid"
			. " FROM $schema.v_groups g"
			. " WHERE g.group_name = :group_name"
			. " AND g.domain_uuid is null"
			. " LIMIT 1");
		$success = $statement->execute(['group_name' => $group_name]);
		if($success !== false) {
			$result = $statement->fetchAll(PDO::FETCH_COLUMN);
			if(!empty($result)) {
				return $result[0];
			}
		}
		return null;
	}

	/**
	 * Execute a statement and return a value if fetch_type is set
	 * @param type $con
	 * @param type $sql
	 * @param int|null $fetch_type
	 * @return bool
	 */
	function db_execute($con, $sql, ?int $fetch_type = null) {
		//allow sql commands to fail without crashing
		$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//prepare the sql statement
		$statement = $con->prepare($sql);
		//execute the statement
		$result = $statement->execute();
		if($result !== false) {
			if($fetch_type === null)
				return;
			else
				switch($fetch_type) {
					case PDO::FETCH_COLUMN:
						return $statement->fetchColumn();
					case PDO::FETCH_ASSOC:
						return $statement->fetchAll(PDO::FETCH_ASSOC);
					default:
						return $statement->fetch($fetch_type);
				}
		}
		return false;
	}

	/**
	 * Read a schema array from an existing FusionPBX app_config style file
	 * @param type $path full directory path
	 * @param type $file if not provided default is app_config.php
	 * @return type
	 */
	function get_schema_from_app_config($path, $file = 'app_config.php') {
		$x = 0;
		$config = $path . '/' . $file;
		if(file_exists($config))
			require $config;
		if(!empty($apps) && is_array($apps) && count($apps) > 0) {
			if(!empty($apps[0]['db'])) {
				return $apps[0]['db'];
			}
		}
		return null;
	}

	/**
	 * Writes a FusionPBX app_config.php style schema array to the database
	 * @param type $con PDO connection
	 * @param type $schema FusionPBX app_config.php style schema array
	 */
	function write_schema($con, $schema) {
		if(empty($schema))
			return;
		if(!is_array($schema))
			return;
		foreach($schema as $table) {
			$table_name = $table['table']['name'];
			if(is_array($table_name)) {
				$table_name = $table['table']['name']['text'];
			}
			$sql = "create table if not exists $table_name (";
			if(!empty($table['fields'])) {
				foreach($table['fields'] as $field) {
					if(isset($field['deprecated']) && $field['deprecated'] == true)
						continue;
					$field_name = $field['name'];
					if(is_array($field_name)) {
						$field_name = $field['name']['text'];
					}
					$field_type = $field['type'];
					if(is_array($field_type)) {
						$field_type = $field_type['pgsql'];
					}
					$sql .= "$field_name $field_type";
					if(!empty($field['key']['type'])) {
						$field_key_type = $field['key']['type'];
						if($field_key_type === 'primary') {
							$sql .= " primary key";
						}
						if($field_key_type === 'foreign') {
							$foreign_key_table = $field['key']['reference']['table'];
							$foreign_key_field = $field['key']['reference']['field'];
						}
					}
					$sql .= ",";
				}
				if(substr($sql, -1) === ",") {
					$sql = substr($sql, 0, strlen($sql)-1);
				}
			}
			$sql .= ")";
			db_execute($con, $sql);
		}
	}

	/**
	 * Reads a default_setting_value for a switch setting from the database
	 * @param type $con	PDO connection
	 * @param type $subcategory switch setting
	 * @return type
	 */
	function get_switch_setting($con, $subcategory) {
		return db_execute($con, "select default_setting_value from v_default_settings"
		. " where default_setting_category='switch' and default_setting_subcategory='$subcategory'", 7);
	}

	function enable_switch_setting($con, $uuid) {
		db_execute($con, "update v_default_settings"
			. " set default_setting_enabled = true"
			. " where default_setting_uuid = '$uuid'");
	}

	/**
	 * Writes a default_setting_value for a switch setting to the database
	 * @param type $con PDO connection
	 * @param string $uuid must be a valid UUID type
	 * @param string $subcategory switch setting
	 * @param string $value value to store in database
	 */
	function put_switch_setting($con, $uuid, $subcategory, $value) {
		db_execute($con, "insert into v_default_settings("
			. "default_setting_uuid"
			. ",default_setting_category"
			. ",default_setting_subcategory"
			. ",default_setting_name"
			. ",default_setting_value"
			. ",default_setting_enabled"
			. ") values ("
			. "'$uuid'"
			. ",'switch'"
			. ",'$subcategory'"
			. ",'dir'"
			. ",'$value'"
			. ",true"
			. ")"
			);
	}


	function make_directory($directory) {
		if(!file_exists($directory))
			mkdir($directory);
	}

	// checks for the dsn pre-process connector in the database
	function dsn_exists($con) {
		return ((int)db_execute($con, "select count(var_uuid) from v_vars where var_category='DSN' and var_name='db_dsn' and var_enabled='true'", PDO::FETCH_COLUMN) > 0);
	}

	function rewrite_event_socket_config() {
	//ensure the sip profile directories are present
	make_directory('/etc/freeswitch/sip_profiles');
	make_directory('/etc/freeswitch/sip_profiles/internal');
	make_directory('/etc/freeswitch/sip_profiles/external');
	make_directory('/etc/freeswitch/autoload_configs');

/////////////////// RAW DATA START //////////////////////
	$data = <<<EOF
<configuration name="sofia.conf" description="sofia Endpoint">

  <global_settings>
    <param name="log-level" value="0"/>
    <!-- <param name="auto-restart" value="false"/> -->
    <param name="debug-presence" value="0"/>
    <!-- <param name="capture-server" value="udp:homer.domain.com:5060"/> -->
  </global_settings>

  <!--
      The rabbit hole goes deep.  This includes all the
      profiles in the sip_profiles directory that is up
      one level from this directory.
  -->
  <profiles>
    <X-PRE-PROCESS cmd="include" data="../sip_profiles/*.xml"/>
  </profiles>

</configuration>
EOF;
/////////////////// RAW DATA END //////////////////////
	if(!file_exists('/etc/freeswitch/autoload_configs/sofia.conf.xml'))
		file_put_contents('/etc/freeswitch/autoload_configs/sofia.conf.xml', $data);
/////////////////// RAW DATA START //////////////////////
	$data = <<<'EOF'
<?xml version="1.0"?>
<!--
    NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE

    This is the FreeSWITCH default config.  Everything you see before you now traverses
    down into all the directories including files which include more files.  The default
    config comes out of the box already working in most situations as a PBX.  This will
    allow you to get started testing and playing with various things in FreeSWITCH.

    Before you start to modify this default please visit this wiki page:

    http://wiki.freeswitch.org/wiki/Getting_Started_Guide#Some_stuff_to_try_out.21

    If all else fails you can read our FAQ located at:

    http://wiki.freeswitch.org/wiki/FreeSwitch_FAQ

    NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE
-->
<document type="freeswitch/xml">
  <!--#comment
      All comments starting with #command will be preprocessed and never sent to the xml parser
      Valid instructions:
      #include ==> Include another file to this exact point
                   (partial xml should be encased in <include></include> tags)
      #set     ==> Set a global variable (can be expanded during preprocessing with $$ variables)
                   (note the double $$ which denotes preprocessor variables)
      #comment ==> A general comment such as this

      The preprocessor will compile the full xml document to ${prefix}/log/freeswitch.xml.fsxml
      Don't modify it while freeswitch is running cos it is mem mapped in most cases =D

      The same can be achieved with the <X-PRE-PROCESS> tag where the attrs 'cmd' and 'data' are
      parsed in the same way.
  -->
  <!--#comment
      vars.xml contains all the #set directives for the preprocessor.
  -->
  <X-PRE-PROCESS cmd="include" data="vars.xml"/>

  <section name="configuration" description="Various Configuration">
    <X-PRE-PROCESS cmd="include" data="autoload_configs/*.xml"/>
  </section>

  <section name="dialplan" description="Regex/XML Dialplan">
    <X-PRE-PROCESS cmd="include" data="dialplan/*.xml"/>
  </section>

  <section name="chatplan" description="Regex/XML Chatplan">
    <X-PRE-PROCESS cmd="include" data="chatplan/*.xml"/>
  </section>

  <!-- mod_dingaling is reliant on the vcard data in the "directory" section. -->
  <!-- mod_sofia is reliant on the user data for authorization -->
  <section name="directory" description="User Directory">
    <X-PRE-PROCESS cmd="include" data="directory/*.xml"/>
  </section>

  <!-- languages section -->
  <section name="languages" description="Language Management">
    <X-PRE-PROCESS cmd="include" data="languages/de/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/en/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/es/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/fr/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/ru/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/he/*.xml"/>
    <X-PRE-PROCESS cmd="include" data="languages/pt/*.xml"/>
  </section>
</document>
EOF;
/////////////////// RAW DATA END //////////////////////
	if(!file_exists('/etc/freeswitch/freeswitch.xml'))
		file_put_contents('/etc/freeswitch/freeswitch.xml', $data);
/////////////////// RAW DATA START //////////////////////
		$data = <<<'EOF'
<configuration name="event_socket.conf" description="Socket Client">
        <settings>
                <param name="nat-map" value="false" />
                <param name="listen-ip" value="0.0.0.0" />
                <param name="listen-port" value="8021" />
                <param name="password" value="ClueCon" />
                <param name="apply-inbound-acl" value="any_v4.auto" />
        </settings>
</configuration>
EOF;
/////////////////// RAW DATA END //////////////////////

		//always replace contents
		file_put_contents('/etc/freeswitch/autoload_configs/event_socket.conf.xml', $data);
	}

	function rewrite_modules_conf() {
/////////////////// RAW DATA START //////////////////////
		$data = <<<'EOF'
<configuration name="modules.conf" description="Modules">
        <modules>

                <!-- Applications -->
                <load module="mod_commands"/>
                <load module="mod_memcache"/>

                <!-- Languages -->
                <load module="mod_lua"/>

                <!-- Endpoints -->
                <load module="mod_sofia"/>

                <!-- Loggers -->
                <load module="mod_logfile"/>
                <load module="mod_console"/>

                <!-- Applications -->
                <load module="mod_callcenter"/>
                <load module="mod_fifo"/>
                <load module="mod_sms"/>
                <load module="mod_fsv"/>
                <load module="mod_esf"/>
                <load module="mod_expr"/>
                <load module="mod_dptools"/>
                <load module="mod_enum"/>
                <load module="mod_valet_parking"/>
                <load module="mod_spandsp"/>
                <load module="mod_db"/>
                <load module="mod_hash"/>
                <load module="mod_conference"/>

                <!-- Auto -->

                <!-- Codecs -->
                <load module="mod_g729"/>
                <load module="mod_g723_1"/>
                <load module="mod_bv"/>
                <load module="mod_amr"/>
                <load module="mod_h26x"/>

                <!-- Dialplan Interfaces -->
                <load module="mod_dialplan_xml"/>

                <!-- Endpoints -->
                <load module="mod_loopback"/>

                <!-- Event Handlers -->
                <load module="mod_event_socket"/>

                <!-- File Format Interfaces -->
                <load module="mod_sndfile"/>
                <load module="mod_native_file"/>

                <!-- Say -->
                <load module="mod_say_en"/>
                <load module="mod_say_zh"/>
                <load module="mod_say_ru"/>
                <load module="mod_say_fr"/>
                <load module="mod_say_th"/>
                <load module="mod_say_he"/>
                <load module="mod_say_pt"/>
                <load module="mod_say_de"/>
                <load module="mod_say_it"/>
                <load module="mod_say_nl"/>
                <load module="mod_say_es"/>
                <load module="mod_say_hu"/>

                <!-- Speech Recognition / Text to Speech -->
                <load module="mod_flite"/>
                <load module="mod_tts_commandline"/>

                <!-- Streams / Files -->
                <load module="mod_local_stream"/>
                <load module="mod_tone_stream"/>
                <load module="mod_shout"/>

                <!-- XML Interfaces -->
                <load module="mod_xml_cdr"/>

        </modules>
</configuration>
EOF;
	/////////////////// RAW DATA END //////////////////////
		//if(!file_exists('/etc/freeswitch/autoload_configs/.modules_copied')) {
			file_put_contents('/etc/freeswitch/autoload_configs/modules.conf.xml', $data);
		//	shell_exec('touch /etc/freeswitch/autoload_configs/.modules_copied');
		//}
	}

	function rewrite_lua_conf() {
	/////////////////// RAW DATA START //////////////////////
	$data = <<<'EOF'
<configuration name="lua.conf" description="LUA Configuration">
  <settings>

    <!--
    Specify local directories that will be searched for LUA modules
    These entries will be pre-pended to the LUA_CPATH environment variable
    -->
    <!--
    <param name="module-directory" value="/usr/local/lib/lua/5.2/?.so"/>
    <param name="module-directory" value="/usr/local/lib/lua/5.2/?"/>
    <param name="module-directory" value="/usr/lib/x86_64-linux-gnu/lua/5.2/?.so"/>
    -->

    <!--
    Specify local directories that will be searched for LUA scripts
    These entries will be pre-pended to the LUA_PATH environment variable
    -->
    <!-- <param name="script-directory" value="/usr/local/lua/?.lua"/> -->
    <param name="script-directory" value="$${script_dir}/?.lua"/>

    <!--
    Deliver XML from lua with the XML Handler
    -->
    <param name="xml-handler-script" value="/var/www/fusionpbx/app/switch/resources/scripts/app.lua xml_handler"/>
    <param name="xml-handler-bindings" value="configuration,dialplan,directory,languages"/>

    <!--
    The following options identifies a lua script that is launched
    at startup and may live forever in the background.
    You can define multiple lines, one for each script you
    need to run.
    -->

    <!-- FusionPBX: Support BLF for call flow -->
    <!-- There 2 way to handle this
      1 - Monitor - ignore SUBSCRIBE and just send NOTIFY each X seconds
      2 - Event handler - handle each SUBSCRIBE request
    -->
    <!--<param name="startup-script" value="call_flow_monitor.lua"/>-->
    <!--<param name="startup-script" value="blf_subscribe.lua flow"/>-->

    <!-- FusionPBX: Support BLF for DND -->
    <!--<param name="startup-script" value="blf_subscribe.lua dnd"/>-->

    <!-- FusionPBX: Support BLF for Call Forward -->
    <!--<param name="startup-script" value="blf_subscribe.lua forward"/>-->

    <!-- FusionPBX: Support BLF for Call Center Agents -->
    <!--<param name="startup-script" value="blf_subscribe.lua agent"/>-->

    <!-- FusionPBX: Support BLF for Voicemail -->
    <!--<param name="startup-script" value="blf_subscribe.lua voicemail"/>-->

    <!-- FusionPBX: Support MWI indicator-->
    <!-- There 2 way to handle this
      1 - Monitor - ignore SUBSCRIBE and just send NOTIFY each X seconds
      2 - Event handler - handle each SUBSCRIBE request
    -->
    <!--<param name="startup-script" value="app/voicemail/resources/scripts/mwi.lua"/>-->
    <!--<param name="startup-script" value="app/voicemail/resources/scripts/mwi_subscribe.lua"/>-->

    <!-- Subscribe to events -->
    <!--<hook event="PHONE_FEATURE_SUBSCRIBE" subclass="" script="app.lua feature_event"/>-->
  </settings>
</configuration>
EOF;
	/////////////////// RAW DATA END //////////////////////
		$file = '/etc/freeswitch/autoload_configs/lua.conf.xml';
		file_put_contents($file, $data);
	}

	//startup
	echo "+-----------------+\n";
	echo "|+---------------+|\n";
	echo "||  STARTING UP  ||\n";
	echo "|+---------------+|\n";
	echo "+-----------------+\n";

	//give extra time for freeswitch to be running
	//sleep(10);

	//rewrite the event_socket_configuration
	rewrite_event_socket_config();

	//connect to database and check needed values
	$con = connect();
	if(!has_table($con, 'v_domains')) {
		echo "Creating v_domains\n";
		write_schema($con, get_schema_from_app_config(CORE_DIR . '/domains'));
		db_execute($con, "insert into v_domains("
			. "domain_uuid"
			. ",domain_name"
			. ",domain_enabled"
			. ") values ("
			. "'" . uuid() . "'"
			. ",'". DOMAIN_NAME . "'"
			. ",true"
			. ")");
	}
	$domain_uuid = db_execute($con, "select domain_uuid from v_domains where domain_name='" . DOMAIN_NAME . "'", 7);

	if(empty($domain_uuid)) {
		$domain_uuid = uuid();
	}

	if(!has_table($con, 'v_vars')) {
		echo "Creating v_vars\n";
		write_schema($con, get_schema_from_app_config(APP_DIR . '/vars'));
	}

	if(!has_table($con, 'v_users')) {
		echo "Creating v_users\n";
		write_schema($con, get_schema_from_app_config(CORE_DIR . '/users'));
		db_execute($con, "insert into v_users("
			. "user_uuid"
			. ",domain_uuid"
			. ",username"
			. ",password"
			. ",user_enabled"
			. ") values ("
			. "'" . uuid() . "'"
			. ",'$domain_uuid'"
			. ",'" . ADMIN_NAME . "'"
			. ",'" . password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT) . "'"
			. ",'true'"
			. ")");
	}

	$sadmin_uuid = db_execute($con, "select user_uuid from v_users where username='admin'", 7);
	
	if(!has_table($con, 'v_groups')) {
		echo "Creating v_groups\n";
		write_schema($con, get_schema_from_app_config(CORE_DIR . '/groups'));
	}

	if(empty(group_uuid($con, 'superadmin'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','superadmin',80)");
	}
	if(empty(group_uuid($con, 'admin'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','admin',50)");
	}
	if(empty(group_uuid($con, 'supervisor'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','supervisor',40)");
	}
	if(empty(group_uuid($con, 'user'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','user',30)");
	}
	if(empty(group_uuid($con, 'agent'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','agent',20)");
	}
	if(empty(group_uuid($con, 'public'))) {
		db_execute($con, "insert into v_groups(group_uuid,group_name,group_level) values ('" . uuid() . "','public',10)");
	}

	$sadmin_group_uuid = db_execute($con, "select group_uuid from v_groups where group_name='superadmin'",7);
	$user_group_uuid = db_execute($con, "select user_group_uuid from v_user_groups"
		. " where domain_uuid='$domain_uuid' and group_uuid='$sadmin_group_uuid' and user_uuid='$sadmin_uuid'", 7);

	if(empty($user_group_uuid)) {
		db_execute($con, "insert into v_user_groups("
			. "user_group_uuid"
			. ",domain_uuid"
			. ",group_name"
			. ",group_uuid"
			. ",user_uuid"
			. ") values ("
			. "'" . uuid() . "'"
			. ",'$domain_uuid'"
			. ",'superadmin'"
			. ",'$sadmin_group_uuid'"
			. ",'$sadmin_uuid'"
			. ")");
	}

	if(!has_table($con, 'v_software')) {
		echo "Creating v_software\n";
		write_schema($con, get_schema_from_app_config(CORE_DIR.'/software'));
	}
	
	if(!has_table($con, 'v_default_settings')) {
		echo "Creating v_default_settings\n";
		write_schema($con, get_schema_from_app_config(CORE_DIR.'/default_settings'));
	}

	$base = get_switch_setting($con, 'base');
	if(is_null($base) || $base === false) {
		put_switch_setting($con, '09e2eed0-0254-4e57-860c-f281491927c8' ,'base', '');
	}

	if(empty(get_switch_setting($con, 'bin'))) {
		put_switch_setting($con, '330d837a-b8bf-4fe8-afbf-413073c4ff24' ,'bin', '/usr/bin/freeswitch');
	}

	if(empty(get_switch_setting($con, 'conf'))) {
		put_switch_setting($con, '5c0413bb-530c-44fb-a12c-1dab02066a3c' ,'conf', '/etc/freeswitch');
	}

	if(empty(get_switch_setting($con, 'db'))) {
		put_switch_setting($con, '45ba4ffe-b303-4d64-b94a-1801ac4177f8' ,'db', '/var/lib/freeswitch/db');
	}

	if(empty(get_switch_setting($con, 'grammar'))) {
		put_switch_setting($con, '1b5a5dbe-7061-444d-a701-ed1b634468fa' ,'grammar', '/var/lib/freeswitch/grammar');
	}

	if(empty(get_switch_setting($con, 'log'))) {
		put_switch_setting($con, '1660dde2-6931-41da-94ec-bd78352b5eb1' ,'log', '/var/log/freeswitch');
	}

	if(empty(get_switch_setting($con, 'mod'))) {
		put_switch_setting($con, '1e76373d-7362-4381-b343-963e39f8c5e3' ,'mod', '/usr/lib/freeswitch/mod');
	}

	if(empty(get_switch_setting($con, 'languages'))) {
		put_switch_setting($con, '1366d3ce-3399-437a-958b-e7f2fba1f716' ,'languages', '/etc/freeswitch/languages');
	}
	if(empty(get_switch_setting($con, 'recordings'))) {
		put_switch_setting($con, '0440f651-dd9e-46b2-ad50-fd1252486210' ,'recordings', '/var/lib/freeswitch/recordings');
	}
	if(empty(get_switch_setting($con, 'scripts'))) {
		put_switch_setting($con, 'fae9105c-c64a-4534-b7b0-d95da1c988c2' ,'scripts', '/usr/share/freeswitch/scripts');
	}
	if(empty(get_switch_setting($con, 'sip_profiles'))) {
		put_switch_setting($con, '874bed95-6c3b-4fe0-be8a-ea2dcecac109' ,'sip_profiles', '/etc/freeswitch/sip_profiles');
	}
	if(empty(get_switch_setting($con, 'sounds'))) {
		put_switch_setting($con, 'b332acc0-c48f-41d2-8d5d-e5452c1cfd86' ,'sounds', '/usr/share/freeswitch/sounds');
	}
	if(empty(get_switch_setting($con, 'storage'))) {
		put_switch_setting($con, 'b21f3949-e75e-459d-8add-3c73ba72e6cc' ,'storage', '/var/lib/freeswitch/storage');
	}
	if(empty(get_switch_setting($con, 'voicemail'))) {
		put_switch_setting($con, 'ba3ac900-245c-4cff-a191-829137db47d8' ,'voicemail', '/var/lib/freeswitch/storage/voicemail');
	}
	if(empty(get_switch_setting($con, 'extensions'))) {
		put_switch_setting($con, '04e0ea1c-dc2c-4377-bee9-39adb61f2c66', 'extensions', '/etc/freeswitch/directory');
	}

	$new_install = false;
	//move the fusionpbx version of the config to the freeswitch config
	if(!file_exists('/etc/freeswitch/.copied')) {
		$new_install = true;
		//move fusionpbx template files in
		shell_exec('rm -Rf /etc/freeswitch/*');
		shell_exec('cp -R /var/www/fusionpbx/resources/templates/conf/* /etc/freeswitch/');
		shell_exec('touch /etc/freeswitch/.copied');
	}

	//rewrite the lua_conf_xml
	rewrite_lua_conf();

	//rewrite the event_socket_configuration
	rewrite_event_socket_config();

	try {
		// consume the output
		ob_start();
		include CORE_DIR . '/upgrade/upgrade.php';
		ob_end_clean();
	} catch (\Throwable $t) {

	}
	// inject the DSN in to the database
	if(!dsn_exists($con)) {
		echo "\n\n";
		echo "+----------------------------------------+\n";
		echo "| CREATING DSN CONNECTOR FOR FreeSWITCH. |\n";
		echo "|    FreeSWITCH MUST BE RESTARTED!       |\n";
		echo "+----------------------------------------+\n";
		echo "\n\n";
		$db_dsn = DB_TYPE."://hostaddr=".DB_HOST . " port=". DB_PORT . " dbname=".DB_NAME. " user=".DB_USERNAME. " password=".DB_PASSWORD;
		db_execute($con, "insert into v_vars("
			. "var_uuid"
			. ",var_category"
			. ",var_name"
			. ",var_value"
			. ",var_command"
			. ",var_enabled"
			. ",var_order) values ("
			. "'" . uuid() . "'"
			. ",'DSN'"
			. ",'db_dsn'"
			. ",'" . DB_TYPE."://hostaddr=".DB_HOST . " port=". DB_PORT . " dbname=".DB_NAME. " user=".DB_USERNAME. " password=".DB_PASSWORD. "'"
			. ",'set'"
			. ",'true'"
			. ",0)"
			);
	}

	try {
		// consume the output
		ob_start();
		include CORE_DIR . '/upgrade/upgrade.php';
		ob_end_clean();
	} catch (\Throwable $t) {

	}

	//wait a while for changes
	//sleep(10);
	
	//rewrite the event_socket_configuration
	rewrite_event_socket_config();
	rewrite_modules_conf();

	//
	// ask freeswitch to restart
	//
	$socket = new event_socket;
	if (!$socket->connect('fs', '8021', 'ClueCon')) {
		echo "Unable to connect to event socket\n";
	} else {
		$cmd = "api fsctl shutdown";
		$result = $socket->request($cmd);
		if( $result !== false && !empty($result)) {
			if($result === '+OK') {
				echo "FreeSWITCH restarting successfully\n";
			}
		}
	}

	//
	//finish
	//
	echo "+----------------------------+\n";
	echo "|+--------------------------+|\n";
	echo "||  DATABASE INIT FINISHED  ||\n";
	echo "|+--------------------------+|\n";
	echo "+----------------------------+\n";
