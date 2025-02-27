@if ($modalIsOpen)
    <!-- Standard modal -->
    <div id="myOffcanvas" class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <h3>Call Details</h3>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <h5 id="offcanvasRightLabel" class="mb-3">{{ $currentRecord->xml_cdr_uuid }}</h5>
            <table class="table table-sm table-centered mb-0">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sip Call ID</td>
                        <td>{{ $currentRecord->sip_call_id }}</td>
                    </tr>
                    <tr>
                        <td>Direction</td>
                        <td>{{ $currentRecord->direction }}</td>
                    </tr>
                    <tr>
                        <td>Caller Name</td>
                        <td>{{ $currentRecord->caller_id_name }}</td>
                    </tr>
                    <tr>
                        <td>Caller</td>
                        <td>{{ $currentRecord->caller_id_number }}</td>
                    </tr>
                    <tr>
                        <td>Caller Destination</td>
                        <td>{{ $currentRecord->caller_destination }}</td>
                    </tr>
                    <tr>
                        <td>Source Number</td>
                        <td>{{ $currentRecord->source_number }}</td>
                    </tr>
                    <tr>
                        <td>Destination Number</td>
                        <td>{{ $currentRecord->destination_number }}</td>
                    </tr>
                    <tr>
                        <td>Start Time</td>
                        <td>{{ $currentRecord->start_epoch }}</td>
                    </tr>
                    <tr>
                        <td>Answer Time</td>
                        <td>{{ $currentRecord->answer_epoch }}</td>
                    </tr>
                    <tr>
                        <td>End Time</td>
                        <td>{{ $currentRecord->end_epoch }}</td>
                    </tr>
                    <tr>
                        <td>Duration</td>
                        <td>{{ $currentRecord->duration }}</td>
                    </tr>
                    <tr>
                        <td>Talk Time</td>
                        <td>{{ $currentRecord->billsec }}</td>
                    </tr>
                    <tr>
                        <td>Wait Time</td>
                        <td>{{ $currentRecord->waitsec }}</td>
                    </tr>
                    <tr>
                        <td>Bridge</td>
                        <td>{{ $currentRecord->bridge_uuid }}</td>
                    </tr>
                    <tr>
                        <td>Read Codec</td>
                        <td>{{ $currentRecord->read_codec }}</td>
                    </tr>
                    <tr>
                        <td>Read Rate</td>
                        <td>{{ $currentRecord->read_rate }}</td>
                    </tr>
                    <tr>
                        <td>Write Codec</td>
                        <td>{{ $currentRecord->write_codec }}</td>
                    </tr>
                    <tr>
                        <td>Write Rate</td>
                        <td>{{ $currentRecord->write_rate }}</td>
                    </tr>

                    <tr>
                        <td>Leg</td>
                        <td>{{ $currentRecord->leg }}</td>
                    </tr>
                    <tr>
                        <td>Originating Leg</td>
                        <td>{{ $currentRecord->originating_leg_uuid }}</td>
                    </tr>
                    <tr>
                        <td>PDD</td>
                        <td>{{ $currentRecord->pdd_ms }}</td>
                    </tr>
                    <tr>
                        <td>RTP Audio in MOS</td>
                        <td>{{ $currentRecord->rtp_audio_in_mos }}</td>
                    </tr>
                    <tr>
                        <td>Last App</td>
                        <td>{{ $currentRecord->last_app }}</td>
                    </tr>
                    <tr>
                        <td>Last Arg</td>
                        <td>{{ $currentRecord->last_arg }}</td>
                    </tr>
                    <tr>
                        <td>Voicemail</td>
                        <td>{{ $currentRecord->voicemail_message }}</td>
                    </tr>
                    <tr>
                        <td>Missed Call</td>
                        <td>{{ $currentRecord->missed_call }}</td>
                    </tr>
                    <tr>
                        <td>Hangup Cause</td>
                        <td>{{ $currentRecord->hangup_cause }}</td>
                    </tr>
                    <tr>
                        <td>Hangup Cause Q850</td>
                        <td>{{ $currentRecord->hangup_cause_q850 }}</td>
                    </tr>
                    <tr>
                        <td>SIP Hangup Disposition</td>
                        <td>{{ $currentRecord->sip_hangup_disposition }}</td>
                    </tr>

                    <tr>
                        <td>Dialed Digits</td>
                        <td>{{ $currentRecord->digits_dialed }}</td>
                    </tr>

                    <tr>
                        <td>CC Queue ID</td>
                        <td>{{ $currentRecord->call_center_queue_uuid }}</td>
                    </tr>
                    <tr>
                        <td>CC Queue Name</td>
                        <td>{{ $currentRecord->cc_queue }}</td>
                    </tr>
                    <tr>
                        <td>CC Side</td>
                        <td>{{ $currentRecord->cc_side }}</td>
                    </tr>
                    <tr>
                        <td>CC Member</td>
                        <td>{{ $currentRecord->cc_member_uuid }}</td>
                    </tr>
                    <tr>
                        <td>CC Joined Time</td>
                        <td>{{ $currentRecord->cc_queue_joined_epoch }}</td>
                    </tr>
                    <tr>
                        <td>CC Member Session</td>
                        <td>{{ $currentRecord->cc_member_session_uuid }}</td>
                    </tr>
                    <tr>
                        <td>CC Agent ID</td>
                        <td>{{ $currentRecord->cc_agent_uuid }}</td>
                    </tr>
                    <tr>
                        <td>CC Agent</td>
                        <td>{{ $currentRecord->cc_agent }}</td>
                    </tr>
                    <tr>
                        <td>CC Agent Type</td>
                        <td>{{ $currentRecord->cc_agent_type }}</td>
                    </tr>
                    <tr>
                        <td>CC Agent Bridged</td>
                        <td>{{ $currentRecord->cc_agent_bridged }}</td>
                    </tr>
                    <tr>
                        <td>CC Queue Answer Time</td>
                        <td>{{ $currentRecord->cc_queue_answered_epoch }}</td>
                    </tr>
                    <tr>
                        <td>CC Queue End Time</td>
                        <td>{{ $currentRecord->cc_queue_terminated_epoch }}</td>
                    </tr>
                    <tr>
                        <td>CC Queue Cancel Time</td>
                        <td>{{ $currentRecord->cc_queue_canceled_epoch }}</td>
                    </tr>
                    <tr>
                        <td>CC Queue Cancel Reason</td>
                        <td>{{ $currentRecord->cc_cancel_reason }}</td>
                    </tr>
                    <tr>
                        <td>CC Cause</td>
                        <td>{{ $currentRecord->cc_cause }}</td>
                    </tr>



                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('open-module', event => {
            // var myModal = new bootstrap.Modal(document.getElementById('standard-modal'), {
            //     backdrop: true
            // });
            // myModal.show();

            var bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('myOffcanvas'));
            bsOffcanvas.show();
        });
    </script>
@endif
