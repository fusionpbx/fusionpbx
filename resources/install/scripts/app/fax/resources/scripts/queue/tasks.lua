local Database  = require "resources.functions.database"
local Settings  = require "resources.functions.lazy_settings"
local send_mail = require "resources.functions.send_mail"

local db

local date_utc_now_sql
local now_add_sec_sql

if database.type == 'pgsql' then
  date_utc_now_sql  = "NOW() at time zone 'utc'"
  now_add_sec_sql   = "NOW() at time zone 'utc' + interval '%s second'"
elseif database.type == 'mysql' then
  date_utc_now_sql  = "UTC_TIMESTAMP()"
  now_add_sec_sql   = "DATE_ADD(UTC_TIMESTAMP(), INTERVAL %s SECOND)"
elseif database.type == 'sqlite'  then
  date_utc_now_sql  = "datetime('now')"
  now_add_sec_sql   = "datetime('now', '%s seconds')"
else
  error("unsupported database type: " .. database.type)
end

-- Broken on FS 1.4 with native postgresql
-- Fixed on 1.6.0
-- Also works with ODBC
local ignore_affected_rows = true
if dbh_affected_rows_broken ~= nil then
  ignore_affected_rows = dbh_affected_rows_broken
end

local Q850_TIMEOUT = {
  [17] = 60;
}

local select_task_common_sql = [[
select
  t1.fax_task_uuid as uuid,
  t1.fax_uuid as fax_uuid,
  t3.domain_name,
  t3.domain_uuid,
  t1.task_status as status,
  t1.task_uri as uri,
  t1.task_dial_string as dial_string,
  t1.task_dtmf as dtmf,
  t1.task_fax_file as fax_file,
  t1.task_wav_file as wav_file,
  t1.task_reply_address as reply_address,
  t1.task_no_answer_counter as no_answer_counter,
  t1.task_no_answer_retry_counter as no_answer_retry_counter,
  t1.task_retry_counter as retry_counter,
  t2.fax_send_greeting as greeting
from v_fax_tasks t1
  inner join v_fax t2 on t2.fax_uuid = t1.fax_uuid
  inner join v_domains t3 on t2.domain_uuid = t3.domain_uuid
where t1.task_interrupted <> 'true'
]]

local next_task_sql = select_task_common_sql .. [[
and t1.task_status = 0 and t1.task_next_time < ]] .. date_utc_now_sql .. [[
and t2.fax_send_channels > (select count(*) from v_fax_tasks as tasks
  where tasks.fax_uuid = t1.fax_uuid and
  tasks.task_status > 0 and tasks.task_status <= 2
)
order by t1.task_next_time
]]

local select_task_sql = select_task_common_sql .. "and t1.fax_task_uuid='%s'"

local aquire_task_sql = [[
  update v_fax_tasks set task_status = 1, task_lock_time = ]] .. date_utc_now_sql .. [[
  where fax_task_uuid = '%s' and task_status = 0
]]

local wait_task_sql = [[
  update v_fax_tasks
  set task_status = %s,
  task_lock_time = NULL,
  task_no_answer_counter = %s,
  task_no_answer_retry_counter = %s,
  task_retry_counter = %s,
  task_next_time = ]] .. now_add_sec_sql .. [[
  where fax_task_uuid = '%s'
]]

local remove_task_task_sql = [[
  delete from v_fax_tasks
  where fax_task_uuid = '%s'
]]

local release_task_sql = [[
  update v_fax_tasks
  set task_status = 0, task_lock_time = NULL,
  task_next_time = ]] .. now_add_sec_sql .. [[
  where fax_task_uuid = '%s'
]]

local release_stuck_tasks_sql = [[
  update v_fax_tasks
  set task_status = 0, task_lock_time = NULL,
  task_next_time = ]] .. date_utc_now_sql .. [[
  where task_lock_time < ]] .. now_add_sec_sql:format('-3600') .. [[
]]

local remove_finished_tasks_sql = [[
  delete from v_fax_tasks where task_status > 3
]]

local function serialize(task, header)
  local str = header or ''
  for k, v in pairs(task) do
    str = str .. ('\n %q = %q'):format(tostring(k), tostring(v))
  end
  return str
end

local function get_db()
  if not db then
    db = assert(Database.new('system'))
  end
  return db
end

local function next_task()
  local db = get_db()

  while true do
    local task, err = db:first_row(next_task_sql)
    if not task then return nil, err end
    local ok, err = db:query( aquire_task_sql:format(task.uuid) )
    if not ok then return nil, err end
    local rows = db:affected_rows()
    if ignore_affected_rows then
      rows = 1
    end
    if rows == 1 then
      task.no_answer_counter       = tonumber(task.no_answer_counter)
      task.no_answer_retry_counter = tonumber(task.no_answer_retry_counter)
      task.retry_counter           = tonumber(task.retry_counter)
      return task
    end
  end
end

local function select_task(fax_task_uuid)
  local db = get_db()

  local task, err = db:first_row(select_task_sql:format(fax_task_uuid))
  if not task then return nil, err end

  task.no_answer_counter       = tonumber(task.no_answer_counter)
  task.no_answer_retry_counter = tonumber(task.no_answer_retry_counter)
  task.retry_counter           = tonumber(task.retry_counter)

  return task
end

local function wait_task(task, answered, q850)
  local db = get_db()

  local interval = 30

  local settings = Settings.new(db, task.domain_name, task.domain_uuid)
  task.status    = 0

  if not answered then
    interval = Q850_TIMEOUT[q850 or 17] or interval
  end

  if not answered then
    local fax_send_no_answer_retry_limit = tonumber(settings:get('fax', 'send_no_answer_retry_limit', 'numeric')) or 0
    task.no_answer_retry_counter = task.no_answer_retry_counter + 1

    if task.no_answer_retry_counter >= fax_send_no_answer_retry_limit then
      task.no_answer_retry_counter = 0
      task.no_answer_counter = task.no_answer_counter + 1
      local fax_send_no_answer_limit = tonumber(settings:get('fax', 'send_no_answer_limit', 'numeric')) or 0
      if task.no_answer_counter >= fax_send_no_answer_limit then
        task.status = 4
      else
        interval = tonumber(settings:get('fax', 'send_no_answer_interval', 'numeric')) or interval
      end
    else
      interval = tonumber(settings:get('fax', 'send_no_answer_retry_interval', 'numeric')) or interval
    end
  else
    task.retry_counter = task.retry_counter + 1
    local fax_send_retry_limit = tonumber(settings:get('fax', 'send_retry_limit', 'numeric')) or 0

    if task.retry_counter >= fax_send_retry_limit then
      task.status = 4
    else
      interval = tonumber(settings:get('fax', 'send_retry_interval', 'numeric')) or interval
      task.task_seq_call_counter = 0
    end
  end

  local sql = wait_task_sql:format(
    tostring(task.status),
    tostring(task.no_answer_counter),
    tostring(task.no_answer_retry_counter),
    tostring(task.retry_counter),
    tostring(interval),
    task.uuid
  )

  local ok, err = db:query( sql )

  if not ok then return nil, err end

  return task
end

local function remove_task(task)
  local db = get_db()

  local sql = remove_task_task_sql:format(task.uuid)
  local ok, err = db:query( sql )
  if not ok then return nil, err end
  return db:affected_rows()
end

local function release_task(task)
  local db = get_db()

  local interval = 30

  local sql = release_task_sql:format(
    tostring(interval),
    task.uuid
  )

  local ok, err = db:query( sql )

  if not ok then return nil, err end

  return task
end

local function cleanup_tasks()
  local db = get_db()

  db:query(release_stuck_tasks_sql)
  db:query(remove_finished_tasks_sql)
end

local function send_mail_task(task, message, call_uuid, file)
  if not task.reply_address or #task.reply_address == 0 then
    return
  end

  local mail_x_headers = {
    ["X-FusionPBX-Domain-UUID"] = task.domain_uuid;
    ["X-FusionPBX-Domain-Name"] = task.domain_name;
    ["X-FusionPBX-Call-UUID"]   = call_uuid;
    ["X-FusionPBX-Email-Type"]  = 'email2fax';
  }

  return send_mail(mail_x_headers, task.reply_address, message, file)
end

local function read_file(name, mode)
  local f, err, code = io.open(name, mode or 'rb')
  if not f then return nil, err, code end
  local data = f:read("*all")
  f:close()
  return data
end

local function read_template(app, name, lang)
  local default_language_path = 'en/us'

  local full_path_tpl = scripts_dir .. '/app/' .. app .. '/resources/templates/{lang}/' .. name .. '.tpl'

  local path

  if lang then
    path = file_exists((full_path_tpl:gsub('{lang}', lang)))
  end

  if (not path) and (lang ~= default_language_path) then
    path = file_exists((full_path_tpl:gsub('{lang}', default_language_path)))
  end

  if path then
    return read_file(path)
  end
end

local function build_template(task, templ, env)
  local lang

  if default_language and default_dialect then
    lang = (default_language .. '/' .. default_dialect):lower()
  else
    local settings = Settings.new(get_db(), task.domain_name, task.domain_uuid)
    lang = settings:get('domain', 'language', 'code')
    if lang then lang = lang:gsub('%-', '/'):lower() end
  end

  local body = read_template('fax', templ, lang)

  body = body:gsub("[^\\](${.-})", function(name)
    name = name:sub(3, -2)
    if type(env) == 'table' then
      return env[name] or ''
    end
    if type(env) == 'function' then
      return env(name) or ''
    end
  end)

  return body
end

return {
  release_db = function()
    if db then
      db:release()
      db = nil
    end
  end;
  next_task      = next_task;
  wait_task      = wait_task;
  select_task    = select_task;
  remove_task    = remove_task;
  release_task   = release_task;
  cleanup_tasks  = cleanup_tasks;
  send_mail_task = send_mail_task;
  build_template = build_template;
}
