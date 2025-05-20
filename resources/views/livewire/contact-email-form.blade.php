<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6>Email Addresses</h6>
            <button type="button" class="btn btn-sm btn-primary" wire:click="addEmail"> <i class="fa fa-plus" aria-hidden="true"></i></button>
        </div>
        <div class="card-body">
            @foreach ($emails as $index => $email)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="email_address_{{ $index }}" class="form-label">Address</label>
                                <input type="email" wire:model="emails.{{ $index }}.email_address"
                                    class="form-control" placeholder="Email">
                            </div>
                            <div class="col-md-4">
                                <label for="emails.{{ $index }}.email_label" class="form-label">Label</label>
                                <select wire:model="emails.{{ $index }}.email_label" class="form-select">
                                    <option value="">Select</option>
                                    <option value="work">Work</option>
                                    <option value="home">Home</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="emails.{{ $index }}.email_primary"
                                        id="email_primary_{{ $index }}">
                                    <label class="form-check-label" for="email_primary_{{ $index }}">
                                        Primary </label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="email_description_{{ $index }}"
                                    class="form-label">Description</label>
                                <textarea id="email_description_{{ $index }}" wire:model="emails.{{ $index }}.email_description"
                                    class="form-control @error('email_description') is-invalid @enderror" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-danger"
                                wire:click="removeEmail({{ $index }})">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
