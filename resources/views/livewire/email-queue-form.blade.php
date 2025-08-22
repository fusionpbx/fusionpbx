<div>
    <div>
        <div class="container-fluid">
            <div class="card card-primary mt-3 card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            {{ $isEditing ? 'Edit Email Queue Entry' : 'Create Email Queue Entry' }}
                        </h3>
                        <div>
                            @if ($isEditing)
                                @can('email_queue_resend')
                                    <button type="button" class="btn btn-warning btn-sm" wire:click="resend">
                                        <i class="fa fa-redo" aria-hidden="true"></i> {{ __('Resend') }}
                                    </button>
                                @endcan

                                @can('email_queue_test')
                                    <button type="button" class="btn btn-info btn-sm" wire:click="testEmail">
                                        <i class="fa fa-paper-plane" aria-hidden="true"></i> {{ __('Test Email') }}
                                    </button>
                                @endcan

                                @can('email_queue_delete')
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="delete"
                                        onclick="return confirm('{{ __('Are you sure you want to delete this email queue entry?') }}')">
                                        <i class="fa fa-trash" aria-hidden="true"></i> {{ __('Delete') }}
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <h5 class="mb-3">{{ __('Email Information') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="email_from" class="form-label">{{ __('From Email') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('email_from') is-invalid @enderror"
                                        id="email_from" wire:model="email_from" placeholder="sender@domain.com"
                                        required>
                                    @error('email_from')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="email_to" class="form-label">{{ __('To Email') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('email_to') is-invalid @enderror"
                                        id="email_to" wire:model="email_to" placeholder="recipient@domain.com"
                                        required>
                                    @error('email_to')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="email_subject" class="form-label">{{ __('Subject') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('email_subject') is-invalid @enderror"
                                        id="email_subject" wire:model="email_subject" placeholder="Enter email subject"
                                        required>
                                    @error('email_subject')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="email_body" class="form-label">{{ __('Email Body') }} <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control @error('email_body') is-invalid @enderror" id="email_body" wire:model="email_body"
                                        rows="8" placeholder="Enter email body content" required></textarea>
                                    @error('email_body')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">{{ __('Status Configuration') }}</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="email_status" class="form-label">{{ __('Status') }}</label>
                                            <select class="form-select @error('email_status') is-invalid @enderror"
                                                id="email_status" wire:model="email_status">
                                                @foreach ($statusOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('email_status')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="email_retry_count"
                                                class="form-label">{{ __('Retry Count') }}</label>
                                            <input type="number"
                                                class="form-control @error('email_retry_count') is-invalid @enderror"
                                                id="email_retry_count" wire:model="email_retry_count" placeholder="0"
                                                min="0" max="10">
                                            @error('email_retry_count')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="hostname" class="form-label">{{ __('Hostname') }}</label>
                                            <input type="text"
                                                class="form-control @error('hostname') is-invalid @enderror"
                                                id="hostname" wire:model="hostname" placeholder="server.domain.com">
                                            @error('hostname')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">{{ __('Date Information') }}</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email_date" class="form-label">{{ __('Email Date') }}</label>
                                            <input type="datetime-local"
                                                class="form-control @error('email_date') is-invalid @enderror"
                                                id="email_date" wire:model="email_date">
                                            @error('email_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @if ($formattedEmailDate)
                                                <small class="form-text text-muted">
                                                    {{ __('Formatted') }}: {{ $formattedEmailDate }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="accordion mb-4" id="actionsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="actionsHeading">
                                    <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#actionsCollapse"
                                        aria-expanded="false" aria-controls="actionsCollapse">
                                        <i class="fa fa-cog me-2"></i>
                                        {{ __('Actions Configuration') }}
                                    </button>
                                </h2>
                                <div id="actionsCollapse" class="accordion-collapse collapse"
                                    aria-labelledby="actionsHeading" data-bs-parent="#actionsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="email_action_after"
                                                        class="form-label">{{ __('Action After') }}</label>
                                                    <textarea class="form-control @error('email_action_after') is-invalid @enderror" id="email_action_after"
                                                        wire:model="email_action_after" rows="4" placeholder="Enter action to execute after sending email"></textarea>
                                                    @error('email_action_after')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted">
                                                        {{ __('Script or command to execute after sending the email') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-secondary me-2" wire:click="goBack">
                                            <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> {{ $isEditing ? __('Update') : __('Create') }}
                                        </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if ($isEditing && $emailQueue)
                <div class="card card-info mt-3 card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Email Queue Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>{{ __('UUID') }}:</strong><br>
                                <small class="text-muted">{{ $emailQueue->email_queue_uuid }}</small>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('Current Status') }}:</strong><br>
                                <span
                                    class="badge badge-{{ $email_status === 'sent' ? 'success' : ($email_status === 'failed' ? 'danger' : ($email_status === 'trying' ? 'warning' : 'secondary')) }}">
                                    {{ $statusOptions[$email_status] ?? $email_status }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('Retry Count') }}:</strong><br>
                                <span
                                    class="badge badge-{{ $email_retry_count > 3 ? 'danger' : ($email_retry_count > 0 ? 'warning' : 'success') }}">
                                    {{ $email_retry_count }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('Created') }}:</strong><br>
                                <small
                                    class="text-muted">{{ $emailQueue->created_at ? $emailQueue->created_at->format('d M Y H:i:s') : 'N/A' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(function(alert) {
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);

            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(function(textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
            });

            window.addEventListener('confirm-delete', event => {
                if (confirm(event.detail.message)) {
                    @this.call('delete');
                }
            });
        });

        document.addEventListener('livewire:load', function() {
        });
    </script>

    <style>
        .badge-secondary {
            background-color: #6c757d;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .card-outline.card-primary {
            border-top: 3px solid #007bff;
        }

        .card-outline.card-info {
            border-top: 3px solid #17a2b8;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .accordion-button:not(.collapsed) {
            background-color: #e3f2fd;
            border-color: #007bff;
        }

        .spinner-border {
            width: 2rem;
            height: 2rem;
        }
    </style>
</div>
