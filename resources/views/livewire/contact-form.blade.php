<div>
    <form wire:submit.prevent="">
        <div class="card mb-4">
            <div class="card-header">
                <h5>{{ isset($contact->contact_uuid) ? 'Edit Contact' : 'New Contact' }}</h5>
            </div>
            <div class="card-body">
                <!-- Basic Information -->
                @livewire('basic-information-form', ['contactUuid' => $contactUuid])
                <!-- Emails -->
                @livewire('contact-email-form', ['contactUuid' => $contactUuid])
                <!-- Phones -->
                @livewire('contact-phone-form', ['contactUuid' => $contactUuid])
                <!-- Addresses -->
                @livewire('contact-address-form', ['contactUuid' => $contactUuid])
                <!-- Urls -->
                @livewire('contact-url-form', ['contactUuid' => $contactUuid])
                <!-- Relations -->
                @livewire('contact-relation-form', ['contactUuid' => $contactUuid])
                <!-- Settings -->
                @livewire('contact-setting-form', ['contactUuid' => $contactUuid])

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('contacts.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="button" class="btn btn-primary" wire:click.prevent="saveTest">Save Contact</button>
                </div>

            </div>
        </div>

        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
