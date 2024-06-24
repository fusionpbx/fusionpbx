local log  = require "resources.functions.log".route_to_bridge
require "resources.functions.split"

local allows_functions = {
	['user_data'] = true,
}

local pcre_match

if freeswitch then
	api = api or freeswitch.API()

	local function escape_regex(s)
		s = string.gsub(s, '\\', '\\\\')
		s = string.gsub(s, '|', '\\|')
		return s
	end

	--! @todo find better way to extract captures
	local unpack = unpack or table.unpack
	function pcre_match(str, pat)
		local a = escape_regex(str) .. "|/" .. escape_regex(pat) .."/"
		if api:execute("regex", a) == 'false' then return end
		local t = {}
		for i = 1, 5 do
			t[i] = api:execute("regex", a .. '|$' .. i)
		end
		return unpack(t)
	end
else
	local pcre = require "rex_pcre"
	function pcre_match(str, pat)
		return pcre.match(str, pat)
	end
end

local function pcre_self_test()
	io.write('Test regex ')
	local a, b, c
	a,b,c = pcre_match('abcd', '(\\d{3})(\\d{3})')
	assert(a == nil)

	a,b,c = pcre_match('123456', '(\\d{3})(\\d{3})')
	assert(a == '123', a)
	assert(b == '456', b)

	a,b,c = pcre_match('999', '(888|999)')
	assert(a == '999', a)

	a,b,c = pcre_match('888|999', '(888\\|999)')
	assert(a == '888|999', a)

	io.write(' - ok\n')
end

local select_outbound_dialplan_sql = [[
SELECT
	d.dialplan_uuid,
	d.dialplan_context,
	d.dialplan_continue,
	s.dialplan_detail_group,
	s.dialplan_detail_break,
	s.dialplan_detail_data,
	s.dialplan_detail_inline,
	s.dialplan_detail_tag,
	s.dialplan_detail_type
FROM v_dialplans as d, v_dialplan_details as s
WHERE  (d.domain_uuid = :domain_uuid OR d.domain_uuid IS NULL)
	AND (d.hostname = :hostname OR d.hostname IS NULL)
	AND d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
	AND d.dialplan_enabled = 'true'
	AND d.dialplan_uuid = s.dialplan_uuid
ORDER BY
	d.dialplan_order ASC,
	d.dialplan_name ASC,
	d.dialplan_uuid ASC,
	s.dialplan_detail_group ASC,
	CASE s.dialplan_detail_tag
		WHEN 'condition' THEN 1
		WHEN 'action' THEN 2
		WHEN 'anti-action' THEN 3
		ELSE 100
	END,
	s.dialplan_detail_order ASC
]]

local function append(t, v)
	t[#t + 1] = v
	return t
end

local function order_keys(t)
	local o = {}
	for k in pairs(t) do append(o, k) end
	table.sort(o)
	local i = 0
	return function(o)
		i = i + 1
		return o[i], t[o[i]]
	end, o
end

local function check_conditions(group, fields)
	local matches, pass, last, break_on

	for n, condition in ipairs(group.conditions) do
		last = (n == #group.conditions)

		local value = fields[condition.type]
		if (not value) and (condition_type ~= '') then -- try var name
			local condition_type = string.match(condition.type, '^%${(.*)}$')
			if condition_type then value = fields[condition_type] end
		end

		if (not value) and (condition.type ~= '') then -- skip unknown fields
			log.errf('Unsupported condition: %s', condition.type)
			matches, pass = {}, false
		else
			if condition.type == '' then
				matches, pass = {}, true
			else
				matches = {pcre_match(value, condition.data)}
				pass = #matches > 0
			end
			log.debugf('%s condition %s(%s) to `%s`', pass and 'PASS' or 'FAIL', condition.type, condition.data, value or '<NONE>')
		end

		break_on = condition.break_on
		if break_on == 'always'  then break
		elseif break_on ~= 'never' then
			if pass then if break_on == 'on-true' then break end
			elseif break_on == 'on-false' or break_on == '' then break end
		end

		break_on = nil
	end

	-- we should execute action/anti-action only if we check ALL conditions
	local act
	if last then act = pass and 'action' or 'anti-action' end

	-- we should break
	return act, not not break_on, matches
end

local function apply_match(s, match)
	return string.gsub(s, "%$(%d)", function(i)
		return match[tonumber(i)] or ''
	end)
end

local function apply_var(s, fields)
	local str = string.gsub(s, "%$?%${([^$%(%){}= ]-)}", function(var)
		return fields[var]
	end)

	if fields.__api__ then
		local api = fields.__api__
		-- try call functions like ('set result=${user_data(args)}')
		str = string.gsub(str, "%${([^$%(%){}= ]+)%s*%((.-)%)%s*}", function(fn, par)
			if allows_functions[fn] then
				return api:execute(fn, par) or ''
			end
			log.warningf('try call not allowed function %s', tostring(fn))
		end)

		-- try call functions like 'set result=${user_data args}'
		str = string.gsub(str, "%${([^$%(%){}= ]+)%s+(%S.-)%s*}", function(fn, par)
			if allows_functions[fn] then
				return api:execute(fn, par) or ''
			end
			log.warningf('try call not allowed function %s', tostring(fn))
		end)
	end

	if string.find(str, '%${.+}') then
		log.warningf('can not resolve vars inside `%s`', tostring(str))
	end
	return str
end

local function group_to_bridge(actions, group, fields)
	local action_type, do_break, matches = check_conditions(group, fields)
	if action_type then
		local t = (action_type == 'action') and group.actions or group.anti_actions
		for _, action in ipairs(t) do
			local value = action.data

			-- we only support set/export actions
			if action.type == 'export' or action.type == 'set' then
				local key

				key, value = split_first(value, '=', true)
				if key then
					local bleg_only = (action.type == 'export') and (string.sub(key, 1, 8) == 'nolocal:')
					if bleg_only then key = string.sub(key, 9) end

					value = apply_match(value, matches)
					value = apply_var(value, fields)

					if action.inline and not bleg_only then
						fields[key] = value
					end

					--! @todo do value escape?
					append(actions, key .. '=' .. value)
				end
			end

			if action.type == 'bridge' then
				value = apply_match(value, matches)
				value = apply_var(value, fields)
				actions.bridge = apply_match(value, matches)
				break
			end
		end
	end

	return do_break
end

local function extension_to_bridge(extension, actions, fields)
	for _, group in order_keys(extension) do
		local do_break = group_to_bridge(actions, group, fields)
		if do_break then break end
	end
end

local function self_test()
	local unpack = unpack or table.unpack

	local function assert_equal(expected, actions)
		for i = 1, math.max(#expected, #actions) do
			local e, v, msg = expected[i], actions[i]
			if not e then
				msg = string.format("unexpected value #%d - `%s`", i, v)
			elseif not v then
				msg = string.format("expected value `%s` at position #%d, but got no value", e, i)
			elseif e ~= v then
				msg = string.format("expected value `%s` at position #%d but got: `%s`", e, i, v)
			end
			assert(not msg, msg)
		end

		for name, e in pairs(expected) do
			local v, msg = actions[name]
			if not v then
				msg = string.format("%s expected as `%s`, but got no value", name, e)
			elseif e ~= v then
				msg = string.format("expected value for %s is `%s`, but got: `%s`", name, e, v)
			end
			assert(not msg, msg)
		end

		for name, v in pairs(actions) do
			local e, msg = expected[name]
			if not e then
				msg = string.format("expected value %s = `%s`", name, v)
			end
			assert(not msg, msg)
		end

	end

	local function test_grout_to_bridge(group, params, ret, expected)
		local actions = {}
		local result = group_to_bridge(actions, group, params)
		if result ~= ret then
			local msg = string.format('expected `%s` but got `%s`', tostring(ret), tostring(result))
			assert(false, msg)
		end
		assert_equal(expected, actions)
	end

	-- mock for API
	local function API(t)
		local api = {
			execute = function(self, cmd, args)
				cmd = assert(t[cmd])
				return cmd[args]
			end;
		}
		return api
	end

	local old_log = log
	log = {
		errf     = function() end;
		warningf = function() end;
		debugf   = function() end;
	}

	pcre_self_test()

	local test_conditions = {
		{
			{conditions={
				{type='destination_number', data='100', break_on=''};
			}};
			{destination_number = 100};
			{'action', false};
		};

		{
			{conditions={
				{type='destination_number', data='100', break_on='on-true'};
			}};
			{destination_number = 100};
			{'action', true};
		};

		{
			{conditions={
				{type='destination_number', data='101', break_on=''};
			}};
			{destination_number = 100};
			{'anti-action', true};
		};

		{
			{conditions={
				{type='destination_number', data='100', break_on=''};
				{type='destination_number', data='101', break_on=''};
				{type='destination_number', data='102', break_on=''};
			}};
			{destination_number = 100};
			{nil, true};
		};

		{
			{conditions={
				{type='destination_number', data='100', break_on='never'};
				{type='destination_number', data='101', break_on='never'};
				{type='destination_number', data='102', break_on='never'};
			}};
			{destination_number = 102};
			{'action', false};
		};

		{
			{conditions={
				{type='destination_number', data='100', break_on='never'};
				{type='destination_number', data='101', break_on='never'};
				{type='destination_number', data='102', break_on='never'};
			}};
			{destination_number = 103};
			{'anti-action', false};
		};

		{
			{conditions={
				{type='destination_number', data='100', break_on=''};
				{type='destination_number', data='101', break_on=''};
				{type='destination_number', data='102', break_on=''};
			}};
			{destination_number = 102};
			{nil, true};
		};

		{
			{conditions={
				{type='', data='', break_on=''};
			}};
			{};
			{'action', false};
		};

		{
			{conditions={
				{type='caller_id_number', data='123456', break_on=''};
			}};
			{};
			{'anti-action', true};
		};

	}

	for i, test in ipairs(test_conditions) do
		io.write('Test conditions #' .. i)
		local group, fields, result = test[1], test[2], test[3]
		local action, do_break, matches = check_conditions(group, fields)
		assert(action   == result[1], tostring(action))
		assert(do_break == result[2])
		io.write(' - ok\n')
	end

	local test_actions = {
		{ -- should not touch unknown vars
			{actions={
					{type='set', data='a=${b}'}
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
			},
			{ -- result
				'a=${b}'
			}
		},
		{ -- should call execute command with braces
			{actions={
					{type='set', data='a=${user_data(a b c)}'}
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['a b c'] = 'value'}}
			},
			{ -- result
				'a=value'
			}
		},
		{ -- should call execute command with spaces
			{actions={
					{type='set', data='a=${user_data a b c }'}
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['a b c'] = 'value'}}
			},
			{ -- result
				'a=value'
			}
		},
		{ -- should not call not allowed function
			{actions={
					{type='set', data='a=${user_exists( a b c )}'}
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['a b c'] = 'value'}}
			},
			{ -- result
				'a=${user_exists( a b c )}'
			}
		},
		{ -- should set inline vars
			{actions={
					{type='set', data='a=hello', inline=true},
					{type='set', data='b=${a}'},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['a b c'] = 'value'}}
			},
			{ -- result
				'a=hello',
				'b=hello',
			}
		},
		{ -- should not set not inline vars
			{actions={
					{type='set', data='a=hello'},
					{type='set', data='b=${a}'},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['a b c'] = 'value'}}
			},
			{ -- result
				'a=hello',
				'b=${a}',
			}
		},
		{ -- should expand vars inside call
			{actions={
					{type='set', data='a=${user_data(${a}${b})}'},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				__api__ = API{user_data={['helloworld'] = 'value'}},
				a = 'hello',
				b = 'world',
			},
			{ -- result
				'a=value',
				}
		},
		{ -- should export nolocal
			{actions={
					{type='export', data='a=nolocal:value', inline=true},
					{type='export', data='b=${a}'},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
			},
			{ -- result
				'a=value',
				'b=${a}',
			}
		},
		{ -- should handle bridge as last action
			{actions={
					{type='bridge', data='sofia/gateway/${a}'},
					{type='set', data='a=123', inline=true},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
				a='gw'
			},
			{ -- result
				bridge = 'sofia/gateway/gw'
			}
		},
		{ -- should ingnore `nolocal` for set
			{actions={
					{type='set', data='a=nolocal:123', inline=true},
					{type='export', data='b=${a}'},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
			},
			{ -- result
				'a=nolocal:123';
				'b=nolocal:123';
			}
		},
		{ -- should ingnore unsupportded actions
			{actions={
					{type='ring_ready', data=''},
					{type='answer', data=''},
				};
				conditions={{type='', data='', break_on='on-true'}};
			},
			{ -- parameters
			},
			{ -- result
			}
		},
	}

	for i, test_case in ipairs(test_actions) do
		local group, params, expected = unpack(test_case)
		io.write('Test execute #' .. i)
		test_grout_to_bridge(group, params, true, expected)
		io.write(' - ok\n')
	end

	log = old_log
end

-- Returns array of set/export actions and bridge command.
--
-- This function does not set any var to session.
--
-- @param dbh database connection
-- @param domain_uuid
-- @param fields list of avaliable channel variables.
--   if `context` provided then dialplan will be filtered by this var
--   `__api__`  key can be used to pass freeswitch.API object for execute
--   some functions in actions (e.g. `s=${user_data ...}`)
-- @param actions optional list of predefined actions
-- @return array part of table will contain list of actions.
--     `bridge` key will contain bridge statement
local function outbound_route_to_bridge(dbh, domain_uuid, fields, actions)
	actions = actions or {}

	local hostname = fields.hostname
	if not hostname  then
		require "resources.functions.trim";
		hostname = trim(api:execute("switchname", ""))
	end

	-- try filter by context
	local context = fields.context
	if context == '' then context = nil end

	--connect to the database
	if (dbh == nil) then
		local Database = require "resources.functions.database";
		dbh = Database.new('system');
	end

	local current_dialplan_uuid, extension
	dbh:query(select_outbound_dialplan_sql, {domain_uuid=domain_uuid, hostname=hostname}, function(route)
		if (route.dialplan_context ~= '${domain_name}') and (context and context ~= route.dialplan_context) then
			-- skip dialplan for wrong contexts
			return
		end

		if current_dialplan_uuid ~= route.dialplan_uuid then
			if extension then
				local n = #actions
				extension_to_bridge(extension, actions, fields)
				-- if we found bridge or add any action and there no continue flag
				if actions.bridge or (n > #actions and route.dialplan_continue == 'false') then
					extension = nil
					return 1
				end
			end
			extension = {}
			current_dialplan_uuid = route.dialplan_uuid
		end

		local group_no = tonumber(route.dialplan_detail_group)
		local tag      = route.dialplan_detail_tag
		local element = {
			type      =  route.dialplan_detail_type;
			data      =  route.dialplan_detail_data;
			break_on  =  route.dialplan_detail_break;
			inline    =  route.dialplan_detail_inline;
		}

		local group = extension[ group_no ] or {
			conditions   = {};
			actions      = {};
			anti_actions = {};
		}
		extension[ group_no ] = group

		if tag == 'condition'   then append(group.conditions,   element) end
		if tag == 'action'      then append(group.actions,      element) end
		if tag == 'anti-action' then append(group.anti_actions, element) end
	end)

	if extension and next(extension) then
		extension_to_bridge(extension, actions, fields)
	end

	if actions.bridge then return actions end
end

local function apply_vars(actions, fields)
	for i, action in ipairs(actions) do
		actions[i] = apply_var(action, fields)
	end
	return actions
end

local function wrap_dbh(t)
	return {query = function(self, sql, params, callback)
		local i = 0
		while true do
			i = i + 1
			local row = t[i]
			if not row then break end

			local result = callback(row)
			if result == 1 then break end
		end
	end}
end

-- Load all extension for outbound routes and
-- returns object which can be used instead real DBH object to build
-- dialplan for specific destination_number
local function preload_dialplan(dbh, domain_uuid, fields)
	local hostname = fields and fields.hostname
	if not hostname  then
		require "resources.functions.trim";
		hostname = trim(api:execute("switchname", ""))
	end

	-- try filter by context
	local context = fields and fields.context
	if context == '' then context = nil end

	local dialplan = {}
	dbh:query(select_outbound_dialplan_sql, {domain_uuid=domain_uuid, hostname=hostname}, function(route)
		if (route.dialplan_context ~= '${domain_name}') and (context and context ~= route.dialplan_context) then
			-- skip dialplan for wrong contexts
			return
		end
		dialplan[#dialplan + 1] = route
	end)

	return wrap_dbh(dialplan), dialplan
end

return setmetatable({
	__self_test = self_test;
	apply_vars = apply_vars;
	preload_dialplan = preload_dialplan;
}, {__call = function(_, ...)
	return outbound_route_to_bridge(...)
end})
