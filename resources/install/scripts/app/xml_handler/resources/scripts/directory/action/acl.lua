--connect to the database
	local Database = require "resources.functions.database"
	local log      = require "resources.functions.log"["directory_acl"]
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
	sql = sql .. "where d.domain_uuid = e.domain_uuid and e.cidr is not null and e.cidr <> '' "
	if domain_name then
		sql = sql .. "and d.domain_name = :domain_name "
	else
		sql = sql .. "order by d.domain_name"
	end
	local params = {domain_name = domain_name}

	if debug['sql'] then
		log.noticef("SQL: %s; params: %s", sql, json.encode(params))
	end

	local prev_domain_name

	dbh:query(sql, params, function(row)
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

		local cidr = (#row.cidr > 0) and (' cidr="' .. row.cidr .. '"') or ''
		table.insert(xml, [[						<user id="]] .. row.extension .. [["]] .. cidr .. [[/>]])
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
