<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6>Addresses</h6>
            <button type="button" class="btn btn-sm btn-primary" wire:click="addAddress"> <i class="fa fa-plus" aria-hidden="true"></i></button>
        </div>
        <div class="card-body">
            @foreach ($addresses as $index => $address)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-2">
                            <label for="address_street">Adress</label>
                            <div class="col-md-10 mb-2">
                                <input type="text" wire:model="addresses.{{ $index }}.address_street"
                                    class="form-control" placeholder="Address 1">
                                <input type="text" wire:model="addresses.{{ $index }}.address_extended"
                                    class="form-control" placeholder="Address 2">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="address_locality">City</label>
                                <input type="text" wire:model="addresses.{{ $index }}.address_locality"
                                    class="form-control" placeholder="City">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_region">Region/State</label>
                                <input type="text" wire:model="addresses.{{ $index }}.address_region"
                                    class="form-control" placeholder="Region/State">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_postal_code">Postal Code</label>
                                <input type="text" wire:model="addresses.{{ $index }}.address_postal_code"
                                    class="form-control" placeholder="Postal Code">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_country">Country</label>
                                <input type="text" wire:model="addresses.{{ $index }}.address_country"
                                    class="form-control" placeholder="Country">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_type">Type</label>
                                <select wire:model="addresses.{{ $index }}.address_type" class="form-select">
                                    <option value="">Select</option>
                                    <option value="work">Work</option>
                                    <option value="home">Home</option>
                                    <option value="domestic">Domestic</option>
                                    <option value="international">international</option>
                                    <option value="postal">Postal</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="preferred">Preferred</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_label">Label</label>
                                <select wire:model="addresses.{{ $index }}.address_label" class="form-select">
                                    <option value="">Select</option>
                                    <option value="work">Work</option>
                                    <option value="home">Home</option>
                                    <option value="mobile">Mobile</option>
                                    <option value="main">Main</option>
                                    <option value="billing">Billing</option>
                                    <option value="fax">Fax</option>
                                    <option value="pager">Pager</option>
                                    <option value="voicemail">Voicemail</option>
                                    <option value="text">Text</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="address_primary">Primary</label>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="addresses.{{ $index }}.address_primary"
                                        id="address_primary_{{ $index }}">
                                    <label class="form-check-label" for="address_primary_{{ $index }}">
                                        Primary</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="address_description{{ $index }}"
                                    class="form-label">Description</label>
                                <textarea id="address_description{{ $index }}" wire:model="phones.{{ $index }}.address_description"
                                    class="form-control @error('phone_description') is-invalid @enderror" rows="3"></textarea>
                                @error('address_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-danger"
                                wire:click="removeAddress({{ $index }})">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
