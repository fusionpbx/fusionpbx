<div>
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
                        <label for="phone_number_{{ $index }}" class="form-label">Phone Number</label>
                        <input type="text" wire:model="phones.{{ $index }}.phone_number" class="form-control"
                            placeholder="Phone number">
                    </div>
                    <div class="col-md-4">
                        <label for="phone_label">Label</label>
                        <select wire:model="phones.{{ $index }}.phone_label" class="form-select">
                            <option value="work">Work</option>
                            <option value="home">Home</option>
                            <option value="mobile">Mobile</option>
                            <option value="billing">Billing</option>
                            <option value="fax">Fax</option>
                            <option value="voicemail">Voicemail</option>
                            <option value="text">Text</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="phone_speed_dial" class="form-label">Speed Dial</label>
                        <input type="text" wire:model="phones.{{ $index }}.phone_speed_dial"
                            class="form-control" placeholder="Speed Dial">
                    </div>
                    <div class="col-md-6">
                        <label for="phone_country_code" class="form-label">Country Code</label>
                        <input type="text" wire:model="phones.{{ $index }}.phone_country_code"
                            class="form-control" placeholder="Country Code">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="phone_extension_{{ $index }}" class="form-label">Extension</label>
                        <input type="text" wire:model="phones.{{ $index }}.phone_extension"
                            class="form-control" placeholder="Extension">
                    </div>

                    <div class="col-md-6 ">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model="phones.{{ $index }}.phone_primary"
                                id="phone_primary_{{ $index }}">
                            <label class="form-check-label" for="phone_primary_{{ $index }}">
                                Primary</label>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <label for="phone_type">Type</label>
                    <div class="col-md-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model="phones.{{ $index }}.phone_type_voice"
                                id="phone_primary_{{ $index }}">
                            <label class="form-check-label" for="phone_type_voice_{{ $index }}">
                                Voice </label>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model="phones.{{ $index }}.phone_type_video"
                                id="phone_primary_{{ $index }}">
                            <label class="form-check-label" for="phone_type_video_{{ $index }}">
                                Video </label>
                        </div>
                    </div>


                    <div class="col-md-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model="phones.{{ $index }}.phone_type_text" id="{{ $index }}">
                            <label class="form-check-label" for="phone_type_text_{{ $index }}">
                                Text</label>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox"
                                wire:model="phones.{{ $index }}.phone_type_fax" id="{{ $index }}">
                            <label class="form-check-label" for="phone_type_fax_{{ $index }}">
                                Fax</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label for="phone_description_{{ $index }}" class="form-label">Description</label>
                    <textarea id="phone_description_{{ $index }}" wire:model="phones.{{ $index }}.phone_description"
                        class="form-control @error('phone_description') is-invalid @enderror" rows="3"></textarea>
                    @error('phone_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-danger"
                        wire:click="removePhone({{ $index }})">Remove</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
