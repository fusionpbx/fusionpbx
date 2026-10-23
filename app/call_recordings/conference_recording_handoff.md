# Scoped conference recording handoff

This opt-in path is limited to one configured simple-conference destination.
It preserves each pre-conference participant recording, starts one UUID-named
recording shared by the room occurrence, verifies that recorder and both CDR
links, and only then stops the exact participant recorder. If FreeSWITCH 1.10
cannot stop a transferred recorder by its path, the recording-only `stop all`
fallback is allowed on that participant channel only after rechecking room
membership, the exact occurrence-owned conference recorder, both persisted
links, the preserved participant file, and that every `session_record` target
on the channel is the same intended path. Other media bugs and channels are
not touched.

## Persistent activation: thevoip.siegecom.net

Scope identifiers:

- Domain: `cafb544b-fb9f-4685-a6b8-e9873fdd3c12`
- Queue: extension `600`, queue UUID `ab8cf596-0eb6-480b-9cc1-05133103538b`
- Callback agent: `1009`, agent UUID `52456b5c-4f9a-4c23-b720-8387d7b77810`
- Simple conference: extension `200`, conference UUID `55bc7fc3-ea18-4833-850c-12f30079a751`
- Installed FreeSWITCH scripts root: `/usr/share/freeswitch/scripts`

Activation remains disabled until all preparation and verification below is
complete.

1. Save a new immutable backup directory containing the current source files,
   installed Lua scripts, conference-200 dialplan XML, call-center XML, the six
   scoped domain-setting rows (including absence), and `\d+` output for
   `v_recording_segment_links`. Keep the controlled-test snapshots unchanged.
2. Verify conference `200` has no members. Verify the retained link table has
   the columns and three indexes in
   `20260921_conference_recording_links_up.sql`. It already contains the ten
   retained controlled-test links, so do not apply the migration on this host.
   For a clean database, first run this migration with `psql -v ON_ERROR_STOP=1`
   after its successful disposable PostgreSQL validation.
3. Install the committed worker as
   `/usr/share/freeswitch/scripts/app/call_recordings/resources/scripts/conference_recording_handoff.lua`
   and the commit-`96d002bef` agent guard beside it. Install that commit's
   `callcenter.conf.lua` generator. Confirm ownership/mode match adjacent
   FreeSWITCH scripts and that the FreeSWITCH service account can read both.
4. Create enabled domain settings for the exact domain above, initially with
   both boolean gates false:

   - `recording_coordinator_enabled` / `boolean` / `false`
   - `recording_coordinator_agent` / `text` /
     `52456b5c-4f9a-4c23-b720-8387d7b77810`
   - `recording_coordinator_queue` / `text` /
     `600@thevoip.siegecom.net`
   - `recording_segment_links_enabled` / `boolean` / `false`
   - `recording_coordinator_conference` / `text` / `200`
   - `conference_recording_handoff_enabled` / `boolean` / `false`

5. Enable `recording_coordinator_enabled`, regenerate only the scoped queue
   and agent call-center configuration, and inspect agent 1009's effective
   contact. It must contain the absolute installed guard path and the exact
   queue scope. Clear only the call-center configuration cache and reload XML.
6. Enable `recording_segment_links_enabled` while the handoff gate remains
   false. Save only simple conference UUID
   `55bc7fc3-ea18-4833-850c-12f30079a751`. Inspect its rendered hook before
   loading it: the Lua application argument must begin with the absolute
   installed handoff path, pass that same path as the fifth argument, and be
   absent from every other destination. Clear only
   `dialplan:thevoip.siegecom.net` and reload XML.
7. Confirm the XML CDR detail user has `xml_cdr_details`,
   `xml_cdr_recording`, and `xml_cdr_recording_play` (plus
   `xml_cdr_recording_download` only if downloads are desired). Verify a
   retained controlled-test CDR renders both stored segment links.
8. Reconfirm conference 200 is empty and then enable
   `conference_recording_handoff_enabled` last. Re-read all six settings and
   inspect both effective hooks. This is the persistent activation boundary.

Rollback sets `conference_recording_handoff_enabled=false` first, then
`recording_segment_links_enabled=false` and
`recording_coordinator_enabled=false`. Restore only the backed-up installed
scripts, conference-200 dialplan, call-center configuration, and the six
setting rows; clear the same two scoped caches and reload XML. Do not drop
`v_recording_segment_links` or remove recordings, so retained playback links
survive rollback.

## Live acceptance, 2026-09-21

The full queue/transfer/conference sequence completed on
`thevoip.siegecom.net`: queue caller CDR
`7a608329-6573-4119-9e39-369777d971bd`, outbound recipient CDR
`730a24df-bdd1-4645-895d-8b41dc0121e8`, and agent-entry CDR
`a8be7042-5c3d-475c-bfd0-b2cc72934fab`. Each retained its separate
pre-conference link. All three linked the one shared occurrence recording
`2bde4767-09df-45e4-9afb-dc35490f094d.wav`. The queue and agent-entry exact
path stops succeeded. The transferred outbound participant exercised and
completed the guarded recording-only fallback. Six domain-scoped link rows
were readable through the authorized resolver.

Full decode checks passed for the three pre-conference recordings (46.64 s,
47.48 s, and 3.00 s) and shared conference recording (232.78 s). The user then
confirmed by listening that the recordings sound correct. Test activation was
rolled back; recordings and ten accumulated link rows were retained.

The FreeSWITCH 1.10.12 fallback probe created the two `*9196` CDRs at 4:56 PM
MDT: UUIDs `2076e9a4-666a-4e3d-9d0f-6708ae764be0` (park leg) and
`b8020bc6-361c-485c-8d9a-c42760b2a628` (echo leg). They share the probe's exact
22:56:04-22:56:45 UTC interval. The probe verified `uuid_record <uuid> stop
all` removed only `session_record`; its unrelated `displace` media bug and both
channels remained active. No committed hook, script, scheduled task, or
setting contains `*9196` or either probe UUID, so normal operation does not
originate those calls. The pre-existing global `*9196` echo feature remains
manually dialable.

The PHP tests are simulations/source contracts and the migration was also
validated on disposable PostgreSQL 17. The call sequence, FreeSWITCH command
behavior, persisted links, media decodes, authorized playback resolution, and
listening result above are live evidence.
