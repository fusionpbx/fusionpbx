-- -- Global settings
-- local settings = Settings.new('system')
-- print(settings:get('switch', 'base', 'dir'))
--
-- Domain settings (to `fax_retry.lua`)
--	local Settings = require "resources.functions.settings"
--	local settings = Settings.new(dbh, domain_name, domain_uuid)
--	storage_type = settings:get('fax', 'storage_type', 'text') or ''
--	storage_path = settings:get('fax', 'storage_path', 'text') or ''
--	storage_path = storage_path
--		:gsub("${domain_name}", domain_name)
--		:gsub("${voicemail_id}", voicemail_id)
--		:gsub("${voicemail_dir}", voicemail_dir)

local Database = require "resources.functions.database"
local cache    = require "resources.functions.cache"
require "resources.functions.split"

-----------------------------------------------------------
local Settings = {} do
Settings.__index = Settings

local NONE = '15783958-912c-4893-8866-4ccd1ca73c6e'

local function append(t, v)
	t[#t+1] = v
	return t
end

local function append_setting(array, category, subcategory, name, value)
	--add the category array
	if not array[category] then
		array[category] = {}
	end

	--add the subcategory array
	if not array[category][subcategory] then
		array[category][subcategory] = {}
	end

	--set the name and value
	if (name == "array") then
		if not array[category][subcategory][name] then
			array[category][subcategory][name] = {}
		end
		append(array[category][subcategory][name], value);
	elseif value ~= nil then
		array[category][subcategory][name] = value;
	end
end

function Settings.new(db, domain_name, domain_uuid)
	local self = setmetatable({}, Settings)
	self._array = {}
	self._db = db
	self._domain_name = domain_name
	self._domain_uuid = domain_uuid
	self._use_cache	 = not cache.settings

	return self
end

function Settings:_cache_key(category, subcategory, name)
	return 'setting:' .. (self._domain_name or '') .. ':' .. category .. ':' .. subcategory .. ':' .. name
end

function Settings:set(category, subcategory, name, value)
	append_setting(self._array, category, subcategory, name, value)
	return self
end

function Settings:get(category, subcategory, name)
	local a = self._array
	local v = a[category] and a[category][subcategory] and a[category][subcategory][name]
	if v == NONE then return nil end
	if v ~= nil then return v end

	if self._use_cache then
		local key = self:_cache_key(category, subcategory, name)

		v = cache.get(key)
		if v then
			if v ~= NONE and name == 'array' then
				v = split(v, '/+/', true)
			end
			self:set(category, subcategory, name, v)
			if v == NONE then return nil end
			return v
		end
	end

	return self:_load(category, subcategory, name)
end

function Settings:_load(category, subcategory, name)
	local domain_uuid = self._domain_uuid
	local db = self._db
	if type(self._db) == 'string' then
		db = Database.new(self._db)
	end

	local found = false

	--get the domain settings
	if domain_uuid then
		local sql = "SELECT domain_setting_uuid,domain_setting_category,domain_setting_subcategory,domain_setting_name,domain_setting_value "
		sql = sql .. "FROM v_domain_settings ";
		sql = sql .. "WHERE domain_uuid = :domain_uuid ";
		sql = sql .. "AND domain_setting_enabled = 'true' ";
		sql = sql .. "AND domain_setting_category = :category ";
		sql = sql .. "AND domain_setting_subcategory = :subcategory ";
		sql = sql .. "AND domain_setting_name = :name ";
		sql = sql .. "AND domain_setting_value is not null ";
		sql = sql .. "ORDER BY domain_setting_category, domain_setting_subcategory ASC ";
		local params = {
			domain_uuid = domain_uuid,
			category = category,
			subcategory = subcategory,
			name = name,
		};

		db:query(sql, params, function(row)
			found = true;
			self:set(
				row.domain_setting_category,
				row.domain_setting_subcategory,
				row.domain_setting_name,
				row.domain_setting_value
			)
		end)
	end

	--get default settings
	if not found then
		local sql = "SELECT default_setting_uuid,default_setting_category,default_setting_subcategory,default_setting_name,default_setting_value "
		sql = sql .. "FROM v_default_settings ";
		sql = sql .. "WHERE default_setting_enabled = 'true' ";
		sql = sql .. "AND default_setting_category = :category ";
		sql = sql .. "AND default_setting_subcategory = :subcategory ";
		sql = sql .. "AND default_setting_name = :name ";
		sql = sql .. "AND default_setting_value is not null ";
		sql = sql .. "ORDER BY default_setting_category, default_setting_subcategory ASC";
		local params = {
			category = category,
			subcategory = subcategory,
			name = name,
		};

		db:query(sql, params, function(row)
			found = true;
			self:set(
				row.default_setting_category,
				row.default_setting_subcategory,
				row.default_setting_name,
				row.default_setting_value
			)
		end)
	end

	if type(self._db) == 'string' then
		db:release()
	end

	--set empty value for unknown setting
	if not found then
		self:set(category, subcategory, name, NONE)
	end

	local a = self._array
	local v = a[category] and a[category][subcategory] and a[category][subcategory][name]

	--store settings in cache
	if self._use_cache and cache.support() then
		local key = self:_cache_key(category, subcategory, name)
		local value = v
		if v ~= NONE and name == 'array' then
			value = table.concat(v, '/+/')
		end
		local exp = expire and expire["settings"] or 3600
		cache.set(key, value, exp)
	end

	if v == NONE then return nil end
	return v
end

end
-----------------------------------------------------------

return Settings
