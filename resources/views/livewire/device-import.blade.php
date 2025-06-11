<div>
    <div class="container-fluid">
        <div class="card card-primary mt-3 card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Import Devices
                </h4>
                <div>
                    @if ($step > 1)
                        <button type="button" class="btn btn-secondary me-2" wire:click="resetImport">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    @endif
                    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="progress-steps d-flex justify-content-between">
                            <div class="step {{ $step >= 1 ? 'active' : '' }}">
                                <div class="step-number">1</div>
                                <div class="step-label">Upload Data</div>
                            </div>
                            <div class="step {{ $step >= 2 ? 'active' : '' }}">
                                <div class="step-number">2</div>
                                <div class="step-label">Map Fields</div>
                            </div>
                            <div class="step {{ $step >= 3 ? 'active' : '' }}">
                                <div class="step-number">3</div>
                                <div class="step-label">Results</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($step == 1)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Import device data from CSV files. You can paste CSV data directly or upload a file. 
                        The system supports devices, device lines, device keys, and device settings.
                    </div>

                    <form wire:submit.prevent="continue">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data" class="form-label">CSV Data</label>
                                    <textarea wire:model="data" class="form-control @error('data') is-invalid @enderror" 
                                        id="data" rows="10" placeholder="Paste CSV data here..."></textarea>
                                    @error('data')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Paste CSV data directly into this field.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="uploadedFile" class="form-label">Or upload CSV file</label>
                                    <input type="file" wire:model="uploadedFile"
                                        class="form-control @error('uploadedFile') is-invalid @enderror"
                                        id="uploadedFile" accept=".csv,.txt">
                                    @error('uploadedFile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Supported formats: CSV, TXT (max 10MB)
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Supported Fields</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Device Fields:</strong>
                                                <ul class="list-unstyled small">
                                                    <li>• device_mac_address</li>
                                                    <li>• device_label</li>
                                                    <li>• device_vendor</li>
                                                    <li>• device_model</li>
                                                    <li>• device_template</li>
                                                    <li>• device_enabled</li>
                                                    <li>• device_description</li>
                                                </ul>
                                            </div>
                                            <div class="col-6">
                                                <strong>Line Fields:</strong>
                                                <ul class="list-unstyled small">
                                                    <li>• line_number</li>
                                                    <li>• user_id</li>
                                                    <li>• auth_id</li>
                                                    <li>• password</li>
                                                    <li>• display_name</li>
                                                    <li>• server_address</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="fromRow" class="form-label">Start from row</label>
                                    <select wire:model="fromRow"
                                        class="form-select @error('fromRow') is-invalid @enderror" id="fromRow">
                                        @for ($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('fromRow')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Row to start importing from.</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="delimiter" class="form-label">Delimiter</label>
                                    <select wire:model="delimiter"
                                        class="form-select @error('delimiter') is-invalid @enderror" id="delimiter">
                                        <option value="comma">Comma (,)</option>
                                        <option value="pipe">Pipe (|)</option>
                                        <option value="semicolon">Semicolon (;)</option>
                                        <option value="tab">Tab</option>
                                    </select>
                                    @error('delimiter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="enclosure" class="form-label">Enclosure</label>
                                    <select wire:model="enclosure"
                                        class="form-select @error('enclosure') is-invalid @enderror" id="enclosure">
                                        <option value="quote">Quotes (")</option>
                                        <option value="none">None</option>
                                    </select>
                                    @error('enclosure')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-arrow-right"></i> Continue
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                @elseif($step == 2)
                    <form wire:submit.prevent="continue">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Map each column from your CSV file to the corresponding device fields. Fields are organized by table.
                        </div>

                        @if (!empty($headers))
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="25%">CSV Column</th>
                                            <th width="40%">Map to Field</th>
                                            <th width="35%">Preview Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($headers as $index => $header)
                                            <tr>
                                                <td>
                                                    <strong class="text-primary">{{ $header }}</strong>
                                                </td>
                                                <td>
                                                    <select wire:model="fieldMappings.{{ $index }}"
                                                        class="form-select form-select-sm">
                                                        <option value="">-- Select field --</option>
                                                        @foreach ($availableFields as $table => $fields)
                                                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $table)) }}">
                                                                @foreach ($fields as $field)
                                                                    <option value="{{ $table }}.{{ $field }}">
                                                                        {{ $field }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        @if (isset($csvData[0][$index]))
                                                            <code>{{ Str::limit($csvData[0][$index], 40) }}</code>
                                                        @endif
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>MAC addresses will be normalized automatically (lowercase, special chars removed)</li>
                                    <li>Existing devices with the same MAC address will be updated</li>
                                    <li>Device lines, keys, and settings will be created as separate records</li>
                                    <li>Username fields will be matched against existing users</li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-upload"></i> Import Devices
                                </button>
                            </div>
                        @endif
                    </form>

                @elseif($step == 3)
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Import Completed Successfully</h5>
                        <p class="mb-0">
                            <strong>{{ $importResults['success'] }}</strong> devices were successfully imported or updated.
                        </p>
                    </div>

                    @if (!empty($importResults['errors']))
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Issues Found During Import:</h6>
                            <div class="mt-2">
                                @foreach ($importResults['errors'] as $error)
                                    <div class="alert alert-sm alert-danger mb-1">
                                        <small>{{ $error }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                                    <h5>View Imported Devices</h5>
                                    <p class="text-muted">Check the devices that were imported</p>
                                    <a href="{{ route('devices.index') }}" class="btn btn-primary">
                                        <i class="fas fa-list"></i> View All Devices
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-plus fa-3x text-success mb-3"></i>
                                    <h5>Import More Devices</h5>
                                    <p class="text-muted">Start a new import process</p>
                                    <button type="button" class="btn btn-success" wire:click="resetImport">
                                        <i class="fas fa-plus"></i> New Import
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .progress-steps {
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 25px;
            right: 25px;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }

        .step.active .step-number {
            background: #0d6efd;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .step.active .step-label {
            color: #0d6efd;
            font-weight: 600;
        }

        .alert-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</div>