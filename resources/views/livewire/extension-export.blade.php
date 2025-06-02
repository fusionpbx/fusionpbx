
<div>
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded">
        <h2 class="mb-0 fw-bold">{{ __('Extension Export') }}</h2>
        <div class="d-flex gap-2">
            <button wire:click="goBack" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
            </button>
            <button wire:click="exportExtensions" 
                    class="btn btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="exportExtensions">
                <span wire:loading.remove wire:target="exportExtensions">
                    <i class="fas fa-download me-2"></i>{{ __('Export') }}
                </span>
                <span wire:loading wire:target="exportExtensions">
                    <i class="fas fa-spinner fa-spin me-2"></i>{{ __('Exporting...') }}
                </span>
            </button>
        </div>
    </div>

    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        {{ __('Select the columns you want to include in the extension export. The data will be downloaded as a CSV file.') }}
    </div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <i class="fas fa-check-square me-1"></i>
                {{ count($selectedColumns) }} of {{ count($availableColumns) }} columns selected
            </small>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <div class="form-check">
                <input wire:model.live="selectAll" 
                       class="form-check-input" 
                       type="checkbox" 
                       id="selectAll">
                <label class="form-check-label fw-bold" for="selectAll">
                    {{ __('Column Name') }}
                </label>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <tbody>
                        @foreach($availableColumns as $columnKey => $columnLabel)
                            <tr>
                                <td class="align-middle" style="width: 50px;">
                                    <div class="form-check">
                                        <input wire:model.live="selectedColumns" 
                                               class="form-check-input" 
                                               type="checkbox" 
                                               value="{{ $columnKey }}"
                                               id="column_{{ $loop->index }}">
                                    </div>
                                </td>
                                <td class="align-middle cursor-pointer"
                                    onclick="toggleCheckbox('column_{{ $loop->index }}')">
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $columnLabel }}</span>
                                        <small class="text-muted">{{ $columnKey }}</small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Click on a row to toggle selection
                </small>
                <div class="d-flex gap-2">
                    <button type="button" 
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="$set('selectedColumns', [])">
                        {{ __('Clear All') }}
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary"
                            wire:click="$set('selectAll', true)">
                        {{ __('Select All') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-md-none mt-3">
        <button wire:click="exportExtensions" 
                class="btn btn-primary w-100"
                wire:loading.attr="disabled"
                wire:target="exportExtensions"
                @disabled(count($selectedColumns) === 0)>
            <span wire:loading.remove wire:target="exportExtensions">
                <i class="fas fa-download me-2"></i>{{ __('Export Selected Columns') }}
            </span>
            <span wire:loading wire:target="exportExtensions">
                <i class="fas fa-spinner fa-spin me-2"></i>{{ __('Exporting...') }}
            </span>
        </button>
    </div>
</div>

@push('scripts')
<script>
    function toggleCheckbox(checkboxId) {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.click();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            });
        }, 5000);
    });
</script>
@endpush

@push('styles')
<style>
    .cursor-pointer {
        cursor: pointer;
    }
    
    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    @media (max-width: 768px) {
        .table td {
            padding: 0.75rem 0.5rem;
        }
    }
</style>
@endpush