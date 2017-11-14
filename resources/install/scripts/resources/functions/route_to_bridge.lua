local log  = require "resources.functions.log".route_to_bridge

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

local select_outbound_dialplan_sql = [[SELECT
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

		if (not value) and (condition.type ~= '') then -- skip unkonw fields
			log.errf('Unsupportded condition: %s', condition.type)
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
		if break_on == 'always'  then break end
		if break_on ~= 'never' then
			if pass and break_on == 'on-true' then break end
			if not pass and (break_on == 'on-false' or break_on == '') then break end
		end

		break_on = nil
	end

	-- we shuld execute action/anti-action only if we check ALL conditions
	local act
	if last then act = pass and 'action' or 'anti-action' end

	-- we shuld break
	return act, not not break_on, matches
end

local function apply_match(s, match)
	return string.gsub(s, "%$(%d)", function(i)
		return match[tonumber(i)] or ''
	end)
end

local function group_to_bridge(actions, group, fields)
	local action, do_break, matches = check_conditions(group, fields)
	if action then
		local t = (action == 'action') and group.actions or group.anti_actions
		for _, element in ipairs(t) do
			local value = element.data
			if element.type == 'export' and string.sub(value, 1, 8) == 'nolocal:' then
				value = string.sub(value, 9)
			end

			-- we only support action/export
			if element.type == 'export' or element.type == 'set' then
				value = apply_match(value, matches)
				append(actions, value)
			end

			if element.type == 'bridge' then
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
end

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

	local current_dialplan_uuid, extension
	dbh:query(select_outbound_dialplan_sql, {domain_uuid=domain_uuid, hostname=hostname}, function(route)
		if context and context ~= route.dialplan_context then
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
		actions[i] = string.gsub(action, '%${(.-)}', function(var)
			local value = fields[var] or ''
			if value == '' then return "''" end
			if not string.find(value, "[',]") then return value end
			return "'" .. string.gsub(value, "'", "\\'") .. "'"
		end)
	end
	return actions
end

local function wrap_dbh(t)
	local i = 0
	return {query = function(self, s, p, f)
		while true do
			i = i + 1
			local row = t[i]
			if not row then break end

			local r = f(row)
			if r == 1 then break end
		end
	end}
end

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
		if context and context ~= route.dialplan_context then
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
