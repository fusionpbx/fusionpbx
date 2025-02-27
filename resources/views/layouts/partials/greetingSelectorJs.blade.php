@if ($useScriptTag ?? true)
<script>
@endif
    @if ($useDocumentReadyTag ?? true)
    document.addEventListener('DOMContentLoaded', function() {
    @endif
        const recordWrapper = $('#{{ $id }}_record_wrapper');
        const greetingPlayPauseButton = $('#{{ $id }}_play_pause_button');
        const greetingManageButton = $('#{{ $id }}_manage_greeting_button');
        const greetingManageModal = $('#{{ $id }}_manage_greeting_modal');
        const greetingManageModalBody = $('#{{ $id }}_manage_greeting_modal_body');
        const audioElement = document.getElementById('{{ $id }}_audio_file');
        const greetingRecordButton = $('#{{ $id }}_record_button');
        const greetingRecordedPlayPauseButton = $('#{{ $id }}_recorded_play_pause_button');
        const greetingRecordInProgress = $('#{{ $id }}_record_in_progress_status');
        const greetingRecordIsDone = $('#{{ $id }}_record_is_done_status');
        const audioElementRecorded = document.getElementById('{{ $id }}_recorded_audio_file');
        const greetingRecordedAudioFileStored = $('#{{ $id }}_recorded_audio_file_stored');
        const greetingEditRecordingModal = $('#{{ $id }}_editRecordingModal');
        const greetingRecorderSaveButton = $('#{{ $id }}_save_recording_btn');
        const greetingUploadButton = $('#{{ $id }}_filename');
        let gumStream;
        let mediaRecorder;
        let chunks = [];
        let extension;
        let codec;

        if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
            extension = "webm";
            codec = "opus";
            recordWrapper.removeClass('d-none');
        } else if (MediaRecorder.isTypeSupported('audio/mp4;codecs=mp4a')) {
            extension = "mp4";
            codec = "mp4a";
            recordWrapper.removeClass('d-none');
        } else {
            console.warn('Your browser does not support recording audio.');
        }

        greetingUploadButton.on('change', function() {
            greetingRecorderSaveButton.attr('disabled', false);
        });

        greetingRecordButton.on('click', function() {
            if (mediaRecorder instanceof MediaRecorder && mediaRecorder.state === "recording") {
                mediaRecorder.stop();
                gumStream.getAudioTracks()[0].stop();
                greetingRecordButton.html('<i class="mdi mdi-record"></i>');
                greetingRecordInProgress.addClass('d-none');
                greetingRecordIsDone.removeClass('d-none');
                greetingRecordedPlayPauseButton.attr('disabled', false);
                return;
            }

            greetingRecordButton.html('<i class="mdi mdi-stop"></i>');
            greetingRecordInProgress.removeClass('d-none');
            greetingRecordIsDone.addClass('d-none');
            greetingRecordedPlayPauseButton.attr('disabled', true);
            const constraints = {
                audio: true
            }
            navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
                console.log(
                    "getUserMedia() success, stream created, initializing MediaRecorder");
                let chunks = [];
                gumStream = stream;
                mediaRecorder = new MediaRecorder(stream, {
                    audioBitsPerSecond: 256000,
                    videoBitsPerSecond: 2500000,
                    bitsPerSecond: 2628000,
                    mimeType: `audio/${extension};codecs=${codec}`
                });

                //when data becomes available add it to our attay of audio data
                mediaRecorder.ondataavailable = function(e) {
                    // add stream data to chunks
                    chunks.push(e.data);
                    // if recorder is 'inactive' then recording has finished
                    if (mediaRecorder.state === 'inactive') {
                        // convert stream data chunks to a 'webm' audio format as a blob
                        const blob = new Blob(chunks, {
                            type: 'audio/' + extension,
                            bitsPerSecond: 128000
                        });
                        console.log("Saving chunk : " + blob.size);
                        const url = URL.createObjectURL(blob);
                        audioElementRecorded.setAttribute('src', url);
                        audioElementRecorded.load()
                        const formData = new FormData();
                        formData.append('recorded_file', blob, 'recordedAudio');
                        $.ajax({
                            type: "POST",
                            url: '{{ route('recordings.storeBlob') }}',
                            cache: false,
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(result) {
                                console.log(result);
                                greetingRecordedAudioFileStored.val(result
                                    .tempfile);
                                greetingRecorderSaveButton.attr('disabled',
                                    false);
                            },
                            error: function(error) {
                                console.error(error);
                            }
                        });
                    }
                };
                mediaRecorder.onerror = function(e) {
                    console.error(e.error);
                }
                mediaRecorder.start(1000);
            }).catch(function(err) {
                greetingRecordButton.html('<i class="mdi mdi-record"></i>');
                greetingRecordIsDone.addClass('d-none');
                greetingRecordInProgress.addClass('d-none');
                console.error("navigator.mediaDevices.getUserMedia() error: " + err);
                alert(
                    `The unexpected issue is occurred ${err}. Please try again or use different browser.`
                );
            });
        })

        $('#{{ $id }}').on('change', function(e) {
            greetingPlayPauseButton.attr('disabled', true)
            if (e.target.value === '' || e.target.value === 'disabled') {
                greetingPlayPauseButton.addClass('d-none');
            } else {
                greetingPlayPauseButton.removeClass('d-none');
                document.getElementById('{{ $id }}_audio_file').setAttribute('src',
                    '{{ route('recordings.file', ['filename' => '/']) }}/' + e.target.value);
                audioElement.load();
            }
        })
        greetingManageButton.on('click', function() {
            greetingManageModal.modal('show');
        });
        greetingManageModal.on('shown.bs.modal', function() {
            loadAllRecordings(greetingManageModalBody);
        });
        greetingManageModal.on('hidden.bs.modal', function() {
            greetingManageModalBody.html($('<div class="loading loading-inline"></div>'))
        });
        greetingPlayPauseButton.click(function() {
            if (audioElement.paused) {
                // console.log('Audio paused. Start')
                greetingPlayPauseButton.find('i').removeClass('uil-play').addClass('uil-pause')
                audioElement.play();
            } else {
                // console.log('Audio playing. Pause')
                greetingPlayPauseButton.find('i').removeClass('uil-pause').addClass('uil-play')
                audioElement.currentTime = 0;
                audioElement.pause();
            }
        });
        audioElement.addEventListener('ended', (event) => {
            console.log('Audio ended ' + event.target.src)
            greetingPlayPauseButton.find('i').removeClass('uil-pause').addClass('uil-play')
            greetingManageModalBody.find('table').find('tr').find('i').removeClass('uil-pause')
                .addClass('uil-play')
        });
        audioElement.addEventListener('canplay', (event) => {
            // console.log('Audio loaded ' + event.target.src)
            greetingPlayPauseButton.attr('disabled', false)
        });
        greetingRecordedPlayPauseButton.click(function() {
            if (audioElementRecorded.paused) {
                // console.log('Recorded audio paused. Start')
                greetingRecordedPlayPauseButton.find('i').removeClass('mdi-play').addClass('mdi-pause')
                audioElementRecorded.play();
            } else {
                greetingRecordedPlayPauseButton.find('i').removeClass('mdi-pause').addClass('mdi-play')
                audioElementRecorded.currentTime = 0;
                audioElementRecorded.pause();
            }
        });
        audioElementRecorded.addEventListener('ended', (event) => {
            console.log('Recorded audio ended ' + event.target.src)
            greetingRecordedPlayPauseButton.find('i').removeClass('mdi-pause').addClass('mdi-play')
        });
        greetingRecorderSaveButton.on('click', function(e) {
            e.preventDefault();

            var formData = new FormData();
            formData.append('greeting_filename', document.getElementById(
                '{{ $id }}_filename').files[0]);
            formData.append('greeting_recorded_file', greetingRecordedAudioFileStored.val());

            $.ajax({
                type: "POST",
                url: '{{ route('recordings.store') }}',
                cache: false,
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    //Reset error messages
                    greetingManageModal.find('.error_message').text('');
                    greetingRecorderSaveButton.attr('disabled', true);
                    $('.loading').show();
                },
                complete: function(xhr, status) {
                    $('.loading').hide();
                },
                success: function(result) {
                    $('.loading').hide();
                    greetingManageModal.find('#{{ $id }}_filename').val('');
                    audioElementRecorded.src = '';
                    greetingRecordedAudioFileStored.val('');
                    greetingRecordedPlayPauseButton.attr('disabled', true);
                    greetingRecordIsDone.addClass('d-none');
                    greetingRecordInProgress.addClass('d-none');
                    greetingRecorderSaveButton.attr('disabled', true);
                    $.NotificationApp.send("Success", result.message, "top-right",
                        "#10c469", "success");
                    $('#{{ $id }}').append(new Option(result.name, result
                        .filename, true, true)).trigger('change');
                    loadAllRecordings(greetingManageModalBody, result.id);
                },
                error: function(error) {
                    $('.loading').hide();
                    greetingManageModal.find('.btn').attr('disabled', false);
                    if (error.status === 422) {
                        if (error.responseJSON.errors) {
                            let errors = {};
                            for (const key in error.responseJSON.errors) {
                                errors['{{ $id }}_' + key] = error.responseJSON
                                    .errors[key];
                            }
                            printErrorMsg(errors);
                        } else {
                            printErrorMsg(error.responseJSON.message);
                        }
                    } else {
                        printErrorMsg(error.responseJSON.message);
                    }
                }
            });
        });

        $('.save-description-btn').on('click', function(e) {
            e.preventDefault();
            var formData = new FormData();
            formData.append('greeting_name', $('#{{ $id }}_name').val());
            formData.append('greeting_description', $('#{{ $id }}_description').val());
            formData.append('_method', 'PUT');
            var url = '{{ route('recordings.update', ':id') }}'
            url = url.replace(':id', $('#{{ $id }}_id').val());
            $.ajax({
                type: "POST",
                url: url,
                cache: false,
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    //Reset error messages
                    greetingEditRecordingModal.find('.error_message').text('');
                    greetingEditRecordingModal.find('.save-description-btn').attr(
                        'disabled', true);
                    $('.loading').show();
                },
                complete: function(xhr, status) {
                    greetingEditRecordingModal.find('.save-description-btn').attr(
                        'disabled', false);
                    $('.loading').hide();
                },
                success: function(result) {
                    $('.loading').hide();
                    greetingEditRecordingModal.find('#{{ $id }}_name').val('');
                    greetingEditRecordingModal.find('#{{ $id }}_description')
                        .val('');
                    greetingEditRecordingModal.modal('hide');
                    $.NotificationApp.send("Success", result.message, "top-right",
                        "#10c469", "success");
                    loadAllRecordings(greetingManageModalBody);
                },
                error: function(error) {
                    $('.loading').hide();
                    greetingManageModal.find('.btn').attr('disabled', false);
                    if (error.status === 422) {
                        if (error.responseJSON.errors) {
                            let errors = {};
                            for (const key in error.responseJSON.errors) {
                                errors['{{ $id }}_' + key] = error.responseJSON
                                    .errors[key];
                            }
                            printErrorMsg(errors);
                        } else {
                            printErrorMsg(error.responseJSON.message);
                        }
                    } else {
                        printErrorMsg(error.responseJSON.message);
                    }
                }
            });
        })

        greetingManageModalBody.on('click', '.{{$id}}_play_current_recording_action', function(e) {
            e.preventDefault();
            let a = $(e.target).closest('a')
            playCurrentRecording(a.data('id'), a.data('filename'));
        })
        greetingManageModalBody.on('click', '.{{$id}}_use_recording_action', function(e) {
            e.preventDefault();
            let a = $(e.target).closest('a')
            useRecordingAction(a.data('route'), a.data('id'), a.data('entity'), a.data('entityid'));
        })
        greetingManageModalBody.on('click', '.{{$id}}_edit_recording_action', function(e) {
            e.preventDefault();
            let a = $(e.target).closest('a')
            editRecordingAction(a.data('route'), a.data('id'));
        })
        greetingManageModalBody.on('click', '.{{$id}}_confirm_delete_recording_action', function(e) {
            e.preventDefault();
            let a = $(e.target).closest('a')
            confirmDeleteRecordingAction(a.data('route'), a.data('id'));
        })
    @if ($useDocumentReadyTag ?? true)
    });
    @endif

    function loadAllRecordings(tgt, blinkId = null) {
        tgt.html($('<div class="loading loading-inline"></div>'));
        $.ajax({
            type: "GET",
            url: '{{ route('recordings.index') }}'
        }).done(function(response) {
            if (response.collection.length > 0) {
                let tb = $('<table>');
                tb.addClass('table');
                tb.append('<thead><tr><th>Name</th><th>Description</th><th>Action</th></tr></thead>')
                tb.append('<tbody>')
                $.each(response.collection, function(i, item) {
                    let tr = $('<tr>');
                    if(blinkId === item.id) {
                        tr.addClass('blink-it');
                    }
                    let trAction = '';
                    trAction+= `<a class="action-icon {{$id}}_play_current_recording_action" href="#" data-id="${item.id}" data-filename="${item.filename}"><i class="uil uil-play" data-bs-container=".tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Play/Pause"></i></a>`
                    @if($showUseRecordingAction ?? true)
                        trAction+= `<a class="action-icon {{$id}}_use_recording_action" href="#" data-route="{{ route('recordings.use', ['recording' => ':id', 'entity' => ':entity', 'entityid' => ':entityid']) }}" data-id="${item.id}" data-filename="${item.filename}" data-entity="{{ $entity }}" data-entityid="{{ $entityid ?? '' }}"><i class="mdi mdi-plus-box-outline" data-bs-container=".tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Use it"></i></a>`
                    @endif
                    trAction+= `<a class="action-icon {{$id}}_edit_recording_action" href="#" data-route="{{ route('recordings.show', ':id') }}" data-id="${item.id}"><i class="mdi mdi-lead-pencil" data-bs-container=".tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit"></i></a>`
                    trAction+= `<a class="action-icon {{$id}}_confirm_delete_recording_action" href="#" data-route="{{ route('recordings.destroy', ':id') }}" data-id="${item.id}"><i class="mdi mdi-delete" data-bs-container=".tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i></a>`
                    tr.attr('id', 'id' + item.id).attr('data-filename', item.filename).append(`<td>${item.name}</td><td>${item.description}</td><td><div class="tooltip-container-actions">${trAction}</div></td>`)
                    tb.append(tr)
                })
                tgt.html(tb);
                if (blinkId != null) {
                    $('#id' + blinkId)[0].scrollIntoView();
                }
                $('[data-bs-toggle="tooltip"]').tooltip();
            } else {
                tgt.html('<div class="text-center">No matching records found</div>');
            }
        });
    }

    function playCurrentRecording(id, filename) {
        var body = $('#{{ $id }}_manage_greeting_modal_body');
        var id = body.find(`#id${id}`);
        id.find('.action-icon').find('i').removeClass('uil-pause').addClass('uil-play')
        var audioElement = document.getElementById('{{ $id }}_audio_file');
        if (!audioElement.paused) {
            body.find('tr').find('.action-icon').find('i').removeClass('uil-pause').addClass('uil-play')
            audioElement.pause();
        } else {
            $('#{{ $id }}').val(filename);
            $('#{{ $id }}').trigger('change');
            $('#{{ $id }}_play_pause_button').click();
            id.find('.action-icon').find('i').removeClass('uil-play').addClass('uil-pause')
        }
    }

    function confirmDeleteRecordingAction(url, setting_id) {
        var dataObj = {};
        dataObj.url = url;
        dataObj.setting_id = setting_id;
        $('#{{ $id }}_confirmDeleteRecordingModal').data(dataObj).modal('show');
        // deleteSetting(setting_id);
    }

    document.addEventListener('DOMContentLoaded', function() {
    $('.delete-greeting-btn').click(function () {
        var confirmDeleteRecordingModal = $("#{{ $id }}_confirmDeleteRecordingModal");
        var setting_id = confirmDeleteRecordingModal.data("setting_id");
        confirmDeleteRecordingModal.modal('hide');
        var url = confirmDeleteRecordingModal.data("url");
        url = url.replace(':id', setting_id);
        $.ajax({
            type: 'POST',
            url: url,
            cache: false,
            data: {
                '_method': 'DELETE',
            }
        }).done(function(response) {
            if (response.error) {
                $.NotificationApp.send("Warning", response.message, "top-right", "#ff5b5b", "error");
            } else {
                $.NotificationApp.send("Success", response.message, "top-right", "#10c469", "success");
                $("#{{ $id }} option[value='" + response.filename + "']").remove();
                /*var newArray = [];
                let newData = $.grep($('#{{ $id }}').select2('data'), function (value) {
                            return value['id'] !== response.filename;
                        });
                        newData.forEach(function(data) {
                            newArray.push(+data.id);
                        });*/
                $("#{{ $id }}") /*.val(newArray)*/ .select2();
                $("#id" + setting_id).fadeOut("slow");
            }
        }).fail(function(jqXHR, testStatus, error) {
            printErrorMsg(error);
        });
    });
});

    function editRecordingAction(url, setting_id) {
        url = url.replace(':id', setting_id);
        $.ajax({
            type: 'GET',
            url: url,
            cache: false
        }).done(function(response) {
            $('#{{ $id }}_name').val(response.name);
            $('#{{ $id }}_description').val(response.description);
            $('#{{ $id }}_id').val(response.id);
            $('#{{ $id }}_editRecordingModal').modal('show');
        });
    }

    function useRecordingAction(url, setting_id, entity, entityid) {
        if(entityid ===  '') {
            entityid = $(`#${entity}_entity_id`).val()
        }
        url = url.replace(':id', setting_id);
        url = url.replace(':entity', entity);
        url = url.replace(':entityid', entityid);
        $.ajax({
            type: 'POST',
            url: url,
            cache: false,
            data: {
                '_method': 'PUT',
            }
        }).done(function(response) {
            if (response.error) {
                $.NotificationApp.send("Warning", response.message, "top-right", "#ff5b5b", "error");
            } else {
                $.NotificationApp.send("Success", response.message, "top-right", "#10c469", "success");
                $('#{{ $id }}').val(response.filename);
                $('#{{ $id }}').trigger('change');
                $('#{{ $id }}_manage_greeting_modal').modal('hide');
            }
        }).fail(function(jqXHR, testStatus, error) {
            printErrorMsg(error);
        });
    }
@if ($useScriptTag ?? true)
</script>
@endif
