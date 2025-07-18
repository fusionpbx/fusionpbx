<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('LCR Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2" role="lcr" aria-label="LCR actions">
                    @can('lcr_add')
                    <a href="{{ route('lcr.create', isset($carrier) ? ['carrier_uuid' => $carrier->carrier_uuid] : []) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>
                    @endcan
                    @can('lcr_edit')
                        <a class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#csv_upload">
                            <i class="fas fa-upload mr-1"></i> {{__('Upload')}}
                        </a>
                        <a href="{{ route('lcr.export', isset($carrier) ? ['carrier_uuid' => $carrier->carrier_uuid] : []) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-download mr-1"></i> {{__('Download')}}
                        </a>

                        <div class="modal fade" id="csv_upload" tabindex="-1" aria-labelledby="csv_upload" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Upload CSV</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="{{ route('lcr.import') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="carrier_uuid" value="{{ $carrier->carrier_uuid ?? null }}">
                                            <div class="row">
                                                <div class="col-md-10">
                                                    <div class="form-group">
                                                        <input name="upload_file" type="file" class="form-control" id="upload_file" accept=".csv">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 4px;">
                                                        {{ __('Send') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-10">
                                                    <div class="form-group">
                                                        <label class="form-label d-block">Clear ALL rates before importing</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="clear_before" value="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="form-label d-block">LCR Profile / Pricing list</label>
                                                        <input class="form-control" name='lcr_profile' type='text' value='default' required='required'>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <p style="font-size: 11px;">
                                                    <br><strong>CSV details:</strong> The file must contain the following column headers. Fields marked with * are required. The order of the columns does not matter.
                                                    <br>
                                                    <br><strong>Destination</strong> → description
                                                    <br><strong>Prefix *</strong> → digits
                                                    <br><strong>Connect Increment</strong> → connect_increment (if not specified then it will be the same as talk_increment)
                                                    <br><strong>Talking Increment *</strong> → talk_increment
                                                    <br><strong>Rate *</strong> → rate
                                                    <br><strong>Connect Rate</strong> → connect_rate (if not specified then it will be the same as rate)
                                                    <br><strong>IntraState Rate</strong> → intrastate_rate (if not specified then it will be the same as rate. Only useful for USA)
                                                    <br><strong>IntraLata Rate</strong> → intralata_rate (if not specified then it will be the same as rate. Only useful for USA)
                                                    <br><strong>Currency</strong> → currency (3 chars)
                                                    <br><strong>Direction</strong> → lcr_direction (inbound, outbound, internal)
                                                    <br><strong>Start Date</strong> → date_start (optional, if not specified then current date and time)
                                                    <br><strong>End Date</strong> → date_end (optional, if not specified then it will be 2099-12-31 06:50:00)
                                                    <br><strong>Profile</strong> → lcr_profile (use defaul if you dont know what to do, check lcr.conf.xml)
                                                    <br><strong>Lead Strip</strong> → lead_strip
                                                    <br><strong>Trail Strip</strong> → trail_strip
                                                    <br><strong>Add Prefix</strong> → prefix
                                                    <br><strong>Add Suffix</strong> → suffix
                                                    <br><strong>Random</strong> → any value you want
                                                    <br>
                                                    <br>This is a simple import, check the box if you want to overwrite prefixes
                                                </p>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:lcr-table :carrier="$carrier ?? null"/>
        </div>
    </div>
</div>
