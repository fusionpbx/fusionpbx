---
-- @tparam table dict Dictionary
-- @tparam[opt='en'] string language default language
-- @tparam[opt='us'] string dialect default language
-- @return[1] nil if key is unknown
-- @return[2] empty string if language/dialect unknown or there no appropriate value for default language/dialect
-- @return[3] translated value accordint dictionary/language/dialect
--
-- @usage
-- local dict = {
-- 	['label-text'] = {
-- 		['en-us'] = 'text';
-- 		['ru-ru'] = 'текст';
-- 	}
-- }
-- local text = Text.new(dict)
-- -- use global `default_language` and `default_dialect` to resolve language
-- var = text['label-attached']
-- -- use prefix form
-- var = text'label-attached'
-- -- Implicit specify language
-- var = text('label-attached', 'ru', 'ru')
-- -- set global variables(you can set them even after ctor call)
-- default_language, default_dialect = 'ru', 'ru'
-- var = text['label-attached']
local function make_text(dict, language, dialect)
	if not (language and dialect) then
		language, dialect = 'en', 'us'
	end

	if type(dict) == 'string' then
		dict = require(dict)
	end

	local default = (language .. '-' .. dialect):lower()

	local function index(_, k)
		local t = dict[k]
		if not t then return end

		local lang
		if default_language and default_dialect then
			lang = (default_language .. '-' .. default_dialect):lower()
		end
		if not lang then lang = default end
		return t[lang] or t[default] or ''
	end

	local function call(self, k, language, dialect)
		if language and dialect then
			local t = dict[k]
			if not t then return end
			local lang = (language .. '-' .. dialect):lower()
			local v = t[lang]
			if v then return v end
		end
		return self[k]
	end

	return setmetatable({},{
		__newindex = function()
			error('Can not add field to proxy')
		end;
		__index = index;
		__call  = call;
	})
end

return {
	new = make_text;
}