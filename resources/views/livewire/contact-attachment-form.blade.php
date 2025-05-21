<div>
    @filepondScripts
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6>Attachment <i class="fa fa-archive" aria-hidden="true"></i></h6>
                @can('contact_attachment_add')
                <button type="button" class="btn btn-sm btn-primary" wire:click="addAttachment"> <i class="fa fa-plus"
                        aria-hidden="true"></i>
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @foreach ($attachments as $index => $attachment)
                <div class="row mb-2">
                    <div class="col-md-6">
                        @if($attachment['attachment_uploaded_date'])
                            <div class="mb-2">
                                <a href="{{ $attachment['file'] }}" target="_blank">
                                    <img src="{{ $attachment['file'] }}" alt="Attachment" class="img-fluid" style="max-height: 20rem">
                                </a>
                            </div>
                            <input type="hidden" wire:model="attachments.{{ $index }}.file" />
                        @else
                            <x-filepond::upload wire:model="attachments.{{ $index }}.file" />
                        @endif
                        @if (session()->has('success'))
                            <div class="text-green-500">{{ session('success') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <textarea id="attachments.{{ $index }}n" wire:model="attachments.{{ $index }}.attachment_description" placeholder="Description"
                            class="form-control @error('phone_description') is-invalid @enderror" rows="3"></textarea>
                        @error('attachment_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" wire:model="attachments.{{ $index }}.attachment_primary"
                                id="attachment_primary_{{ $index }}">
                            <label class="form-check-label" for="">
                                Primary</label>
                        </div>
                    </div>
                    @can('contact_attachment_delete')       
                    <div class="row mb-2">
                        <button type="button" class="btn btn-sm btn-danger"
                            wire:click="removeAttachment({{ $index }})">
                            <i class="bi bi-trash"></i> 
                        </button>
                    </div>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>
</div>
