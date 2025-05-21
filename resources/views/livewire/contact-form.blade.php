<div>

    <div class= 'card card-primary mt-3 card-outline'>
        <form wire:submit.prevent="">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ isset($contact->contact_uuid) ? 'Edit Contact' : 'New Contact' }}</h3>

                    @if (isset($contact->contact_uuid))
                    <div class="card-tools">
                        @can('contact_edit')
                        <div class="d-flex gap-2 " role="contact" aria-label="contact actions">
                            <a href="{{ route('contacts.vcard', $contact->contact_uuid) }}"  class="btn btn-primary btn-sm">
                                <i class="fa fa-id-card" aria-hidden="true"></i> {{ __('V Card') }}
                            </a>
                            <a class="btn btn-primary btn-sm">
                                <i class="fa fa-qrcode" aria-hidden="true"></i> {{ __('QR Code') }}
                            </a>
                            <a href= ""  class="btn btn-primary btn-sm">
                                <i class="fas fa-users mr-1"></i> {{ __('Users') }}
                            </a>
                        </div>
                        @endcan
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @livewire('basic-information-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-email-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-phone-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-address-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-url-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-relation-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-setting-form', ['contactUuid' => $contactUuid])

                    @livewire('contact-attachment-form', ['contactUuid' => $contactUuid])
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('contacts.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-primary"
                            wire:click.prevent="saveTest">{{ isset($contact->contact_uuid) ? 'Update' : 'Create' }}</button>
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
</div>
