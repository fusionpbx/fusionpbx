@extends('layouts.app', ["page_title"=> "Voicemail Messages"])

@section('content')

<!-- Start Content-->
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Voicemail Messages ({{ $voicemail->voicemail_id ?? '' }})</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-xl-4">
                            <label class="form-label">Showing {{ $messages->count() ?? 0 }}  results for Voicemail Messages</label>
                        </div>
                        <div class="col-xl-8">
                            <div class="text-xl-end mt-xl-0 mt-2">
                                @if ($permissions['add_new'])
                                    <a href="{{ route('voicemails.create') }}" class="btn btn-success mb-2 me-2">
                                        <i class="mdi mdi-plus-circle me-1"></i>
                                        Add New
                                    </a>
                                @endif
                                @if ($permissions['delete'])
                                    <a href="javascript:confirmDeleteAction('{{ route('voicemails.messages.destroy', ':id') }}');" id="deleteMultipleActionButton" class="btn btn-danger mb-2 me-2 disabled">
                                        Delete Selected
                                    </a>
                                @endif
                                {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
                            </div>
                        </div><!-- end col-->
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered mb-0" id="voicemail_list">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20px;">
                                        @if ($permissions['delete'])
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                                                <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                                            </div>
                                        @endif
                                    </th>
                                    <th>Caller ID</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($messages as $key=>$message)
                                    <tr id="id{{ $message->voicemail_message_uuid  }}">
                                        <td>
                                            @if ($permissions['delete'])
                                                <div class="form-check">
                                                    <input type="checkbox" name="action_box[]" value="{{ $message->voicemail_message_uuid }}" class="form-check-input action_checkbox">
                                                    <label class="form-check-label" >&nbsp;</label>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-body @if ($message->message_status == '') fw-bold @endif">
                                                {{ $message->caller_id_name ?? ''}}
                                            </span>
                                            <br>
                                            <span class="text-body @if ($message->message_status == '') fw-bold @endif">
                                                {{ $message->caller_id_number ?? '' }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $message->date }}
                                        </td>

                                        <td>
                                            <audio id="{{ $message->voicemail_message_uuid  }}_audio_file"
                                                src="{{ route('getVoicemailMessage', $message ) }}">
                                            </audio>

                                            <a href="javascript:playVmMessage('{{ $message->voicemail_message_uuid  }}');" 
                                                id="{{ $message->voicemail_message_uuid  }}_play_button" class="btn btn-light" title="Play">
                                                <i class="uil uil-play"></i>                                            
                                            </a>

                                            <a href="javascript:pauseVmMessage('{{ $message->voicemail_message_uuid  }}');" class="btn btn-light"
                                                id="{{ $message->voicemail_message_uuid  }}_pause_button" title="Pause" style="display: none">
                                                <i class="uil uil-pause"></i>
                                            </a>
                                            
                                            <a href="{{ route('downloadVoicemailMessage', $message->voicemail_message_uuid ) }}">
                                                    <button type="button" class="btn btn-light" title="Download">
                                                        <i class="uil uil-down-arrow"></i> 
                                                    </button>
                                            </a>
{{-- 
                                            <button id="voicemail_unavailable_delete_file_button" type="button" class="btn btn-light" title="Delete"
                                                data-url="{{ route('deleteVoicemailGreeting', ['voicemail' => $voicemail->voicemail_uuid,'filename' => 'greeting_1.wav'] ) }}">
                                                <span id="voicemail_unavailable_delete_file_button_icon" ><i class="uil uil-trash-alt"></i> </span>
                                                <span id="voicemail_unavailable_delete_file_button_spinner" hidden class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            </button> --}}

                                            <a href="javascript:confirmDeleteAction('{{ route('voicemails.messages.destroy', ':id') }}','{{ $message->voicemail_message_uuid }}');" class="btn btn-light"> 
                                                <i class="uil uil-trash-alt" title="Delete"></i>
                                            </a>
               
                                        </td>
                                    


                                        
                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                    </div>
                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    <!-- end row -->

</div> <!-- container -->
@endsection


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#selectallCheckbox').on('change',function(){
            if($(this).is(':checked')){
                $('.action_checkbox').prop('checked',true);
            } else {
                $('.action_checkbox').prop('checked',false);
            }
        });

        $('.action_checkbox').on('change',function(){
            if(!$(this).is(':checked')){
                $('#selectallCheckbox').prop('checked',false);
            } else {
                if(checkAllbox()){
                    $('#selectallCheckbox').prop('checked',true);
                }
            }
        });
    });

    //This function plays the VM message
    function playVmMessage(message_uuid){
        $('#' + message_uuid + '_play_button').hide();
        $('#' + message_uuid + '_pause_button').show();
        var audioElement = document.getElementById(message_uuid + '_audio_file');
        audioElement.play();
    }

    //This function pauses playing VM message
    function pauseVmMessage(message_uuid){
        $('#' + message_uuid + '_pause_button').hide();
        $('#' + message_uuid + '_play_button').show();
        var audioElement = document.getElementById(message_uuid + '_audio_file');
        audioElement.pause();
    }

    

    function checkAllbox(){
        var checked=true;
        $('.action_checkbox').each(function(key,val){
            if(!$(this).is(':checked')){
                checked=false;
            }
        });
        return checked;
    }



    
    
</script>
@endpush