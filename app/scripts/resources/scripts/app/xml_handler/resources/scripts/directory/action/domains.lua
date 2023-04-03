--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--include xml library
	local Xml = require "resources.functions.xml";

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--process when the sip profile is rescanned, sofia is reloaded, or sip redirect
	local xml = Xml:new();
	xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	xml:append([[<document type="freeswitch/xml">]]);
	xml:append([[	<section name="directory">]]);
	local sql = "SELECT domain_name FROM v_domains ";
	dbh:query(sql, function(row)
		xml:append([[		<domain name="]] .. xml.sanitize(row.domain_name) .. [[" />]]);
	end);
	xml:append([[	</section>]]);
	xml:append([[</document>]]);
	XML_STRING = xml:build();

--close the database connection
	dbh:release();
