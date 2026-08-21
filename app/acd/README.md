# Advanced Call Distribution (ACD)

A lightweight, multi-tenant call-queue / contact-distribution app for FusionPBX
that drives FreeSWITCH directly from PostgreSQL + Lua. It is an alternative to
`mod_callcenter`: there is no queue state held in FreeSWITCH memory — all state
(queues, members, login status, live sessions) lives in the database, so
login/logout is instant and everything is inspectable in SQL.

## What you get

- **Queues** with an extension that is selectable as a destination everywhere
  (inbound routes, IVRs, time conditions, ring groups, etc.).
- **Tiered ringing** of logged-in agents, with a configurable tier-advance time.
- **Agents reached on extension + follow-me**, including Microsoft Teams users.
  First agent to answer is connected; the rest stop ringing.
- **Per-member Busy Handling**: `Always Ring`, `Never Ring (Any Call)`, or
  `Never Ring (Queue Calls Only)`.
- **Self-service login/logout** feature codes:
  - `*86` — toggle login/logout for **all** queues you belong to.
  - `*86<extension>` — toggle a **single** queue by its extension (e.g. `*86771`).
- **Live dashboard** (Active Calls) showing waiting callers, connected calls with
  talk time, and currently-ringing agent legs.

## FreeSWITCH modules used

`mod_lua` (all call logic), `mod_dptools` (`set`, `answer`, `playback`, `sleep`,
`intercept`), `mod_commands` (`show channels`, `uuid_*`, `originate`),
`mod_loopback` + `mod_sofia` (reach agents / trunks), `mod_local_stream`
(hold music), `mod_dialplan_xml`, `mod_event_socket` (dashboard), `mod_sndfile`.

## Install

The app self-installs through the standard FusionPBX flow:

1. Copy `app/acd` into the FusionPBX `app/` directory and the Lua scripts in
   `app/switch/resources/scripts/app/acd/` are deployed to
   `/usr/local/freeswitch/scripts/app/acd/` on the next switch-scripts sync.
2. Advanced → **Upgrade**: run **App Defaults**, **Schema**, **Permission
   Defaults**, and **Menu Defaults** (or the full upgrade). This creates the
   `v_acd_*` tables, the `acd_*` permissions, the menu, default settings, the
   queue destination type, and the per-domain `*86` feature-code dialplan.
3. Each queue you create automatically generates its own dialplan entry.

## Tables

- `v_acd_queues` — queue definitions
- `v_acd_queue_members` — agents per queue (tier, follow-me, busy handling, login flag)
- `v_acd_sessions` — one row per live caller in queue (the call anchor)
- `v_acd_busy` — transient ringing-leg tracking
