local log  = require "resources.functions.log".route_to_bridge

local pcre_match

if freeswitch then
	--! @todo find better way to extract captures
	api = api or freeswitch.API()
	local unpack = unpack or table.unpack
	function pcre_match(str, pat)
		local a = str .. "|/" .. pat .."/"
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
	local a, b, c
	a,b,c = pcre_match('abcd', '(\\d{3})(\\d{3})')
	assert(a == nil)

	a,b,c = pcre_match('123456', '(\\d{3})(\\d{3})')
	assert(a == '123', a)
	assert(b == '456', b)
end

local select_routes_sql = [[
select *
from v_dialplans
where (domain_uuid = :domain_uuid or domain_uuid is null)
  and (hostname = :hostname or hostname is null) 
  and app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
  and dialplan_enabled = 'true'
order by dialplan_order asc
]]

local select_extensions_sql = [[
select * from v_dialplan_details 
where dialplan_uuid = :dialplan_uuid
order by dialplan_detail_group asc, dialplan_detail_order asc
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

local function outbound_route_to_bridge(dbh, domain_uuid, fields)
	local actions, dial_string = {}
	require "resources.functions.trim";
	local hostname = trim(api:execute("switchname", ""));

	local params = {}
	dbh:query(select_routes_sql, {domain_uuid=domain_uuid,hostname=hostname}, function(route)
		local extension = {}
		params.dialplan_uuid = route.dialplan_uuid
		dbh:query(select_extensions_sql, params, function(ext)
			local group_no = tonumber(ext.dialplan_detail_group)
			local tag      = ext.dialplan_detail_tag
			local element = {
				type      =          ext.dialplan_detail_type;
				data      =          ext.dialplan_detail_data;
				break_on  =          ext.dialplan_detail_break;
				inline    =          ext.dialplan_detail_inline;
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

		local n = #actions

		extension_to_bridge(extension, actions, fields)

		if actions.bridge or (n > #actions and route.dialplan_continue == 'false') then
			return 1
		end
	end)

	if actions.bridge then return actions end
end

return setmetatable({
	__self_test = self_test;
}, {__call = function(_, ...)
	return outbound_route_to_bridge(...)
end})
