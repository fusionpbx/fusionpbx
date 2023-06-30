
--connect to the database
	local Database = require "resources.functions.database"
	local log      = require "resources.functions.log"["directory_dir"]
	local dbh = Database.new('system')

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--build the xml
	local xml = Xml:new();
	xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]])
	xml:append([[<document type="freeswitch/xml">]])
	xml:append([[	<section name="directory">]])

--process when the sip profile is rescanned, sofia is reloaded, or sip redirect
	local sql = "SELECT * FROM v_domains as d, v_extensions as e "
	sql = sql .. "where d.domain_uuid = e.domain_uuid "
	sql = sql .. "and (e.directory_visible = 'true' or e.directory_exten_visible='true') "
	if domain_name then
		sql = sql .. "and d.domain_name = :domain_name "
	else
		sql = sql .. "order by d.domain_name "
	end
	local sql_params = {domain_name = domain_name}

	if debug['sql'] then
		log.noticef("SQL: %s; params: %s", sql, json.encode(sql_params))
	end

-- export this params
	local params = {
		directory_visible       = "directory-visible";
		directory_exten_visible = "directory-exten-visible";
	}

-- export this variables
	local variables = {
		effective_caller_id_name = "effective_caller_id_name";
		directory_full_name      = "directory_full_name";
	}

	local prev_domain_name

	dbh:query(sql, sql_params, function(row)
		if prev_domain_name ~= row.domain_name then
			if prev_domain_name then
				xml:append([[					</users>]])
				xml:append([[				</group>]])
				xml:append([[			</groups>]])
				xml:append([[		</domain>]])
			end
			prev_domain_name = row.domain_name
			xml:append([[		<domain name="]] .. xml.sanitize(row.domain_name) .. [[" alias="true">]])
			xml:append([[			<groups>]])
			xml:append([[				<group name="default">]])
			xml:append([[					<users>]])
		end

		row.sip_from_user   = row.extension
		row.sip_from_number = (#number_alias > 0) and number_alias or row.extension
		local number_alias_string = ''
		if #row.number_alias > 0 then
			number_alias_string = ' number-alias="' .. xml.sanitize(row.number_alias) .. '"'
		end

		xml:append([[						<user id="]] .. xml.sanitize(row.extension) .. [["]] .. number_alias_string .. [[>]]);
		xml:append([[							<params>]])
		for name, param in pairs(params) do
			if row[name] and #row[name] > 0 then
				xml:append([[								<param name="]] .. xml.sanitize(param) .. [[" value="]] .. xml.sanitize(row[name]) .. [["/>]])
			end
		end
		xml:append([[							</params>]])
		xml:append([[							<variables>]])
		for name, param in pairs(variables) do
			if row[name] and #row[name] > 0 then
				xml:append([[								<variable name="]] .. xml.sanitize(param) .. [[" value="]] .. xml.sanitize(row[name]) .. [["/>]])
			end
		end
		xml:append([[							</variables>]])
		xml:append([[						</user>]])
	end)

	if prev_domain_name then
		xml:append([[					</users>]])
		xml:append([[				</group>]])
		xml:append([[			</groups>]])
		xml:append([[		</domain>]])
	end

	xml:append([[	</section>]])
	xml:append([[</document>]])
	XML_STRING = xml:build();

	if (debug["xml_string"]) then
		log.notice("XML_STRING "..XML_STRING)
	end

--close the database connection
	dbh:release()
