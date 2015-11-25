require "resources.functions.config"

require "resources.functions.sleep"
local log       = require "resources.functions.log".next_fax_task
local Tasks     = require "fax_queue.tasks"
local Esl       = require "resources.functions.esl"

local FAX_OPTIONS = {
  "fax_use_ecm=false,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=default";
  "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=false";
  "fax_use_ecm=true,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
  "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=true";
  "fax_use_ecm=false,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
}

local function next_task()
  local task, err = Tasks.next_task()

  if not task then
    if err then
      log.noticef('Can not select next task: %s', tostring(err))
    else
      log.notice("No task")
    end
    return
  end

  local esl
  local ok, err = pcall(function()

    for k, v in pairs(task) do
      print(string.format("  `%s` => `%s`", tostring(k), tostring(v)))
    end

    local mode = (task.retry_counter % #FAX_OPTIONS) + 1
    local dial_string  = '{' ..
      task.dial_string .. "api_hangup_hook='lua fax_queue/retry.lua'," ..
      FAX_OPTIONS[mode] .. 
    '}' .. task.uri

    local originate = 'originate ' .. dial_string .. ' &lua(fax_queue/exec.lua)'

    log.notice(originate)
    esl = assert(Esl.new())
    local ok, err = esl:api(originate)
    log.notice(ok or err)
  end)

  if esl then esl:close() end

  if not ok then
    Tasks.release_task(task)
    log.noticef("Error execute task: %s", tostring(err))
  end

  return true
end

local function poll_once()
  Tasks.cleanup_tasks()
  while next_task() do
    sleep(5000)
  end
  Tasks.release_db()
end

return {
  poll_once = poll_once;
}
