local log = log or require "resources.functions.log"[app_name or 'play_file']
local find_file = require "resources.functions.find_file"

function play_file(dbh, domain_name, domain_uuid, file_name)
	local full_path, is_base64 = find_file(dbh, domain_name, domain_uuid, file_name)
	if not full_path then
		log.warningf('Can not find audio file: %s. Try using it in raw mode.', file_name)
		full_path = file_name
	else
		log.noticef('Found `%s` as `%s`%s', file_name, full_path, is_base64 and '(BASE64)' or '')
	end
	session:execute("playback", full_path);
end

return play_file