# FusionPBX Filament Admin Panel

A modern, beautiful admin panel for FusionPBX built with [Filament v3](https://filamentphp.com/).

## Overview

This integration adds a complete admin panel to FusionPBX using Filament v3, providing:
- Modern, responsive UI
- Complete CRUD operations for all resources
- Advanced filtering and searching
- Bulk operations
- Multi-tenant support
- Role-based access control
- Real-time notifications

## Installation

### 1. Install Dependencies

```bash
cd /path/to/fusionpbx
composer install
```

This will install:
- `filament/filament` - Main Filament package
- `filament/tables` - Table builder
- `filament/forms` - Form builder
- `filament/notifications` - Notification system
- `filament/widgets` - Dashboard widgets

### 2. Configure Web Server

#### Apache

Add this to your Apache configuration or `.htaccess`:

```apache
# Filament Admin Panel
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^admin(/.*)?$ app/filament/public/index.php [L]
</IfModule>
```

#### Nginx

Add this to your Nginx configuration:

```nginx
location /admin {
    try_files $uri $uri/ /app/filament/public/index.php?$query_string;
}
```

### 3. Create Storage Directories

```bash
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/logs
chmod -R 775 storage
```

## Available Resources

### System Resources

1. **Domains** - Manage multi-tenant domains
   - Create, view, edit, delete domains
   - Enable/disable domains
   - Parent-child domain hierarchy
   - View user and extension counts

2. **Users** - User account management
   - User authentication
   - Permission management via groups
   - User settings and preferences
   - Multi-domain support
   - Password management

3. **Groups** - User groups and permissions
   - Create permission groups
   - Assign users to groups
   - Group-level permissions
   - Protected system groups

### Communication Resources

4. **Extensions** - SIP extension management
   - Extension number and password
   - Caller ID configuration
   - Directory settings
   - Voicemail integration
   - Device registration

### Additional Resources (Coming Soon)

- **Voicemail** - Voicemail box management
- **Devices** - Phone/device provisioning
- **Gateways** - SIP trunk/gateway management
- **Call Center Queues** - Call center queue management
- **Conferences** - Conference room management
- **IVR Menus** - IVR menu builder
- **Contacts** - Contact management
- **Dashboards** - Custom dashboard management
- **Call Detail Records** - CDR viewing and reporting

## Usage

### Accessing the Admin Panel

Navigate to: `https://your-fusionpbx-domain/admin`

### Creating Resources

1. Navigate to the desired resource (e.g., Domains, Users, Extensions)
2. Click the "Create" button
3. Fill in the required fields
4. Click "Create" to save

### Viewing Resources

- **List View**: See all records in a table with sorting, filtering, and searching
- **Detail View**: Click on a record to see full details
- **Edit View**: Edit any field and save changes
- **Delete**: Remove records (with confirmation)

### Filtering

Each resource table includes filters:
- **Domain Filter**: Filter by domain (multi-tenant)
- **Status Filters**: Enable/disabled, active/inactive
- **Custom Filters**: Resource-specific filters

### Bulk Operations

Select multiple records using checkboxes and perform bulk actions:
- Bulk delete
- Bulk status changes (where applicable)

### Searching

Use the search box to find records by:
- Name
- Number
- Email
- Description
- Other searchable fields

## Features

### Form Components

The admin panel uses various Filament form components:

- **TextInput**: Simple text fields
- **Textarea**: Multi-line text
- **Select**: Dropdown selection (with search)
- **Toggle**: Boolean on/off switches
- **DateTimePicker**: Date and time selection
- **Relationship Select**: Link to related resources
- **Multi-Select**: Select multiple options

### Table Features

Tables include:
- **Sortable Columns**: Click column headers to sort
- **Searchable**: Quick search across multiple fields
- **Toggleable Columns**: Show/hide columns
- **Pagination**: Navigate large datasets
- **Filters**: Advanced filtering options
- **Actions**: Quick action buttons per row
- **Bulk Actions**: Operations on multiple records

### Sections and Collapsible Forms

Forms are organized into logical sections:
- **Basic Information**: Essential fields
- **Advanced Settings**: Additional configuration (collapsed by default)
- **Metadata**: System fields (read-only, collapsed)

### Validation

All forms include validation:
- Required fields
- Unique constraints
- Format validation (email, URL, etc.)
- Custom business logic

## Architecture

### Directory Structure

```
app/filament/
├── AdminPanelProvider.php          # Panel configuration
├── filament_bootstrap.php          # Initialization file
├── Resources/                      # Resource definitions
│   ├── DomainResource.php
│   │   └── Pages/
│   │       ├── ListDomains.php
│   │       ├── CreateDomain.php
│   │       ├── ViewDomain.php
│   │       └── EditDomain.php
│   ├── UserResource.php
│   │   └── Pages/
│   ├── ExtensionResource.php
│   │   └── Pages/
│   └── GroupResource.php
│       └── Pages/
├── Pages/                          # Custom pages
│   └── Dashboard.php
└── Widgets/                        # Dashboard widgets
    └── StatsOverview.php
```

### Resource Structure

Each resource consists of:

1. **Resource Class**: Defines the table, form, and navigation
   - `form()`: Form schema for create/edit
   - `table()`: Table columns, filters, actions
   - `getPages()`: Associated pages

2. **Pages**: Different views of the resource
   - `ListRecords`: Table view
   - `CreateRecord`: Create form
   - `ViewRecord`: Detail view
   - `EditRecord`: Edit form

### Navigation Groups

Resources are organized into navigation groups:
- **System**: Domains, Users, Groups, Permissions
- **Communication**: Extensions, Voicemail, Devices
- **Call Center**: Queues, Agents, Tiers
- **Advanced**: Gateways, Dialplans, IVR
- **Contacts & CRM**: Contacts, Organizations
- **Reports**: CDR, Analytics

## Customization

### Adding New Resources

To add a new resource:

1. Create the resource file:
```php
<?php
namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\YourModel;
use Filament\Resources\Resource;

class YourResource extends Resource
{
    protected static ?string $model = YourModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'System';
    
    // Define form() and table() methods
}
```

2. Create page files in `Resources/YourResource/Pages/`

3. Resource will auto-discover via `AdminPanelProvider`

### Customizing Forms

Modify the `form()` method in any resource:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('field_name')
            ->required()
            ->maxLength(255),
        // Add more fields...
    ]);
}
```

### Customizing Tables

Modify the `table()` method:

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('field_name')
                ->searchable()
                ->sortable(),
        ])
        ->filters([
            // Add filters
        ])
        ->actions([
            // Add actions
        ]);
}
```

## Multi-Tenant Support

The admin panel supports FusionPBX's multi-tenant architecture:

1. **Domain Filtering**: Filter resources by domain
2. **Domain Scoping**: Automatic domain scoping in queries
3. **Relationships**: Proper domain relationships
4. **Permissions**: Domain-based access control

### Domain Scoping Example

```php
// In your resource
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    if ($domainUuid = auth()->user()->domain_uuid) {
        $query->where('domain_uuid', $domainUuid);
    }
    
    return $query;
}
```

## Security

### Authentication

Authentication integration with FusionPBX:
- Session-based authentication
- Uses existing FusionPBX user database
- Password verification
- Session management

### Authorization

Permission checks:
- Resource-level permissions
- Action-level permissions
- Group-based permissions
- Domain isolation

### Best Practices

1. **Always validate input**: Use Filament's built-in validation
2. **Use domain scoping**: Filter by domain_uuid
3. **Check permissions**: Use `can()` gates
4. **Sanitize output**: Filament handles this automatically
5. **Use HTTPS**: Always use SSL in production

## Performance

### Optimization Tips

1. **Eager Loading**: Use `with()` for relationships
2. **Pagination**: Limit results per page
3. **Caching**: Cache frequently accessed data
4. **Indexing**: Ensure database indexes exist
5. **Lazy Loading**: Use `lazy()` for large datasets

### Example

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $query->with(['domain', 'contact']);
        })
        ->paginate([10, 25, 50, 100]);
}
```

## Troubleshooting

### Common Issues

**"Composer dependencies not installed"**
- Run: `composer install`

**"Class not found" errors**
- Run: `composer dump-autoload`

**Permission denied on storage**
- Run: `chmod -R 775 storage`

**Blank page on /admin**
- Check PHP error logs
- Verify web server configuration
- Ensure all dependencies are installed

### Debug Mode

Enable debug mode in `filament_bootstrap.php`:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Development

### Creating Custom Widgets

```php
<?php
namespace FusionPBX\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;

class CustomStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', '1,234'),
            Stat::make('Active Extensions', '567'),
        ];
    }
}
```

### Creating Custom Pages

```php
<?php
namespace FusionPBX\Filament\Pages;

use Filament\Pages\Page;

class CustomPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.custom-page';
}
```

## Contributing

To contribute new resources or improvements:

1. Follow Filament's resource structure
2. Use existing resources as templates
3. Include proper documentation
4. Test all CRUD operations
5. Ensure multi-tenant compatibility

## Support

For issues or questions:
- Check this documentation
- Review Filament documentation: https://filamentphp.com/docs
- Check FusionPBX forums
- Review GitHub issues

## License

This integration follows FusionPBX's licensing.

## Credits

- **Filament**: https://filamentphp.com
- **FusionPBX**: https://www.fusionpbx.com
- **Laravel Components**: https://laravel.com
