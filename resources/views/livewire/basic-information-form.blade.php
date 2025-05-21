<div>
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
                        <option value="">Select</option>
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
</div>
