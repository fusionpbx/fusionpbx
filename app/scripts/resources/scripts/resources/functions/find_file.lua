local log = log or require "resources.functions.log"[app_name or 'find_file']

local file             = require "resources.functions.file"
local Settings         = require "resources.functions.lazy_settings"
local basename         = require "resources.functions.basename"
local is_absolute_path = require "resources.functions.is_absolute_path"

function find_file(dbh, domain_name, domain_uuid, file_name)
	-- if we specify e.g. full path
	if (is_absolute_path(file_name) and file.exists(file_name)) then
		log.debugf('[find_file.lua] found file `%s` in file system', file_name)
		return file_name
	end

	local file_name_only = basename(file_name)
	local is_base64, found

	if file_name_only == file_name then -- this can be recordings
		local full_path = recordings_dir .. "/" .. domain_name .. "/" .. file_name
		if file.exists(full_path) then
			log.debugf('[find_file.lua] `%s` as recording `%s`', file_name, full_path)
			file_name, found = full_path, true
		else 
			--send debug info to the log
			log.debugf('[find_file.lua] `%s` as recording `%s`', file_name, full_path)

			-- recordings may be in database
			local settings = Settings.new(dbh, domain_name, domain_uuid)
			local storage_type = settings:get('recordings', 'storage_type', 'text') or ''
			if storage_type == 'base64' then
				local sql = "SELECT recording_base64 FROM v_recordings "
					.. "WHERE domain_uuid = :domain_uuid"
					.. "AND recording_filename = :file_name "
				local params = {domain_uuid = domain_uuid, file_name = file_name};
				if (debug["sql"]) then
					log.notice("SQL: " .. sql .. "; params: " .. json.encode(params))
				end

				local dbh = Database.new('system', 'base64/read')
				local recording_base64 = dbh:first_value(sql, params);
				dbh:release();

				if recording_base64 and #recording_base64 > 32 then
					log.debugf('[find_file.lua] `%s` as recording `%s`(base64)', file_name, full_path)
					file_name, found, is_base64 = full_path, true, true
					file.write_base64(file_name, recording_base64)
				end
			end
		end
	end

	if not found then
		local sounds_dir
		if session then
			if session:ready() then
				-- Implemented based on stream.lua. But seems it never works.
				-- because if we have file like `digits/1.wav` but full_path is `sounds_dir/digits/XXXX/1.wav`
				sounds_dir = session:getVariable("sounds_dir")
				local default_language = session:getVariable("default_language") or 'en'
				local default_dialect = session:getVariable("default_dialect") or 'us'
				local default_voice = session:getVariable("default_voice") or 'callie'

				sounds_dir = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice
			end
		else
			--! @todo implement for not session call.
		end

		if sounds_dir then
			found = file.exists(sounds_dir.. "/" ..file_name_only)
			if found then
				log.debugf('[find_file.lua] `%s` as sound `%s`', file_name, found)
				file_name, found = found, true
			end
		end
	end

	if not found then
		return
	end

	return file_name, is_base64
end

return find_file
