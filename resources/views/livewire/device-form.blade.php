<div>
    <div class="container-fluid ">
        <div class="card card-primary mt-3 card-outline">
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-router me-2"></i>
                            {{ $isEditing ? 'Edit Device' : 'New Device' }}
                        </h4>
                        @if ($isEditing)
                            <div class="btn-group">
                                <button type="button" wire:click="copyDevice" class="btn btn-outline-light btn-sm">
                                    <i class="bi bi-copy me-1"></i>Copy
                                </button>
                                <button type="button" wire:click="delete"
                                    wire:confirm="Are you sure you want to delete this device?"
                                    class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <!-- General Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-info-circle me-2"></i>General Information
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="device_mac_address" class="form-label">MAC Address <span
                                        class="text-danger">*</span></label>
                                <input type="text" wire:model.lazy="device_mac_address"
                                    class="form-control @error('device_mac_address') is-invalid @enderror"
                                    id="device_mac_address" placeholder="AA:BB:CC:DD:EE:FF">
                                @error('device_mac_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if ($duplicateMacDomain)
                                    <div class="form-text text-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        This MAC already exists in domain: {{ $duplicateMacDomain }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="device_label" class="form-label">Label <span
                                        class="text-danger">*</span></label>
                                <input type="text" wire:model="device_label"
                                    class="form-control @error('device_label') is-invalid @enderror" id="device_label"
                                    placeholder="Device name">
                                @error('device_label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @can('device_model')
                            <div class="col-md-6 mb-3">
                                <label for="device_model" class="form-label">Model</label>
                                <input type="text" wire:model="device_model"
                                    class="form-control @error('device_model') is-invalid @enderror" id="device_model"
                                    placeholder="Device model">
                                @error('device_model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endcan

                            @can('device_location')
                            <div class="col-md-6 mb-3">
                                <label for="device_location" class="form-label">Location</label>
                                <input type="text" wire:model="device_location"
                                    class="form-control @error('device_location') is-invalid @enderror"
                                    id="device_location" placeholder="Physical location">
                                @error('device_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endcan

                            @can('device_firmware')
                            <div class="col-md-6 mb-3">
                                <label for="device_firmware_version" class="form-label">Firmware Version</label>
                                <input type="text" wire:model="device_firmware_version"
                                    class="form-control @error('device_firmware_version') is-invalid @enderror"
                                    id="device_firmware_version" placeholder="Firmware version">
                                @error('device_firmware_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endcan

                            <div class="col-md-6 mb-3">
                                <label for="device_template" class="form-label">Device Template</label>
                                <div class="template-select-container">
                                    <select wire:model="device_template"
                                        class="form-select @error('device_template') is-invalid @enderror"
                                        id="device_template">
                                        <option value="">Select a template...</option>
                                        @foreach ($deviceTemplates as $vendor => $vendorData)
                                            <optgroup label="{{ $vendorData['name'] }}">
                                                @foreach ($vendorData['templates'] as $template)
                                                    <option value="{{ $template['value'] }}">{{ $template['label'] }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('device_template')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if (!empty($templateInfo) && isset($templateInfo['image']) && $templateInfo['image']['exists'])
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Preview</label>
                                    <div class="device-image-container">
                                        <img src="data:image/jpeg;base64,{{ $templateInfo['image']['base64'] }}"
                                            alt="{{ $templateInfo['template'] }}" class="device-image img-fluid"
                                            title="{{ $templateInfo['vendor'] }}/{{ $templateInfo['template'] }}">
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6 mb-3">
                                <label for="device_user_uuid" class="form-label">Assigned User</label>
                                <select wire:model="device_user_uuid" class="form-select" id="device_user_uuid">
                                    <option value="">No user assigned...</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user['user_uuid'] }}">{{ $user['username'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @can('device_alternate')
                            @if( !empty($device_uuid_alternate))
                            <div class="col-md-6 mb-3">
                                <label for="device_uuid_alternate" class="form-label">Alternate Device</label>
                                <input type="text" wire:model.lazy="device_uuid_alternate" class="form-control"
                                    id="device_uuid_alternate" placeholder="Alternate device UUID">
                                @if (count($alternateDevices) > 0)
                                    <div class="form-text text-info">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Devices found: {{ count($alternateDevices) }}
                                    </div>
                                @endif
                            </div>
                            @endif
                            @endcan

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" wire:model="device_enabled" value="true" {{ $device_enabled === 'true' ? 'checked' : '' }}
                                        rol="switch" class="form-check-input" id="device_enabled">
                                    <label class="form-check-label" for="device_enabled">Device Enabled</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="domain_uuid" class="form-label">{{ __('Domain') }}</label>
                                    <select class="form-select @error('domain_uuid') is-invalid @enderror"
                                        id="domain_uuid" wire:model="domain_uuid">
                                        @foreach ($availableDomains as $availableDomain)
                                            <option value="{{ $availableDomain['domain_uuid'] }}">
                                                {{ $availableDomain['domain_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @error('domain_uuid')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="device_vendor" class="form-label">Device Vendor</label>
                                <input type="text" wire:model="device_vendor" class="form-control"
                                    id="device_vendor" placeholder="Device vendor">
                                @error('device_vendor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="device_description" class="form-label">Description</label>
                                <textarea wire:model="device_description" class="form-control @error('device_description') is-invalid @enderror"
                                    id="device_description" rows="3" placeholder="Device description"></textarea>
                                @error('device_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-shield-lock me-2"></i>Authentication
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="device_username" class="form-label">Username</label>
                                <input type="text" wire:model="device_username" class="form-control"
                                    id="device_username" placeholder="Username">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="device_password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" wire:model="device_password" class="form-control"
                                        id="device_password" placeholder="Password">
                                    <button type="button" wire:click="generatePassword"
                                        class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Generate
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Device Lines -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="border-bottom pb-2 mb-0">
                                        <i class="bi bi-telephone me-2"></i>Device Lines
                                    </h5>
                                    <button type="button" wire:click="addDeviceLine" class="btn btn-success btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i>Add Line
                                    </button>
                                </div>

                                @if (count($deviceLines) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Line</th>
                                                    @can('device_line_server_address')
                                                        <th>Server Address</th>
                                                    @endcan
                                                    @can('device_line_server_address_primary')
                                                        <th>Server Address Primary</th>
                                                    @endcan
                                                    @can('device_line_server_address_secondary')
                                                        <th>Server Address Secondary</th>
                                                    @endcan
                                                    @can('device_line_outbound_proxy_primary')
                                                        <th>Outbound Proxy Primary</th>
                                                    @endcan
                                                    @can('device_line_outbound_proxy_secondary')
                                                        <th>Outbound Proxy Secondary</th>
                                                    @endcan
                                                    @can('device_line_label')
                                                        <th>Label</th>
                                                    @endcan
                                                    @can('device_line_display_name')
                                                        <th>Display Name</th>
                                                    @endcan
                                                    <th>User ID</th>

                                                    @can('device_line_auth_id')
                                                        <th>Auth ID</th>
                                                    @endcan
                                                    @can('device_line_password')
                                                        <th>Password</th>
                                                    @endcan
                                                    @can('device_line_port')
                                                        <th>Port</th>
                                                    @endcan
                                                    @can('device_line_transport')
                                                        <th>Transport</th>
                                                    @endcan
                                                    @can('device_line_register_expires')
                                                        <th>Register Expires</th>
                                                    @endcan
                                                    @can('device_line_shared')
                                                        <th>Shared Line</th>
                                                    @endcan
                                                    <th>Enabled</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($deviceLines as $index => $line)
                                                    <tr>
                                                        <td>
                                                            <input type="number" max="99"
                                                                wire:model="deviceLines.{{ $index }}.line_number"
                                                                class="form-control form-control-sm @error('deviceLines.' . $index . '.line_number') is-invalid @enderror">
                                                        </td>
                                                        @can('device_line_server_address')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.server_address"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Server Address">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_server_address_primary')
                                                            <td>
                                                                @foreach ($deviceLinesServerPrimary as $item)
                                                                    <select name=""
                                                                        wire:model="deviceLines.{{ $index }}.server_address_primary"
                                                                        id="deviceLines.{{ $index }}.server_address_primary">
                                                                        <option value="{{ $item->id }}">
                                                                            {{ $item->name }}</option>
                                                                    </select>
                                                                @endforeach
                                                            </td>
                                                        @endcan
                                                        @can('device_line_server_address_secondary')
                                                            <td>
                                                                @foreach ($deviceLinesServerSecondary as $item)
                                                                    <select name=""
                                                                        wire:model="deviceLines.{{ $index }}.server_address_secondary"
                                                                        id="deviceLines.{{ $index }}.server_address_secondary">
                                                                        <option value="{{ $item->name }}">
                                                                            {{ $item->name }}</option>
                                                                    </select>
                                                                @endforeach
                                                            </td>
                                                        @endcan
                                                        @can('device_line_outbound_proxy_primary')
                                                            <td>
                                                                @foreach ($outboundProxyPrimary as $item)
                                                                    <select name=""
                                                                        wire:model="deviceLines.{{ $index }}.outbound_proxy_primary"
                                                                        id="">
                                                                        <option value="{{ $item->id }}">
                                                                            {{ $item->name }}</option>
                                                                    </select>
                                                                @endforeach
                                                            </td>
                                                        @endcan
                                                        @can('device_line_outbound_proxy_secondary')
                                                            <td>
                                                                @foreach ($outboundProxySecondary as $item)
                                                                    <select name=""
                                                                        wire:model="deviceLines.{{ $index }}.outbound_proxy_secondary"
                                                                        id="">
                                                                        <option value="{{ $item->id }}">
                                                                            {{ $item->name }}</option>
                                                                    </select>
                                                                @endforeach
                                                            </td>
                                                        @endcan
                                                        @can('device_line_label')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.label"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Label">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_display_name')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.display_name"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Description">
                                                            </td>
                                                        @endcan
                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceLines.{{ $index }}.user_id"
                                                                class="form-control form-control-sm"
                                                                placeholder="User">
                                                        </td>
                                                        @can('device_line_auth_id')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.auth_id"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Auth ID">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_password')
                                                            <td>
                                                                <input type="password"
                                                                    wire:model="deviceLines.{{ $index }}.password"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Password">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_port')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.sip_port"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="5060">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_transport')
                                                            <td>
                                                                <select name=""
                                                                    wire:model="deviceLines.{{ $index }}.sip_transport"
                                                                    id="deviceLines.{{ $index }}.sip_transport">
                                                                    <option value="udp">UDP</option>
                                                                    <option value="tcp">TCP</option>
                                                                    <option value="tls">TLS</option>
                                                                    <option value="dns srv">DNS SRV</option>
                                                                </select>
                                                            </td>
                                                        @endcan
                                                        @can('device_line_register_expires')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.register_expires"
                                                                    class="form-control form-control-sm" placeholder="60">
                                                            </td>
                                                        @endcan
                                                        @can('device_line_shared')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceLines.{{ $index }}.shared_line"
                                                                    class="form-control form-control-sm">
                                                            </td>
                                                        @endcan
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox"
                                                                    wire:model="deviceLines.{{ $index }}.enabled"
                                                                    value="true" class="form-check-input">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <button type="button"
                                                                wire:click="removeDeviceLine({{ $index }})"
                                                                class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No lines configured. Click "Add Line" to start.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Device Keys -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="border-bottom pb-2 mb-0">
                                        <i class="bi bi-keyboard me-2"></i>Device Keys
                                    </h5>
                                    <button type="button" wire:click="addDeviceKey" class="btn btn-success btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i>Add Key
                                    </button>
                                </div>

                                @if (count($deviceKeys) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Category</th>
                                                    @can('device_key_id')
                                                        <th>ID</th>
                                                    @endcan
                                                    <th>Type</th>
                                                    @if ($isEditing && $showKeySubtype)
                                                        <th>Subcategory</th>
                                                    @endif
                                                    @can('device_key_line')
                                                        <th>Line</th>
                                                    @endcan
                                                    <th>Value</th>
                                                    @can('device_key_extension')
                                                        <th>Extension</th>
                                                    @endcan
                                                    <th>Label</th>
                                                    @can('device_key_icon')
                                                        <th>Icon</th>
                                                    @endcan
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($deviceKeys as $index => $key)
                                                    <tr>
                                                        <td>
                                                            <select
                                                                wire:model="deviceKeys.{{ $index }}.device_key_category"
                                                                class="form-select form-select-sm @error('deviceKeys.' . $index . '.device_key_category') is-invalid @enderror">
                                                                <option value="">Select...</option>
                                                                <option value="line"
                                                                    {{ ($key['device_key_category'] ?? '') == 'line' ? 'selected' : '' }}>
                                                                    Line
                                                                </option>
                                                                @if (empty($device_vendor) || strtolower($device_vendor) !== 'polycom')
                                                                    <option value="memory"
                                                                        {{ ($key['device_key_category'] ?? '') == 'memory' ? 'selected' : '' }}>
                                                                        Memory
                                                                    </option>
                                                                @endif
                                                                <option value="programmable"
                                                                    {{ ($key['device_key_category'] ?? '') == 'programmable' ? 'selected' : '' }}>
                                                                    Programmable
                                                                </option>
                                                                @if (empty($device_vendor) || strtolower($device_vendor) !== 'polycom')
                                                                    @if (empty($device_vendor))
                                                                        <option value="expansion"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion' ? 'selected' : '' }}>
                                                                            Expansion 1
                                                                        </option>
                                                                        <option value="expansion-2"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion-2' ? 'selected' : '' }}>
                                                                            Expansion 2
                                                                        </option>
                                                                        <option value="expansion-3"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion-3' ? 'selected' : '' }}>
                                                                            Expansion 3
                                                                        </option>
                                                                        <option value="expansion-4"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion-4' ? 'selected' : '' }}>
                                                                            Expansion 4
                                                                        </option>
                                                                        <option value="expansion-5"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion-5' ? 'selected' : '' }}>
                                                                            Expansion 5
                                                                        </option>
                                                                        <option value="expansion-6"
                                                                            {{ ($key['device_key_category'] ?? '') == 'expansion-6' ? 'selected' : '' }}>
                                                                            Expansion 6
                                                                        </option>
                                                                    @else
                                                                        @if (in_array(strtolower($device_vendor), ['cisco', 'yealink']))
                                                                            <option value="expansion-1"
                                                                                {{ in_array($key['device_key_category'] ?? '', ['expansion-1', 'expansion']) ? 'selected' : '' }}>
                                                                                Expansion 1
                                                                            </option>
                                                                            <option value="expansion-2"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion-2' ? 'selected' : '' }}>
                                                                                Expansion 2
                                                                            </option>
                                                                            <option value="expansion-3"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion-3' ? 'selected' : '' }}>
                                                                                Expansion 3
                                                                            </option>
                                                                            <option value="expansion-4"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion-4' ? 'selected' : '' }}>
                                                                                Expansion 4
                                                                            </option>
                                                                            <option value="expansion-5"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion-5' ? 'selected' : '' }}>
                                                                                Expansion 5
                                                                            </option>
                                                                            <option value="expansion-6"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion-6' ? 'selected' : '' }}>
                                                                                Expansion 6
                                                                            </option>
                                                                        @else
                                                                            <option value="expansion"
                                                                                {{ ($key['device_key_category'] ?? '') == 'expansion' ? 'selected' : '' }}>
                                                                                Expansion
                                                                            </option>
                                                                        @endif
                                                                    @endif
                                                                @endif
                                                            </select>
                                                            @error('deviceKeys.' . $index . '.device_key_category')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        @can('device_key_id')
                                                            <td>
                                                                <input type="number" max="255"
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_id"
                                                                    class="form-control form-control-sm" placeholder="ID">
                                                            </td>
                                                        @endcan


                                                        <td>
                                                            <div class="template-select-container">
                                                                <select
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_type"
                                                                    class="form-select @error('deviceKeys.{{ $index }}.device_key_type') is-invalid @enderror"
                                                                    id="deviceKeys.{{ $index }}.device_key_type">
                                                                    <option value="">Select a typer...
                                                                    </option>

                                                                    @foreach ($vendorFunctions as $vendor => $vendorData)
                                                                        <optgroup
                                                                            label="{{ $vendorData['vendor_name'] }}">
                                                                            <option value="{{ $template['value'] }}">
                                                                                {{ $template['value'] }}
                                                                            </option>

                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                                @error('deviceKeys.{{ $index }}.device_key_type')
                                                                    <div class="invalid-feedback">{{ $message }}
                                                                    </div>
                                                                @enderror
                                                            </div>

                                                        </td>
                                                        @if ($isEditing && $showKeySubtype)
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_subtype"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Subtype">
                                                            </td>
                                                        @endif
                                                        @can('device_key_line')
                                                            <td>
                                                                <input type="number"
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_line"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Line">
                                                            </td>
                                                        @endcan

                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceKeys.{{ $index }}.device_key_value"
                                                                class="form-control form-control-sm"
                                                                placeholder="Value">
                                                        </td>
                                                        @can('device_key_extension')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_extension"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Extension">
                                                            </td>
                                                        @endcan
                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceKeys.{{ $index }}.device_key_label"
                                                                class="form-control form-control-sm"
                                                                placeholder="Label">
                                                        </td>

                                                        @can('device_key_icon')
                                                            <td>
                                                                <input type="text"
                                                                    wire:model="deviceKeys.{{ $index }}.device_key_icon"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Icon">
                                                            </td>
                                                        @endcan
                                                        <td>
                                                            <button type="button"
                                                                wire:click="removeDeviceKey({{ $index }})"
                                                                class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No keys configured. Click "Add Key" to start.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Device Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="border-bottom pb-2 mb-0">
                                        <i class="bi bi-gear me-2"></i>Device Settings
                                    </h5>
                                    <button type="button" wire:click="addDeviceSetting"
                                        class="btn btn-success btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i>Add Setting
                                    </button>
                                </div>

                                @if (count($deviceSettings) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Value</th>
                                                    <th>Enabled</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($deviceSettings as $index => $setting)
                                                    <tr>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceSettings.{{ $index }}.device_setting_name"
                                                                class="form-control form-control-sm"
                                                                placeholder="Name">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceSettings.{{ $index }}.device_setting_value"
                                                                class="form-control form-control-sm"
                                                                placeholder="Value">
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox"
                                                                    wire:model="deviceSettings.{{ $index }}.device_setting_enabled"
                                                                    value="true" class="form-check-input">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="deviceSettings.{{ $index }}.device_setting_description"
                                                                class="form-control form-control-sm"
                                                                placeholder="Description">
                                                        </td>
                                                        <td>
                                                            <button type="button"
                                                                wire:click="removeDeviceSetting({{ $index }})"
                                                                class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No custom settings. Click "Add Setting" to add specific configurations.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('devices.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>
                                        {{ $isEditing ? 'Update Device' : 'Create Device' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
