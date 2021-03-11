-- include libraries
	require "resources.functions.config";
	require "resources.functions.split";
	require "resources.functions.file_exists";

	local log       = require "resources.functions.log".fax_retry
	local Database  = require "resources.functions.database"
	local Settings  = require "resources.functions.lazy_settings"
	local Tasks     = require "app.fax.resources.scripts.queue.tasks"
	local send_mail = require "resources.functions.send_mail"

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

	local fax_task_uuid  = env:getHeader('fax_task_uuid')
	if not fax_task_uuid then
		log.warning("No [fax_task_uuid] channel variable")
		return
	end
	local task           = Tasks.select_task(fax_task_uuid)
	if not task then
		log.warningf("Can not find fax task: %q", tostring(fax_task_uuid))
		return
	end

-- show all channel variables
	if debug["fax_serialize"] then
		log.noticef("info:\n%s", env:serialize())
	end

	local dbh = Database.new('system')

-- Global environment
	default_language                     = env:getHeader("default_language")
	default_dialect                      = env:getHeader("default_dialect")

-- Channel/FusionPBX variables
	local uuid                           = env:getHeader("uuid")
	local fax_queue_task_session         = env:getHeader('fax_queue_task_session')
	local domain_uuid                    = env:getHeader("domain_uuid")                  or task.domain_uuid
	local domain_name                    = env:getHeader("domain_name")                  or task.domain_name
	local origination_caller_id_name     = env:getHeader("origination_caller_id_name")   or '000000000000000'
	local origination_caller_id_number   = env:getHeader("origination_caller_id_number") or '000000000000000'
	local accountcode                    = env:getHeader("accountcode")                  or domain_name
	local duration                       = tonumber(env:getHeader("billmsec"))           or 0
	local sip_to_user                    = env:getHeader("sip_to_user")
	local bridge_hangup_cause            = env:getHeader("bridge_hangup_cause")
	local hangup_cause_q850              = tonumber(env:getHeader("hangup_cause_q850"))
	local answered                       = duration > 0

-- fax variables
	local fax_success                    = env:getHeader('fax_success')
	local has_t38                        = env:getHeader('has_t38')                        or 'false'
	local t38_broken_boolean             = env:getHeader('t38_broken_boolean')             or ''
	local fax_result_code                = tonumber(env:getHeader('fax_result_code'))      or 2
	local fax_result_text                = env:getHeader('fax_result_text')                or 'FS_NOT_SET'
	local fax_ecm_used                   = env:getHeader('fax_ecm_used')                   or ''
	local fax_local_station_id           = env:getHeader('fax_local_station_id')           or ''
	local fax_document_transferred_pages = env:getHeader('fax_document_transferred_pages') or nil
	local fax_document_total_pages       = env:getHeader('fax_document_total_pages')       or nil
	local fax_image_resolution           = env:getHeader('fax_image_resolution')           or ''
	local fax_image_size                 = env:getHeader('fax_image_size')                 or nil
	local fax_bad_rows                   = env:getHeader('fax_bad_rows')                   or nil
	local fax_transfer_rate              = env:getHeader('fax_transfer_rate')              or nil
	local fax_v17_disabled               = env:getHeader('fax_v17_disabled')               or ''
	local fax_ecm_requested              = env:getHeader('fax_ecm_requested')              or ''
	local fax_remote_station_id          = env:getHeader('fax_remote_station_id')          or ''

	local fax_options = ("fax_use_ecm=%s,fax_enable_t38=%s,fax_enable_t38_request=%s,fax_disable_v17=%s"):format(
		env:getHeader('fax_use_ecm')            or '',
		env:getHeader('fax_enable_t38')         or '',
		env:getHeader('fax_enable_t38_request') or '',
		env:getHeader('fax_disable_v17')        or ''
	)

-- Fax task params
	local fax_uri                        = env:getHeader("fax_uri")                        or task.uri
	local fax_file                       = env:getHeader("fax_file")                       or task.fax_file
	local wav_file                       = env:getHeader("wav_file")                       or task.wav_file
	local fax_uuid                       = task.fax_uuid
	local pdf_file                       = fax_file and string.gsub(fax_file, '(%.[^\\/]+)$', '.pdf')

-- Email variables
	local number_dialed = fax_uri:match("/([^/]-)%s*$")

	log.noticef([[<<< CALL RESULT >>>
    uuid:                          = '%s'
    task_session_uuid:             = '%s'
    answered:                      = '%s'
    fax_file:                      = '%s'
    wav_file:                      = '%s'
    fax_uri:                       = '%s'
    sip_to_user:                   = '%s'
    accountcode:                   = '%s'
    origination_caller_id_name:    = '%s'
    origination_caller_id_number:  = '%s'
    mailto_address:                = '%s'
    hangup_cause_q850:             = '%s'
    fax_options                    = '%s'
]],
    tostring(uuid)                         ,
    tostring(fax_queue_task_session)       ,
    tostring(answered)                     ,
    tostring(fax_file)                     ,
    tostring(wav_file)                     ,
    tostring(fax_uri)                      ,
    tostring(sip_to_user)                  ,
    tostring(accountcode)                  ,
    tostring(origination_caller_id_name)   ,
    tostring(origination_caller_id_number) ,
    tostring(task.reply_address)           ,
    tostring(hangup_cause_q850)            ,
    fax_options
)

	if fax_success then
		log.noticef([[<<< FAX RESULT >>>
    fax_success                    = '%s'
    has_t38                        = '%s'
    t38_broken_boolean             = '%s'
    fax_result_code                = '%s'
    fax_result_text                = '%s'
    fax_ecm_used                   = '%s'
    fax_local_station_id           = '%s'
    fax_document_transferred_pages = '%s'
    fax_document_total_pages       = '%s'
    fax_image_resolution           = '%s'
    fax_image_size                 = '%s'
    fax_bad_rows                   = '%s'
    fax_transfer_rate              = '%s'
    fax_v17_disabled               = '%s'
    fax_ecm_requested              = '%s'
    fax_remote_station_id          = '%s'
    '%s'
]],
			fax_success                    ,
			has_t38                        ,
			t38_broken_boolean             ,
			fax_result_code                ,
			fax_result_text                ,
			fax_ecm_used                   ,
			fax_local_station_id           ,
			fax_document_transferred_pages ,
			fax_document_total_pages       ,
			fax_image_resolution           ,
			fax_image_size                 ,
			fax_bad_rows                   ,
			fax_transfer_rate              ,
			fax_v17_disabled               ,
			fax_ecm_requested              ,
			fax_remote_station_id          ,
			'---------------------------------'
		)
	end

	log.debug([[<<< DEBUG >>>
    domain_name                  = '%s'
    domain_uuid                  = '%s'
    task.domain_name             = '%s'
    task.domain_uuid             = '%s'
]],
    tostring(domain_name      ),
    tostring(domain_uuid      ),
    tostring(task.domain_name ),
    tostring(task.domain_uuid )
)

	assert(fax_uuid,    'no fax server uuid')
	assert(domain_name, 'no domain name')
	assert(domain_uuid, 'no domain uuid')
	assert(domain_uuid:lower() == task.domain_uuid:lower(), 'invalid domain uuid')
	assert(domain_name:lower() == task.domain_name:lower(), 'invalid domain name')

--settings
	local settings = Settings.new(dbh, domain_name, domain_uuid)
	local keep_local   = settings:get('fax', 'keep_local', 'boolean')
	local storage_type = (keep_local == "false") and "" or settings:get('fax', 'storage_type', 'text')

	local function opt(v, default)
		if v then return "'" .. v .. "'" end
		return default or 'NULL'
	end

	local function now_sql()
		return (database["type"] == "sqlite") and "'"..os.date("%Y-%m-%d %X").."'" or "now()";
	end

--add to fax logs
	do
		local fields = {
			"fax_log_uuid";
			"domain_uuid";
			"fax_uuid";
			"fax_success";
			"fax_result_code";
			"fax_result_text";
			"fax_file";
			"fax_ecm_used";
			"fax_local_station_id";
			"fax_document_transferred_pages";
			"fax_document_total_pages";
			"fax_image_resolution";
			"fax_image_size";
			"fax_bad_rows";
			"fax_transfer_rate";
			"fax_retry_attempts";
			"fax_retry_limit";
			"fax_retry_sleep";
			"fax_uri";
			"fax_epoch";
		}

		local params = {
			fax_log_uuid                   = uuid;
			domain_uuid                    = domain_uuid;
			fax_uuid                       = fax_uuid or dbh.NULL;
			fax_success                    = fax_success or dbh.NULL;
			fax_result_code                = fax_result_code or dbh.NULL;
			fax_result_text                = fax_result_text or dbh.NULL;
			fax_file                       = fax_file or dbh.NULL;
			fax_ecm_used                   = fax_ecm_used or dbh.NULL;
			fax_local_station_id           = fax_local_station_id or dbh.NULL;
			fax_document_transferred_pages = fax_document_transferred_pages or '0';
			fax_document_total_pages       = fax_document_total_pages or '0';
			fax_image_resolution           = fax_image_resolution or dbh.NULL;
			fax_image_size                 = fax_image_size or dbh.NULL;
			fax_bad_rows                   = fax_bad_rows or dbh.NULL;
			fax_transfer_rate              = fax_transfer_rate or dbh.NULL;
			fax_retry_attempts             = fax_retry_attempts or dbh.NULL;
			fax_retry_limit                = fax_retry_limit or dbh.NULL;
			fax_retry_sleep                = fax_retry_sleep or dbh.NULL;
			fax_uri                        = fax_uri or dbh.NULL;
			fax_epoch                      = os.time();
		}

		local values = ":" .. table.concat(fields, ",:")
		fields = table.concat(fields, ",") .. ",fax_date"

		if database["type"] == "sqlite" then
			params.fax_date = os.date("%Y-%m-%d %X");
			values = values .. ",:fax_date"
		else
			values = values .. ",now()"
		end

		local sql = "insert into v_fax_logs(" .. fields .. ")values(" .. values .. ")"

		if (debug["sql"]) then
			log.noticef("SQL: %s; params: %s", sql, json.encode(params, dbh.NULL));
		end

		dbh:query(sql, params);
	end

-- add the fax files
	if fax_success == "1" then

		if storage_type == "base64" then
			--include the file io
				local file = require "resources.functions.file"

			--read file content as base64 string
				fax_base64 = file.read_base64(fax_file);
				if not fax_base64 then
					log.waitng("Can not find file %s", fax_file)
					storage_type = nil
				end
		end

	-- build SQL
		local sql do

			local fields = {
				"fax_file_uuid";
				"fax_uuid";
				"fax_mode";
				"fax_destination";
				"fax_file_type";
				"fax_file_path";
				"fax_caller_id_name";
				"fax_caller_id_number";
				"fax_epoch";
				"fax_base64";
				"domain_uuid";
			}

			local params = {
				fax_file_uuid        = uuid;
				fax_uuid             = fax_uuid or dbh.NULL;
				fax_mode             = "tx";
				fax_destination      = sip_to_user or dbh.NULL;
				fax_file_type        = "tif";
				fax_file_path        = fax_file or dbh.NULL;
				fax_caller_id_name   = origination_caller_id_name or dbh.NULL;
				fax_caller_id_number = origination_caller_id_number or dbh.NULL;
				fax_epoch            = os.time();
				fax_base64           = fax_base64 or dbh.NULL;
				domain_uuid          = domain_uuid or dbh.NULL;
			}

			local values = ":" .. table.concat(fields, ",:")
			fields = table.concat(fields, ",") .. ",fax_date"

			if database["type"] == "sqlite" then
				params.fax_date = os.date("%Y-%m-%d %X");
				values = values .. ",:fax_date"
			else
				values = values .. ",now()"
			end

			local sql = "insert into v_fax_files(" .. fields .. ")values(" .. values .. ")"

			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params, dbh.NULL));
			end

			if storage_type == "base64" then
				local dbh = Database.new('system', 'base64');
				dbh:query(sql, params);
				dbh:release();
			else
				dbh:query(sql, params)
			end
		end
	end

	if fax_success == "1" then
		--Success
		log.infof("RETRY STATS SUCCESS: GATEWAY[%s]", fax_options);

		Tasks.remove_task(task)

		local Text = require "resources.functions.text"
		local text = Text.new("app.fax.app_languages")

		local env = {
			fax_options                = fax_options;
			destination_number         = number_dialed:match("^([^@]*)");
			document_transferred_pages = fax_document_transferred_pages;
			document_total_pages       = fax_document_total_pages;
			message                    = text['message-send_success'];
		}

		local body    = Tasks.build_template(task, 'outbound/success/body', env)
		local subject = Tasks.build_template(task, 'outbound/success/subject', env)

		if not subject then
			log.warning("Can not find template for email")
			subject = "Fax to: " .. number_dialed .. " SENT"
		end

		local attachment = pdf_file and file_exists(pdf_file) or fax_file and file_exists(fax_file)
		Tasks.send_mail_task(task, {subject, body}, uuid, attachment)

		if keep_local == "false" then
			os.remove(pdf_file);
			os.remove(fax_file);
		end
	end

	if fax_success ~= "1" then
		if not answered then
			log.noticef("no answer: %d", hangup_cause_q850)
		else
			if not fax_success then
				log.noticef("Fax not detected: %s", fax_options)
			else
				log.noticef("fax fail %s", fax_options)
			end
		end

		-- if task use group call then retry.lua will be called multiple times
		-- here we check eathre that channel which execute `exec.lua`
		-- Note that if there no one execute `exec.lua` we do not need call this
		-- becase it should deal in `next.lua`
		if fax_queue_task_session == uuid then
			Tasks.wait_task(task, answered, hangup_cause_q850)
			if task.status ~= 0 then
				Tasks.remove_task(task)

				local Text = require "resources.functions.text"
				local text = Text.new("app.fax.app_languages")

				local env = {
					fax_options                = fax_options;
					destination_number         = number_dialed:match("^([^@]*)");
					document_transferred_pages = fax_document_transferred_pages;
					document_total_pages       = fax_document_total_pages;
					hangup_cause               = hangup_cause;
					hangup_cause_q850          = hangup_cause_q850;
					fax_result_code            = fax_result_code;
					fax_result_text            = fax_result_text;
					message                    = text['message-send_fail'];
				}

				local body    = Tasks.build_template(task, 'outbound/fail/body', env)
				local subject = Tasks.build_template(task, 'outbound/fail/subject', env)

				if not subject then
					log.warning("Can not find template for email")
					subject = "Fax to: " .. number_dialed .. " FAILED"
				end

				local attachment = pdf_file and file_exists(pdf_file) or fax_file and file_exists(fax_file)
				Tasks.send_mail_task(task, {subject, body}, uuid, attachment)

				if keep_local == "false" then
					os.remove(pdf_file);
					os.remove(fax_file);
				end

			end
		end
	end

