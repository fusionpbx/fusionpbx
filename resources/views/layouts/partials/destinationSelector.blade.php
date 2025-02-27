@php
    /**
     * @property string $type
     * @property string $id
     * @property string $value
     * @property array $extensions
     */
        $arrval = $id;
        if($id === '__NEWROWID__') {
            $arrval = 'newrow'.$arrval;
        }
@endphp
<div class="d-flex">
    <div class="mx-1">
        <select onchange="(function(){if($('#{{$type}}_type_{{$id}}').val() === 'external'){$('#{{$type}}_target_external_wrapper_{{$id}}').show();$('#{{$type}}_target_internal_wrapper_{{$id}}').hide();$('#{{$type}}_target_internal_{{$id}}').val('0');$('#{{$type}}_target_internal_{{$id}}').trigger('change');} else {$('#{{$type}}_target_internal_wrapper_{{$id}}').show();$('#{{$type}}_target_external_wrapper_{{$id}}').hide();$('#{{$type}}_target_external_{{$id}}').val('');}})()" id="{{$type}}_type_{{$id}}" name="{{$type}}[{{$arrval}}][type]">
            <option value="internal" @if (!detect_if_phone_number($value)) selected @endif>Internal</option>
            <option value="external" @if (detect_if_phone_number($value)) selected @endif>External</option>
        </select>
    </div>
    <div class="flex-fill">
        <div id="{{$type}}_target_external_wrapper_{{$id}}" class="destination_wrapper"
             @if (!detect_if_phone_number($value)) style="display: none;" @endif
        >
            <input type="text" id="{{$type}}_target_external_{{$id}}"
                   class="form-control dest-external" name="{{$type}}[{{$arrval}}][target_external]"
                   placeholder="Enter phone number"
                   @if (detect_if_phone_number($value))
                       value="{{$value}}"
                   @else
                       value=""
                   @endif
            />
        </div>
        <div id="{{$type}}_target_internal_wrapper_{{$id}}" class="destination_wrapper"
             @if (detect_if_phone_number($value)) style="display: none;" @endif
        >
            <select id="{{$type}}_target_internal_{{$id}}"
                    class="dest-internal"
                    name="{{$type}}[{{$arrval}}][target_internal]">
                <option value="0" @if($value == '') selected @endif>Choose destination</option>
                @foreach($extensions as $group => $exts)
                    <optgroup label="{{$group}}">
                        @foreach($exts as $ext)
                            <option value="{{ $ext->getId() }}" @if((string)$value === (string)$ext->getId()) selected @endif>
                                {{ $ext->getName() }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="text-danger {{$type}}_{{$arrval}}_target_external_err error_message"></div>
<div class="text-danger {{$type}}_{{$arrval}}_target_internal_err error_message"></div>
