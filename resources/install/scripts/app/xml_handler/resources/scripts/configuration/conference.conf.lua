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

--set the xml array
	local xml = {}
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[	<section name="configuration">]]);
	table.insert(xml, [[		<configuration name="conference.conf" description="Audio Conference">]]);
	table.insert(xml, [[			<caller-controls>]]);
	table.insert(xml, [[				<group name="default">]]);
	table.insert(xml, [[					<control action="mute" digits=""/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[				<group name="moderator">]]);
	table.insert(xml, [[					<control action="mute" digits=""/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[					<control action="execute_application" digits="0" data="lua app/conference_center/resources/scripts/mute.lua non_moderator"/>]]);
	table.insert(xml, [[					<control action="execute_application" digits="*" data="lua app/conference_center/resources/scripts/unmute.lua non_moderator"/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[				<group name="page">]]);
	table.insert(xml, [[					<control action="mute" digits="0"/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[			</caller-controls>]]);
	table.insert(xml, [[			<profiles>]]);

	table.insert(xml, [[				<profile name="default">]]);
	table.insert(xml, [[					<param name="cdr-log-dir" value="auto"/>]]);
	table.insert(xml, [[					<param name="conference-flags" value="livearray-sync" />]]);
	table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="8000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="15"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
	table.insert(xml, [[					<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[					<param name="auto-record" value="]] .. temp_dir:gsub("\\","/") .. [[/test.wav"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="wideband">]]);
	table.insert(xml, [[					<param name="cdr-log-dir" value="auto"/>]]);
	table.insert(xml, [[					<param name="conference-flags" value="livearray-sync" />]]);
	table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="16000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="15"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
	table.insert(xml, [[					<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[					<param name="auto-record" value="]] .. temp_dir:gsub("\\","/") .. [[/test.wav"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="ultrawideband">]]);
	table.insert(xml, [[					<param name="cdr-log-dir" value="auto"/>]]);
	table.insert(xml, [[					<param name="conference-flags" value="livearray-sync" />]]);
	table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="32000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="15"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
	table.insert(xml, [[					<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[					<param name="auto-record" value="]] .. temp_dir:gsub("\\","/") .. [[/test.wav"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="cdquality">]]);
	table.insert(xml, [[					<param name="cdr-log-dir" value="auto"/>]]);
	table.insert(xml, [[					<param name="conference-flags" value="livearray-sync" />]]);
	table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="48000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="15"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
	table.insert(xml, [[					<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[					<param name="auto-record" value="]] .. temp_dir:gsub("\\","/") .. [[/test.wav"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="sla">]]);
	--table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="16000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="300"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="none"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="none"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="silence"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="page">]]);
	--table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="rate" value="8000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="300"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="page"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="moderator"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[				<profile name="wait-mod">]]);
	--table.insert(xml, [[					<param name="domain" value="$${domain}"/>]]);
	table.insert(xml, [[					<param name="cdr-log-dir" value="auto"/>]]);
	table.insert(xml, [[					<param name="conference-flags" value="wait-mod,livearray-sync" />]]);
	table.insert(xml, [[					<param name="rate" value="8000"/>]]);
	table.insert(xml, [[					<param name="interval" value="20"/>]]);
	table.insert(xml, [[					<param name="energy-level" value="15"/>]]);
	table.insert(xml, [[					<param name="auto-gain-level" value="0"/>]]);
	table.insert(xml, [[					<param name="caller-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="moderator-controls" value="default"/>]]);
	table.insert(xml, [[					<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
	table.insert(xml, [[					<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
	table.insert(xml, [[					<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
	table.insert(xml, [[					<param name="moh-sound" value="local_stream://default"/>]]);
	table.insert(xml, [[					<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
	table.insert(xml, [[					<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
	table.insert(xml, [[					<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
	table.insert(xml, [[					<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
	table.insert(xml, [[					<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
	table.insert(xml, [[					<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
	table.insert(xml, [[					<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
	table.insert(xml, [[					<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
	table.insert(xml, [[					<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
	table.insert(xml, [[					<param name="comfort-noise" value="true"/>]]);
	table.insert(xml, [[				</profile>]]);

	table.insert(xml, [[			</profiles>]]);

--set the xml array and then concatenate the array to a string
	table.insert(xml, [[		</configuration>]]);
	table.insert(xml, [[	</section>]]);
	table.insert(xml, [[</document>]]);
	XML_STRING = table.concat(xml, "\n");

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .."/conference.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
