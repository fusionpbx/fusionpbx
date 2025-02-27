<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label for="timeout_category" class="form-label">If not answered, calls will be sent</label>
            <div class="row">
                <div class="col-md-4 col-sm-4">
                    <select class="select2 form-control" data-toggle="select2" data-placeholder="Choose option..."
                            id="timeout_category" name="timeout_category">
                        <option value="">
                        </option>
                        <option value="disabled" @if($destinationsByCategory == 'disabled') selected="selected" @endif>
                            Hang up
                        </option>
                        <option value="ringgroup" @if($destinationsByCategory == 'ringgroup') selected="selected" @endif>
                            Ring Group
                        </option>
                        {{-- <option value="dialplans" @if($destinationsByCategory == 'dialplans') selected="selected" @endif>
                            Dial Plans
                        </option> --}}
                        <option value="extensions" @if($destinationsByCategory == 'extensions') selected="selected" @endif>
                            Extension
                        </option>
                        {{--<option value="timeconditions" @if($destinationsByCategory == 'timeconditions') selected="selected" @endif>
                            Time Conditions
                        </option>--}}
                        <option value="voicemails" @if($destinationsByCategory == 'voicemails') selected="selected" @endif>
                            Voicemail
                        </option>
                        <option value="ivrs" @if($destinationsByCategory == 'ivrs') selected="selected" @endif>
                            Auto Receptionist
                        </option>
                        <option value="recordings" @if($destinationsByCategory == 'recordings') selected="selected" @endif>
                            Recordings
                        </option>
                        <option value="others" @if($destinationsByCategory == 'others') selected="selected" @endif>
                            Miscellaneous
                        </option>
                    </select>
                    <div id="timeout_category_err" class="text-danger error_message"></div>
                </div>
                <div id="timeout_action_wrapper" class="col-md-8 col-sm-8"
                     @if($destinationsByCategory == 'disabled') style="display: none" @endif>
                    @foreach($timeoutDestinationsByCategory as $category => $items)
                        <div id="timeout_action_wrapper_{{$category}}" @if($destinationsByCategory != $category) style="display: none" @endif>
                            <select class="select2 form-control" data-toggle="select2" data-placeholder="Choose ..."
                                    id="timeout_action_{{$category}}" name="timeout_action_{{$category}}">
                                @foreach($items as $item)
                                    <option value="{{$item['id']}}"
                                            @if($entityUuid == $item['id']) selected="selected" @endif>
                                        {{$item['label']}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
            <div id="timeout_data_err" class="text-danger error_message"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timeoutCategory = $('#timeout_category');
            const timeoutActionWrapper = $('#timeout_action_wrapper');

            timeoutCategory.on('change', function(e) {
                e.preventDefault();
                if (e.target.value === 'disabled') {
                    timeoutActionWrapper.hide()
                    return;
                } else {
                    timeoutActionWrapper.show()
                }

                timeoutActionWrapper.find('div').hide();
                timeoutActionWrapper.find('div#timeout_action_wrapper_' + e.target.value).show();
            })
        });
    </script>
@endpush
