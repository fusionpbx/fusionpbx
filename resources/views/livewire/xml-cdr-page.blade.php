<div>
    {{-- Filter Form --}}
    <div class="row g-2 gx-4">
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label>Direction</label>
                <select name="direction" class="form-select" wire:model.defer="filters.direction">
                    <option value=""></option>
                    <option value="inbound">Inbound</option>
                    <option value="outbound">Outbound</option>
                    <option value="local">Local</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group mb-3">
                <label></label>
                <select name="leg" class="form-select" wire:model.defer="filters.leg">
                    <option value=""></option>
                    <option value="a">a-leg</option>
                    <option value="b">b-leg</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Status</label>
                <select name="call_result" class="form-select" wire:model.defer="filters.status">
                    <option value=""></option>
                    <option value="answered">Answered</option>
                    <option value="missed">Missed</option>
                    <option value="voicemail">Voicemail</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row g-2 gx-4">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Extension</label>
                <input type="text" class="form-control" wire:model.defer="filters.extension">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label>Caller ID</label>
                <input type="text" class="form-control" placeholder="{{__('Name')}}" wire:model.defer="filters.caller_id_name">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label></label>
                <input type="text" class="form-control" placeholder="{{__('Number')}}" wire:model.defer="filters.caller_id_number">
            </div>
        </div>
    </div>
    <div class="row g-2 gx-4">
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label>Start Range</label>
                <div class="input-group log-event" id="start_range_from" data-td-target-input="nearest" data-td-target-toggle="nearest">
                    <input type="text" class="form-control datetimepicker" data-td-target="#start_range_from" placeholder="{{__('From')}}" wire:model.defer="filters.start_range_from">
                    <span class="input-group-text" data-td-target="#start_range_from" data-td-toggle="datetimepicker"><i class="fas fa-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label></label>
                <div class="input-group log-event" id="start_range_to" data-td-target-input="nearest" data-td-target-toggle="nearest">
                    <input type="text" class="form-control datetimepicker" data-td-target="#start_range_to" placeholder="{{__('To')}}" wire:model.defer="filters.start_range_to">
                    <span class="input-group-text" data-td-target="#start_range_to" data-td-toggle="datetimepicker"><i class="fas fa-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label>Duration (sec)</label>
                <input type="number" class="form-control" placeholder="Minimum" wire:model.defer="filters.duration_min">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group mb-3">
                <label></label>
                <input type="number" class="form-control" placeholder="Maximum" wire:model.defer="filters.duration_max">
            </div>
        </div>
    </div>
    <div class="row g-2 gx-4">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Caller Destination</label>
                <input type="number" class="form-control" wire:model.defer="filters.caller_destination">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Destination</label>
                <input type="number" class="form-control" wire:model.defer="filters.destination_number">
            </div>
        </div>
    </div>
    <div class="row g-2 gx-4">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>TTA (sec)</label>
                <input type="number" class="form-control" wire:model.defer="filters.tta">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Hangup Cause</label>
                <select name="hangup_cause" class="form-select" wire:model.defer="filters.hangup_cause">
				    <option value=""></option>
                    <option value="ALLOTTED_TIMEOUT">Allotted Timeout</option>
                    <option value="ATTENDED_TRANSFER">Attended Transfer</option>
                    <option value="BLIND_TRANSFER">Blind Transfer</option>
                    <option value="CALL_REJECTED">Call Rejected</option>
                    <option value="CHAN_NOT_IMPLEMENTED">Chan Not Implemented</option>
                    <option value="DESTINATION_OUT_OF_ORDER">Destination Out Of Order</option>
                    <option value="EXCHANGE_ROUTING_ERROR">Exchange Routing Error</option>
                    <option value="INCOMPATIBLE_DESTINATION">Incompatible Destination</option>
                    <option value="INVALID_NUMBER_FORMAT">Invalid Number Format</option>
                    <option value="LOSE_RACE">Lose Race</option>
                    <option value="MANAGER_REQUEST">Manager Request</option>
                    <option value="MANDATORY_IE_MISSING">Mandatory Ie Missing</option>
                    <option value="MEDIA_TIMEOUT">Media Timeout</option>
                    <option value="NETWORK_OUT_OF_ORDER">Network Out Of Order</option>
                    <option value="NONE">None</option>
                    <option value="NORMAL_CLEARING">Normal Clearing</option>
                    <option value="NORMAL_TEMPORARY_FAILURE">Normal Temporary Failure</option>
                    <option value="NORMAL_UNSPECIFIED">Normal Unspecified</option>
                    <option value="NO_ANSWER">No Answer</option>
                    <option value="NO_ROUTE_DESTINATION">No Route Destination</option>
                    <option value="NO_USER_RESPONSE">No User Response</option>
                    <option value="ORIGINATOR_CANCEL">Originator Cancel</option>
                    <option value="PICKED_OFF">Picked Off</option>
                    <option value="RECOVERY_ON_TIMER_EXPIRE">Recovery On Timer Expire</option>
                    <option value="REQUESTED_CHAN_UNAVAIL">Requested Chan Unavail</option>
                    <option value="SUBSCRIBER_ABSENT">Subscriber Absent</option>
                    <option value="SYSTEM_SHUTDOWN">System Shutdown</option>
                    <option value="UNALLOCATED_NUMBER">Unallocated Number</option>
                    <option value="USER_BUSY">User Busy</option>
                    <option value="USER_NOT_REGISTERED">User Not Registered</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row g-2 gx-4">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label>Recording</label>
                <select name="recording" class="form-select" wire:model.defer="filters.recording">
                    <option value=""></option>
                    <option value="true">True</option>
                    <option value="false">False</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label>Order</label>
                <select name="order_field" class="form-select" wire:model.defer="filters.order_field">
                    <option value="extension">Extension</option>
                    <option value="domain_name">Domain</option>
                    <option value="caller_id_name">Caller Name</option>
                    <option value="caller_id_number">Caller Number</option>
                    <option value="caller_destination">Caller Destination</option>
                    <option value="destination_number">Destination</option>
                    <option value="start_stamp" selected="selected">Start</option>
                    <option value="tta">TTA</option>
                    <option value="duration">Duration</option>
                    <option value="pdd_ms">PDD</option>
                    <option value="rtp_audio_in_mos">MOS</option>
                    <option value="hangup_cause">Hangup Cause</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group mb-3">
                <label></label>
                <select name="order_sort" class="form-select" wire:model.defer="filters.order_sort">
                    <option value="desc" selected="selected">Descending</option>
                    <option value="asc">Ascending</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-5 justify-content-end">
        <div class="col-auto">
            <button wire:click="applyFilters" class="btn btn-primary">Apply</button>
        </div>
        <div class="col-auto">
            <button wire:click="resetFilters" class="btn btn-secondary">Reset</button>
        </div>
    </div>

    {{-- Table --}}
    <livewire:xml-cdr-table :filters="$filters" :key="json_encode($filters)"/>
</div>
