
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
	local xml = {}
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]])
	table.insert(xml, [[<document type="freeswitch/xml">]])
	table.insert(xml, [[	<section name="directory">]])

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
				table.insert(xml, [[					</users>]])
				table.insert(xml, [[				</group>]])
				table.insert(xml, [[			</groups>]])
				table.insert(xml, [[		</domain>]])
			end
			prev_domain_name = row.domain_name
			table.insert(xml, [[		<domain name="]] .. row.domain_name .. [[" alias="true">]])
			table.insert(xml, [[			<groups>]])
			table.insert(xml, [[				<group name="default">]])
			table.insert(xml, [[					<users>]])
		end

		row.sip_from_user   = row.extension
		row.sip_from_number = (#number_alias > 0) and number_alias or row.extension
		local number_alias_string = ''
		if #row.number_alias > 0 then
			number_alias_string = ' number-alias="' .. row.number_alias .. '"'
		end

		table.insert(xml, [[						<user id="]] .. row.extension .. [["]] .. number_alias_string .. [[>]]);
		table.insert(xml, [[							<params>]])
		for name, param in pairs(params) do
			if row[name] and #row[name] > 0 then
				table.insert(xml, [[								<param name="]] .. param .. [[" value="]] .. row[name] .. [["/>]])
			end
		end
		table.insert(xml, [[							</params>]])
		table.insert(xml, [[							<variables>]])
		for name, param in pairs(variables) do
			if row[name] and #row[name] > 0 then
				table.insert(xml, [[								<variable name="]] .. param .. [[" value="]] .. row[name] .. [["/>]])
			end
		end
		table.insert(xml, [[							</variables>]])
		table.insert(xml, [[						</user>]])
	end)

	if prev_domain_name then
		table.insert(xml, [[					</users>]])
		table.insert(xml, [[				</group>]])
		table.insert(xml, [[			</groups>]])
		table.insert(xml, [[		</domain>]])
	end

	table.insert(xml, [[	</section>]])
	table.insert(xml, [[</document>]])

	XML_STRING = table.concat(xml, "\n")
	if (debug["xml_string"]) then
		log.notice("XML_STRING "..XML_STRING)
	end

--close the database connection
	dbh:release()
