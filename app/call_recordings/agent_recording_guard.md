# Controlled Agent Recording Guard

This opt-in guard prevents the callback-agent recording hook from adding a
second `session_record` media bug when the matching queue recorder is already
active. It is disabled by default. It does not use the recording coordinator
database tables and does not require their migration.

## Scope and deployment

The guard is selected only when the domain settings below are all present and
enabled:

- `call_recordings/recording_coordinator_enabled/boolean = true`
- `call_recordings/recording_coordinator_agent/text = <call_center_agent_uuid>`
- `call_recordings/recording_coordinator_queue/text = <queue>@<domain>`

The generator compares the configured UUID with the callback-agent UUID, not
the display extension. At execution it verifies both `cc_agent` and `cc_queue`.
An unmatched queue call follows the pre-existing native `record_session` path.

For a narrow rollout, copy only these two scripts through
`switch_files::copy_script()` and clear only the call-center configuration
cache before reloading the selected agent and queue:

- `app/switch/resources/scripts/app/xml_handler/resources/scripts/configuration/callcenter.conf.lua`
- `app/switch/resources/scripts/app/call_recordings/resources/scripts/agent_recording_guard.lua`

The generator embeds the configured FreeSWITCH scripts directory while it
builds the contact. It must not leave `${scripts_dir}` for expansion on the
queue member channel.

## Siege-Com controlled test, 2026-09-17

- Queue-only: one queue recorder on the caller leg.
  `4847bd05-e066-477f-a8f7-77a244ead794.wav`, 29.26 seconds, mono, 8 kHz PCM.
- Agent-only: one agent recorder, with `active_verified` after the Lua guard
  started and observed its media bug.
  `4f5f0e63-a032-41c4-bfbd-9d00bfe79a9b.wav`, 22.12 seconds, stereo, 8 kHz PCM.
- Queue plus agent: one queue recorder and guard outcome `already_active`.
  `6934169e-acb2-4422-bca6-8cf65a7a47e6.wav`, 28.88 seconds, mono, 8 kHz PCM.

Each recording existed after hangup and was linked from the queue caller CDR.
The PHP status adapter and direct `uuid_buglist` agreed for the live snapshots.

## Limits

This only covers the tested callback-agent path. A recorder that exists before
queue entry can still be duplicated by native queue recording before this
pre-bridge guard runs. Unknown or malformed status suppresses an agent start
and can leave a requested call unrecorded; the guard exposes
`recording_coordinator_outcome`, `recording_coordinator_recheck_attempt`, and
`recording_coordinator_recheck_needed` for diagnosis. The test does not prove
anything about chipmunk audio, other recording entry points, transfers,
conference handoff, or persistent recording-history links.
