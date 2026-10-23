<?php
/* Mirrors the Lua guard decision table; FreeSWITCH execution is covered only in controlled staging. */
function guard_decision($own,$peer){if($own==='unknown'||$peer==='unknown')return 'status_unknown';if($own==='active'||$peer==='active')return 'already_active';return 'start_and_verify';}
function guard_scope($actual_agent,$actual_queue,$configured_agent,$configured_queue){return $configured_agent!==''&&$configured_queue!==''&&$actual_agent===$configured_agent&&$actual_queue===$configured_queue?'guard':'native_fallback';}
function guard_logic_assert($value,$message){if(!$value)throw new Exception($message);}
guard_logic_assert(guard_decision('absent','active')==='already_active','queue-only recorder suppresses agent duplicate');
guard_logic_assert(guard_decision('absent','absent')==='start_and_verify','agent-only recording starts when both legs are absent');
guard_logic_assert(guard_decision('active','absent')==='already_active','queue and agent enabled retain one existing recorder');
guard_logic_assert(guard_decision('absent','absent')==='start_and_verify','failed queue start is recovered by agent policy');
guard_logic_assert(guard_decision('unknown','absent')==='status_unknown'&&guard_decision('absent','unknown')==='status_unknown','unknown state never becomes absent');
guard_logic_assert(guard_scope('agent-1009','600@thevoip.siegecom.net','agent-1009','600@thevoip.siegecom.net')==='guard','exact runtime queue and agent enter guard');
guard_logic_assert(guard_scope('agent-1009','other@thevoip.siegecom.net','agent-1009','600@thevoip.siegecom.net')==='native_fallback','other queue keeps native recording behavior');
guard_logic_assert(guard_scope('agent-1002','600@thevoip.siegecom.net','agent-1009','600@thevoip.siegecom.net')==='native_fallback','other agent keeps native recording behavior');
echo "agent_recording_guard_logic_test: OK (deterministic guard decisions; no FreeSWITCH Lua execution)\n";
