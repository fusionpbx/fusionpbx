	local sleep_interval = 60;

--include config.lua
	require "resources.functions.config";

--general functions
	require "resources.functions.file_exists";
	require "resources.functions.mkdir";
	require "resources.functions.sleep";

	local log = require "resources.functions.log".fax_queue_monitor
	local Next = require "app.fax.resources.scripts.queue.next"

	mkdir(scripts_dir .. "/run");

--define the run file
	local run_file = scripts_dir .. "/run/fax_queue.tmp";

--used to stop the lua service
	local file = assert(io.open(run_file, "w"));
	file:write("remove this file to stop the script");
	file:close()

	log.notice("Start")

	while true do
		local ok, err = pcall(function()
			Next.poll_once()
		end)

		if not ok then
			log.errf("fail poll queue: %s", tostring(err))
		end

		if not file_exists(run_file) then
			break;
		end

		sleep(sleep_interval * 1000)
	end

	log.notice("Stop")
