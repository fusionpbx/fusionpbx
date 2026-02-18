# FusionPBX Eloquent Models - Architecture Overview

## Model Relationships Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                            FusionPBX Database                            │
│                         Laravel Eloquent Models                          │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────┐
│   Domain     │ (Multi-tenant root)
│ v_domains    │
└──────┬───────┘
       │
       ├─── hasMany ──────┬─────────────────────────────────────┐
       │                  │                                      │
       ▼                  ▼                                      ▼
   ┌────────┐      ┌──────────┐                         ┌──────────┐
   │  User  │      │Extension │                         │ Device   │
   │v_users │      │v_extensions│                       │v_devices │
   └───┬────┘      └────┬─────┘                         └────┬─────┘
       │                │                                     │
       │                ├─── hasOne ──────────┐              │
       │                │                      ▼              │
       │                │                 ┌──────────┐       │
       │                │                 │Voicemail │       │
       │                │                 │v_voicemails│    │
       │                │                 └──────────┘       │
       │                │                                     │
       │                ├─── hasMany ─────────────┐          │
       │                │                          ▼          │
       │                │                    ┌──────────────┐│
       │                │                    │DeviceLine    ││
       │                │                    │v_device_lines││
       │                └────────────────────┴──────────────┘│
       │                                                      │
       ├─── belongsToMany ────────────┐                     │
       │                               │                     │
       ▼                               ▼                     ▼
   ┌────────┐                    ┌──────────┐        ┌──────────┐
   │ Group  │◄──belongsToMany────┤Extension │        │DeviceKey │
   │v_groups│                    │          │        │v_device_keys│
   └────────┘                    └──────────┘        └──────────┘


CALL CENTER MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────────────┐              ┌──────────────────┐
   │CallCenterQueue   │              │CallCenterAgent   │
   │v_call_center_    │              │v_call_center_    │
   │      queues      │              │      agents      │
   └────────┬─────────┘              └─────────┬────────┘
            │                                   │
            │          ┌──────────────────┐    │
            └────┬────►│CallCenterTier    │◄───┘
                 │     │v_call_center_tiers│
                 │     └──────────────────┘
                 │     (links agents to queues)
                 │
                 ▼
        ┌─────────────┐
        │  Dialplan   │
        │v_dialplans  │
        └─────────────┘


CONFERENCE MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────────┐
   │  Conference  │
   │v_conferences │
   └──────┬───────┘
          │
          ├─── hasMany ──────┬──────────────────────┐
          │                  │                       │
          ▼                  ▼                       ▼
   ┌───────────────┐  ┌──────────────┐    ┌─────────────┐
   │ConferenceUser │  │ConferenceSession│  │  Dialplan   │
   │v_conference_  │  │v_conference_    │  │v_dialplans  │
   │     users     │  │    sessions     │  └─────────────┘
   └───────────────┘  └──────────────┘


IVR & ROUTING MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────┐                      ┌───────────┐
   │ IvrMenu  │                      │RingGroup  │
   │v_ivr_menus│                     │v_ring_groups│
   └────┬─────┘                      └─────┬─────┘
        │                                   │
        ├─── hasMany ────────┐             ├─── hasMany ──────┬────────┐
        │                     │             │                   │        │
        ▼                     ▼             ▼                   ▼        ▼
   ┌──────────┐        ┌─────────┐   ┌──────────┐     ┌────────────┐  │
   │IvrMenuOption│      │Dialplan │   │RingGroup │     │RingGroup   │  │
   │v_ivr_menu_│        │v_dialplans│ │Destination│    │   User     │  │
   │  options  │        └─────────┘   │v_ring_group│   │v_ring_group│  │
   └──────────┘                        │_destinations│  │  _users    │  │
                                       └──────────┘     └────────────┘  │
                                                                          │
                                                        ┌─────────────────┘
                                                        │
                                                        ▼
                                                   ┌────────┐
                                                   │  User  │
                                                   │v_users │
                                                   └────────┘


CONTACT MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────┐
   │ Contact  │
   │v_contacts│
   └────┬─────┘
        │
        ├─── hasMany ──────────┬────────────────┬──────────────┬──────────┐
        │                      │                │              │          │
        ▼                      ▼                ▼              ▼          ▼
   ┌──────────┐        ┌──────────┐     ┌──────────┐  ┌──────────┐ ┌──────────┐
   │ContactPhone│       │ContactAddress│ │ContactEmail││ContactNote││ContactAttachment│
   │v_contact_│         │v_contact_│     │v_contact_│  │v_contact_│ │v_contact_│
   │  phones  │         │ addresses│     │  emails  │  │  notes   │ │attachments│
   └──────────┘         └──────────┘     └──────────┘  └──────────┘ └──────────┘


CDR & LOGGING MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────┐
   │  XmlCdr  │ (Call Detail Records)
   │v_xml_cdr │
   └────┬─────┘
        │
        ├─── belongsTo ──────┬──────────────┐
        │                    │              │
        ▼                    ▼              ▼
   ┌────────┐          ┌──────────┐  ┌─────────┐
   │ Domain │          │Extension │  │  User   │
   │v_domains│         │v_extensions│ │v_users  │
   └────────┘          └──────────┘  └─────────┘


MEDIA & FAX MODELS
─────────────────────────────────────────────────────────────────────

   ┌──────────────┐        ┌──────────────┐        ┌──────────────┐
   │  Recording   │        │ MusicOnHold  │        │     Fax      │
   │v_recordings  │        │v_music_on_hold│       │    v_fax     │
   └──────────────┘        └──────────────┘        └──────┬───────┘
                                                           │
                                                           ├─── hasMany ───┐
                                                           │               │
                                                           ▼               ▼
                                                     ┌──────────┐   ┌─────────┐
                                                     │ FaxFile  │   │Dialplan │
                                                     │v_fax_files│  │v_dialplans│
                                                     └──────────┘   └─────────┘
```

## Model Categories

### Core System (4 models)
- **Domain**: Multi-tenant domains/organizations
- **User**: User accounts with authentication
- **Group**: Permission groups
- **Contact**: Contact information management

### Communication (9 models)
- **Extension**: SIP extensions
- **Voicemail**: Voicemail boxes
- **Device**: SIP devices (phones)
- **Gateway**: SIP gateways/trunks
- **Dialplan**: Call routing
- **Recording**: Audio recordings
- **MusicOnHold**: Hold music categories
- **Fax**: Fax extensions
- **FollowMe**: Follow-me routing

### Call Management (6 models)
- **CallCenterQueue**: Call queue definitions
- **CallCenterAgent**: Call center agents
- **CallCenterTier**: Agent-to-queue assignments
- **Conference**: Conference rooms
- **IvrMenu**: IVR menus
- **RingGroup**: Ring group configurations

### Supporting (40+ models)
Extension-related, device-related, contact-related, voicemail-related, etc.

## Common Relationships

### One-to-Many (hasMany/belongsTo)
```php
// Domain has many Extensions
$domain->extensions;

// Extension belongs to Domain
$extension->domain;
```

### One-to-One (hasOne/belongsTo)
```php
// Extension has one Voicemail
$extension->voicemail;
```

### Many-to-Many (belongsToMany)
```php
// User belongs to many Groups
$user->groups;

// User has many Extensions (through pivot)
$user->extensions;
```

## Query Examples

### Navigate Relationships
```php
// Get domain with all users and their extensions
$domain = Domain::with(['users.extensions'])->find($uuid);

// Get extension with voicemail and device lines
$ext = Extension::with(['voicemail', 'deviceLines'])->find($uuid);

// Get call center queue with all agents
$queue = CallCenterQueue::with('tiers.agent')->find($uuid);
```

### Filter by Domain (Multi-tenancy)
```php
// All models support domain filtering
$extensions = Extension::forDomain($domain_uuid)->get();
$users = User::forDomain($domain_uuid)->get();
$conferences = Conference::forDomain($domain_uuid)->get();
```

### Enabled/Disabled Status
```php
// Filter enabled items
$extensions = Extension::enabled()->get();

// Filter disabled items
$devices = Device::disabled()->get();
```

## Database Support

All models support:
- **PostgreSQL** (default, recommended)
- **MySQL** (fully supported)
- **SQLite** (supported)

## Key Features

1. **UUID Primary Keys**: All models use UUIDs instead of auto-increment
2. **No Timestamps**: Uses FusionPBX's insert_date/update_date pattern
3. **Multi-tenant**: Built-in domain filtering support
4. **Relationships**: Pre-configured for easy navigation
5. **Type Casting**: Automatic casting for dates, booleans, integers
6. **Query Scopes**: Reusable query filters
7. **Mass Assignment**: Protected with fillable arrays

## Best Practices

### Always Filter by Domain
```php
// Good - secure multi-tenant query
Extension::forDomain($domain_uuid)->get();

// Bad - returns all domains (security risk)
Extension::all();
```

### Use Eager Loading
```php
// Good - single query
Extension::with('voicemail')->get();

// Bad - N+1 queries
$extensions = Extension::all();
foreach ($extensions as $ext) {
    $ext->voicemail; // Triggers separate query
}
```

### Use Transactions
```php
DB::beginTransaction();
try {
    Extension::create([...]);
    Voicemail::create([...]);
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
}
```

## Learn More

- **Quick Start**: `app/models/QUICKSTART.md`
- **Full Documentation**: `app/models/README.md`
- **Examples**: `app/models/examples.php`
- **Test Suite**: `php app/models/test.php`
