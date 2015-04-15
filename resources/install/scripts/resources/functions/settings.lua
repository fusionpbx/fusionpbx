
--debug
	debug["sql"] = false;

--define trim
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end

--get the domain_uuid
	if (domain_uuid == nil) then
		if (domain_name ~= nil) then
			sql = "SELECT domain_uuid FROM v_domains ";
			sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(rows)
				domain_uuid = string.lower(rows["domain_uuid"]);
				end);
		end
	end

--define is_array function
	function is_array(table)
		local max = 0;
		local count = 0;
		if (table) then
			for k, v in pairs(table) do
				if type(k) == "number" then
					if k > max then max = k end
					count = count + 1;
				else
					return false;
				end
			end
		else
			return false;
		end
		if (max > count * 2) then
			return false;
		end
		return max;
	end

--define select_entry function 
	function settings(domain_uuid)

		--define the table
			array = {}

		--get the default settings
			sql = "SELECT * FROM v_default_settings ";
			sql = sql .. "WHERE default_setting_enabled = 'true' ";
			sql = sql .. "ORDER BY default_setting_category, default_setting_subcategory ASC";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "SQL: " .. sql .. "\n");
			end
			x = 1;
			previous_category = '';
			dbh:query(sql, function(row)
				--variables
					setting_uuid = row.default_setting_uuid
					category = row.default_setting_category;
					subcategory = row.default_setting_subcategory;
					name = row.default_setting_name;
					value = row.default_setting_value;
				
				--add the category array
					if (previous_category ~= category) then
						array[category] = {}
					end

				--add the subcategory array
					if (previous_subcategory ~= subcategory) then
						array[category][subcategory] = {}
						x = 1;
					end

				--set the name and value
					if (name == "array") then
						array[category][subcategory][x] = {}
						array[category][subcategory][x] = value;
					else
						array[category][subcategory][name] = value;
					end

				--set the previous category
					previous_category = category;

				--set the previous subcategory
					previous_subcategory = subcategory;

				--increment the value of x
					x = x + 1;
			end);

		--get the domain settings
			if (domain_uuid ~= nil) then
				sql = "SELECT * FROM v_domain_settings ";
				sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .. "' ";
				sql = sql .. "AND domain_setting_enabled = 'true' ";
				sql = sql .. "ORDER BY domain_setting_category, domain_setting_subcategory ASC ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[directory] SQL: " .. sql .. "\n");
				end
				dbh:query(sql, function(row)
					--variables
						setting_uuid = row.domain_setting_uuid
						category = row.domain_setting_category;
						subcategory = row.domain_setting_subcategory;
						name = row.domain_setting_name;
						value = row.domain_setting_value;
					
					--add the category array
						array[category] = {}

					--add the subcategory array
						array[category][subcategory] = {}
	
					--add the subcategory array
						if (previous_subcategory ~= subcategory) then
							x = 1;
						end

					--set the name and value
						if (name == "array") then
							array[category][subcategory][x] = {}
							array[category][subcategory][x] = value;
						else
							array[category][subcategory][name] = value;
						end

					--set the previous category
						previous_category = category;

					--set the previous subcategory
						previous_subcategory = subcategory;

				end);
			end

		--return the array
			return array;
	end

--example use
	--result = array['email']['smtp_host']['var'];
	--freeswitch.consoleLog("notice", result .. "\n");
