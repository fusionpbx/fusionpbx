<div>
    <div class="card mb-4">
        <div class="card-header">
            <h5>{{ isset($contact->contact_uuid) ? 'Edit Contact' : 'New Contact' }}</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Contact Type -->
                            <div class="col-md-6 mb-3">
                                <label for="contactType" class="form-label">Type</label>
                                <select id="contactType" wire:model="contactType"
                                    class="form-select @error('contactType') is-invalid @enderror">
                                    <option value="">Select a type...</option>
                                    @foreach ($contactTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                @error('contactType')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Organization -->
                            <div class="col-md-6 mb-3">
                                <label for="contactOrganization" class="form-label">Organization</label>
                                <input type="text" id="contactOrganization" wire:model="contactOrganization"
                                    class="form-control @error('contactOrganization') is-invalid @enderror">
                                @error('contactOrganization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Prefix -->
                            <div class="col-md-2 mb-3">
                                <label for="contactNamePrefix" class="form-label">Prefix</label>
                                <input type="text" id="contactNamePrefix" wire:model="contactNamePrefix"
                                    class="form-control @error('contactNamePrefix') is-invalid @enderror">
                                @error('contactNamePrefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- First Name -->
                            <div class="col-md-3 mb-3">
                                <label for="contactNameGiven" class="form-label">First Name</label>
                                <input type="text" id="contactNameGiven" wire:model="contactNameGiven"
                                    class="form-control @error('contactNameGiven') is-invalid @enderror">
                                @error('contactNameGiven')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Middle Name -->
                            <div class="col-md-2 mb-3">
                                <label for="contactNameMiddle" class="form-label">Middle</label>
                                <input type="text" id="contactNameMiddle" wire:model="contactNameMiddle"
                                    class="form-control @error('contactNameMiddle') is-invalid @enderror">
                                @error('contactNameMiddle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-3 mb-3">
                                <label for="contactNameFamily" class="form-label">Last Name</label>
                                <input type="text" id="contactNameFamily" wire:model="contactNameFamily"
                                    class="form-control @error('contactNameFamily') is-invalid @enderror">
                                @error('contactNameFamily')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Suffix -->
                            <div class="col-md-2 mb-3">
                                <label for="contactNameSuffix" class="form-label">Suffix</label>
                                <input type="text" id="contactNameSuffix" wire:model="contactNameSuffix"
                                    class="form-control @error('contactNameSuffix') is-invalid @enderror">
                                @error('contactNameSuffix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Nickname -->
                            <div class="col-md-4 mb-3">
                                <label for="contactNickname" class="form-label">Nickname</label>
                                <input type="text" id="contactNickname" wire:model="contactNickname"
                                    class="form-control @error('contactNickname') is-invalid @enderror">
                                @error('contactNickname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Title -->
                            <div class="col-md-4 mb-3">
                                <label for="contactTitle" class="form-label">Title</label>
                                <input type="text" id="contactTitle" wire:model="contactTitle"
                                    class="form-control @error('contactTitle') is-invalid @enderror">
                                @error('contactTitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Role -->
                            <div class="col-md-4 mb-3">
                                <label for="contactRole" class="form-label">Role</label>
                                <input type="text" id="contactRole" wire:model="contactRole"
                                    class="form-control @error('contactRole') is-invalid @enderror">
                                @error('contactRole')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Category -->
                            <div class="col-md-6 mb-3">
                                <label for="contactCategory" class="form-label">Category</label>
                                <input type="text" id="contactCategory" wire:model="contactCategory"
                                    class="form-control @error('contactCategory') is-invalid @enderror">
                                @error('contactCategory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- URL -->
                            <div class="col-md-6 mb-3">
                                <label for="contactUrl" class="form-label">URL</label>
                                <input type="url" id="contactUrl" wire:model="contactUrl"
                                    class="form-control @error('contactUrl') is-invalid @enderror">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Time Zone -->
                            <div class="col-md-6 mb-3">
                                <label for="contactTimeZone" class="form-label">Time Zone</label>
                                <select id="contactTimeZone" wire:model="contactTimeZone"
                                    class="form-select @error('contactTimeZone') is-invalid @enderror">
                                    <option value="">Select a time zone...</option>
                                    @foreach ($timeZones as $tz)
                                        <option value="{{ $tz }}">{{ $tz }}</option>
                                    @endforeach
                                </select>
                                @error('contactTimeZone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Note -->
                            <div class="col-md-12 mb-3">
                                <label for="contactNote" class="form-label">Note</label>
                                <textarea id="contactNote" wire:model="contactNote" class="form-control @error('contactNote') is-invalid @enderror"
                                    rows="3"></textarea>
                                @error('contactNote')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emails -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>Email Addresses</h6>
                        <button type="button" class="btn btn-sm btn-primary" wire:click="addEmail">Add
                            Email</button>
                    </div>
                    <div class="card-body">
                        @foreach ($emails as $index => $email)
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <input type="email" wire:model="emails.{{ $index }}.email_address"
                                        class="form-control" placeholder="Email">
                                </div>
                                <div class="col-md-4">
                                    <select wire:model="emails.{{ $index }}.email_type" class="form-select">
                                        <option value="work">Work</option>
                                        <option value="home">Home</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-danger"
                                        wire:click="removeEmail({{ $index }})">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Phones -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>Phones</h6>
                        <button type="button" class="btn btn-sm btn-primary" wire:click="addPhone">Add
                            Phone</button>
                    </div>
                    <div class="card-body">
                        @foreach ($phones as $index => $phone)
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <input type="text" wire:model="phones.{{ $index }}.phone_number"
                                        class="form-control" placeholder="Phone number">
                                </div>
                                <div class="col-md-4">
                                    <select wire:model="phones.{{ $index }}.phone_type" class="form-select">
                                        <option value="work">Work</option>
                                        <option value="home">Home</option>
                                        <option value="mobile">Mobile</option>
                                        <option value="fax">Fax</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-danger"
                                        wire:click="removePhone({{ $index }})">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Addresses -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>Addresses</h6>
                        <button type="button" class="btn btn-sm btn-primary" wire:click="addAddress">Add
                            Address</button>
                    </div>
                    <div class="card-body">
                        @foreach ($addresses as $index => $address)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-md-10">
                                            <input type="text"
                                                wire:model="addresses.{{ $index }}.address_street"
                                                class="form-control" placeholder="Street">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="removeAddress({{ $index }})">Remove</button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <input type="text"
                                                wire:model="addresses.{{ $index }}.address_city"
                                                class="form-control" placeholder="City">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text"
                                                wire:model="addresses.{{ $index }}.address_region"
                                                class="form-control" placeholder="Region/State">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text"
                                                wire:model="addresses.{{ $index }}.address_postal_code"
                                                class="form-control" placeholder="Postal Code">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text"
                                                wire:model="addresses.{{ $index }}.address_country"
                                                class="form-control" placeholder="Country">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <select wire:model="addresses.{{ $index }}.address_type"
                                                class="form-select">
                                                <option value="work">Work</option>
                                                <option value="home">Home</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>


                <!-- Urls -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6>Urls</h6>
                        <button type="button" class="btn btn-sm btn-primary" wire:click="addUrl">Add
                            Url</button>
                    </div>
                    <div class="card-body">
                        @foreach ($urls as $index => $url)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row mb-2">
                                       <div class="col-md-6 mb-2">
                                            <select wire:model="urls.{{ $index }}.url_label"
                                                class="form-select">
                                                <option value="work">Work</option>
                                                <option value="personal">Personal</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="removeAddress({{ $index }})">Remove</button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="urls.{{ $index }}.url_primary"
                                                id="url_primary_{{ $index }}">
                                            <label class="form-check-label" for="url_primary_{{ $index }}">
                                                Primary </label>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label for="url_address{{ $index }}" class="form-label"> Adress</label>
                                            <input type="text"
                                                wire:model="urls_address.{{ $index }}"
                                                class="form-control" placeholder="http://...">
                                            
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="url_type{{ $index }}" class="form-label">Type</label>
                                            <select wire:model="urls.{{ $index }}.url_label"
                                                class="form-select">
                                                <option value="">Select</option>
                                                <option value="work">Work</option>
                                                <option value="personal">Personal</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Groups -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6>Groups</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($availableGroups as $uuid => $name)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $uuid }}"
                                            wire:model="selectedGroups" id="group_{{ $uuid }}">
                                        <label class="form-check-label" for="group_{{ $uuid }}">
                                            {{ $name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('contacts.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Contact</button>
                </div>
            </form>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif
</div>
