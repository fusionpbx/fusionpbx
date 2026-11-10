<?php
$generator=file_get_contents(dirname(__DIR__,2).'/switch/resources/scripts/app/xml_handler/resources/scripts/configuration/callcenter.conf.lua');
$guard=file_get_contents(dirname(__DIR__,2).'/switch/resources/scripts/app/call_recordings/resources/scripts/agent_recording_guard.lua');
function guard_assert($value,$message){if(!$value)throw new Exception($message);}
guard_assert(strpos($generator,"recording_coordinator_enabled == true")!==false,'feature gate selects guard');
guard_assert(strpos($generator,"Settings.new(dbh, domain_name, domain_uuid)")!==false,'feature gate is resolved for each agent domain');
guard_assert(strpos($generator,"recording_coordinator_agent == agent_uuid")!==false&&strpos($generator,"recording_coordinator_queue")!==false,'guard contact is limited to an explicit callback agent UUID and queue');
guard_assert(strpos($generator,'agent_recording_guard.lua ${uuid}')!==false,'guard receives original filename UUID expansion');
guard_assert(strpos($generator,'lua "..scripts_dir.."/app/call_recordings/resources/scripts/agent_recording_guard.lua')!==false,'generator resolves scripts_dir while building the contact');
guard_assert(strpos($generator,'lua ${scripts_dir}/app/call_recordings/resources/scripts/agent_recording_guard.lua')===false,'contact never leaves an unresolved scripts_dir placeholder');
guard_assert(strpos($generator,"record = string.format(\",execute_on_pre_bridge='record_session")!==false,'feature off retains native record_session');
guard_assert(strpos($guard,'bridge_uuid')!==false&&strpos($guard,'uuid_buglist')!==false,'guard checks both bridge legs');
guard_assert(strpos($guard,'status_unknown')!==false&&strpos($guard,'start_unverified')!==false,'unknown and unverified states fail closed');
guard_assert(strpos($guard,'<function>%s*session_record%s*</function>')!==false,'guard matches function element rather than filename');
guard_assert(strpos($guard,'recording_coordinator_recheck_attempt')!==false&&strpos($guard,'attempts < 2')!==false,'rechecking is bounded and diagnostic');
guard_assert(strpos($guard,'actual_agent ~= scoped_agent')!==false&&strpos($guard,'actual_queue ~= scoped_queue')!==false,'Lua verifies the runtime agent and queue before deduplicating');
guard_assert(strpos($guard,'scope_unmatched')!==false&&strpos($guard,'native_record(target_uuid)')!==false,'out-of-scope calls retain the native recording start');
echo "agent_recording_guard_contract_test: OK (source contract; Lua runtime requires controlled FreeSWITCH call)\n";
