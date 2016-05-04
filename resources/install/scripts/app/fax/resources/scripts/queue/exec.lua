-- @usage without queue
-- api: originate {fax_file='',wav_file='',fax_dtmf=''}user/108@domain.local &lua(app/fax/resources/scripts/queue/exec.lua)
-- @usage with queue task
-- api: originate {fax_task_uuid=''}user/108@domain.local &lua(app/fax/resources/scripts/queue/exec.lua)
-- @fax_dtmf
--  0-9*# - dtmf symbols
--  @200  - dtmf duration in ms
--  p     - pause 500 ms
--  P     - pause 1000 ms
--
-- example: pause 5 sec dial 008 pause 2 sec paly greeting
-- PPPPP008@300PP
--

require "resources.functions.config"
local log = require "resources.functions.log".fax_task

-- If we handle queue task
local fax_task_uuid  = session:getVariable('fax_task_uuid')
local task if fax_task_uuid then
  local Tasks = require "app.fax.resources.scripts.queue.tasks"
  task = Tasks.select_task(fax_task_uuid)
  if not task then
    log.warningf("Can not found fax task: %q", tostring(fax_task_uuid))
    return
  end
end

if task then
  local str = 'Queue task :'
  for k, v in pairs(task) do
    str = str .. ('\n %q = %q'):format(k, v)
  end
  log.info(str)
else
  log.info('Not queued task')
end

local function empty(t) return (not t) or (#t == 0) end

local function not_empty(t) if not empty(t) then return t end end

local dtmf, wav_file, fax_file

if task then
  dtmf     = not_empty(task.dtmf)
  wav_file = not_empty(task.wav_file) or not_empty(task.greeting)
  fax_file = not_empty(task.fax_file)
else
  dtmf     = not_empty(session:getVariable('fax_dtmf'))
  wav_file = not_empty(session:getVariable('wav_file'))
  fax_file = not_empty(session:getVariable('fax_file'))
end

if not (wav_file or fax_file) then
  log.warning("No fax or wav file")
  return
end

local function decode_dtmf(dtmf)
  local r, sleep, seq = {}
  dtmf:gsub('P', 'pp'):gsub('.', function(ch)
    if ch == ';' or ch == ',' then
      r[#r + 1] = sleep or seq
      sleep, seq = nil
    elseif ch == 'p' then
      r[#r + 1] = seq
      sleep = (sleep or 0) + 500
      seq = nil
    else
      r[#r + 1] = sleep
      seq = (seq or '') .. ch
      sleep = nil
    end
  end)
  r[#r + 1] = sleep or seq
  return r
end

local function send_fax()
  session:execute("txfax", fax_file)
end

local function start_fax_detect(detect_duration)
  if not tone_detect_cb then
    function tone_detect_cb(s, type, obj, arg)
      if type == "event" then
        if obj:getHeader('Event-Name') == 'DETECTED_TONE' then
          return "false"
        end
      end
    end
  end

  log.notice("Start detecting fax")

  detect_duration = detect_duration or 60000

  session:setInputCallback("tone_detect_cb")
  session:execute("tone_detect", "txfax 2100 r +" .. tostring(detect_duration) .. " set remote_fax_detected=txfax")
  session:execute("tone_detect", "rxfax 1100 r +" .. tostring(detect_duration) .. " set remote_fax_detected=rxfax")
  session:setVariable("sip_api_on_image", "uuid_break " .. session:getVariable("uuid") .. " all")
end

local function stop_fax_detect()
  session:unsetInputCallback()
  session:execute("stop_tone_detect")
  session:setVariable("sip_api_on_image", "")
end

local function fax_deteced()
  if session:getVariable('has_t38') == 'true' then
    log.noticef('Detected t38')
    session:setVariable('remote_fax_detected', 'txfax')
  end

  if fax_file and session:getVariable('remote_fax_detected') then
    log.noticef("Detected %s", session:getVariable('remote_fax_detected'))
    if session:getVariable('remote_fax_detected') == 'txfax' then
      send_fax()
    else
      log.warning('Remote fax try send fax')
    end
    return true
  end
end

local function check()
  if not session:ready() then return false end
  if fax_deteced() then return false end
  return true
end

local function task()
  local session_uuid = session:getVariable('uuid')

  session:setVariable('fax_queue_task_session', session_uuid)

  log.infof("SESSION UUID: %s", session_uuid)

  session:waitForAnswer(session)

  while not session:answered() do
    if not session:ready() then return end
    session:sleep(500)
  end

  if not (session:ready() and session:answered()) then return end

  if fax_file and wav_file then
    start_fax_detect()
  end

  if dtmf then
    dtmf = decode_dtmf(dtmf)
    for _, element in ipairs(dtmf) do
      if type(element) == 'number' then
        session:streamFile("silence_stream://" .. tostring(element))
      else
        session:execute("send_dtmf", element)
      end
      if not check() then return end
    end
  end

  if wav_file then
    session:streamFile(wav_file)
    if not check() then return end
  end

  if fax_file then
    if wav_file then
      stop_fax_detect()
    end
    send_fax()
  end
end

log.noticef("START TASK")
log.notice("Fax:" .. tostring(fax_file))
log.notice("Wav:" .. tostring(wav_file))

task()

log.noticef("STOP TASK")
log.notice("Ready: " .. tostring(session:ready()))
log.notice("Answered: " .. tostring(session:answered()))
