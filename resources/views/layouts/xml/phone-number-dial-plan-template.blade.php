<extension name="{{ $phone_number->destination_number }}" continue="{{ $dialplan_continue }}"
    uuid="{{ $phone_number->dialplan_uuid }}">
    @if (!empty($phone_number->destination_conditions))
        @php
            $destination_conditions = json_decode($phone_number->destination_conditions, true);
        @endphp
        @foreach ($destination_conditions as $row)
            <condition regex="all" break="never">
                <regex field="{{ $destination_condition_field }}"
                    expression="{{ $phone_number->destination_number_regex }}" />
                <regex field="{{ $row['condition_field'] }}" expression="^\+?1?{{ $row['condition_expression'] }}" />
                <action application="export" data="call_direction=inbound" inline="true" />
                <action application="set" data="domain_uuid={{ $phone_number->domain_uuid }}" inline="true" />
                <action application="set" data="domain_name={{ $domain_name }}" inline="true" />
                <action application="{{ $row['condition_app'] }}" data="{{ $row['condition_data'] }}" />
            </condition>
        @endforeach
    @endif
    <condition field="{{ $destination_condition_field }}" expression="{{ $phone_number->destination_number_regex }}">
        <action application="export" data="call_direction=inbound" inline="true" />
        <action application="set" data="domain_uuid={{ $phone_number->domain_uuid }}" inline="true" />
        <action application="set" data="domain_name={{ $domain_name }}" inline="true" />
        @if (!empty($destination_hold_music))
            <action application="export" data="hold_music={{ $phone_number->destination_hold_music }}"
                inline="true" />
        @endif
        @if ($phone_number->destination_record == 'true')
            <action application="set"
                data="record_path=${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}"
                inline="true" />
            <action application="set" data="record_name=${uuid}.${record_ext}" inline="true" />
            <action application="set" data="record_append=true" inline="true" />
            <action application="set" data="record_in_progress=true" inline="true" />
            <action application="set" data="recording_follow_transfer=true" inline="true" />
            <action application="record_session" data="${record_path}/${record_name}" inline="false" />
        @endif
        @if ($phone_number->destination_cid_name_prefix && !empty($phone_number->destination_cid_name_prefix))
            <action application="set"
                data="effective_caller_id_name={{ $phone_number->destination_cid_name_prefix }}#${caller_id_name}"
                inline="false" />
        @endif
        @if ($phone_number->destination_distinctive_ring && !empty($phone_number->destination_distinctive_ring))
            <action application="export" data="sip_h_Alert-Info={{ $phone_number->destination_distinctive_ring }}"
                inline="true" />
        @endif
        @if ($phone_number->destination_accountcode && !empty($phone_number->destination_accountcode))
            <action application="export" data="accountcode={{ $phone_number->destination_accountcode }}"
                inline="true" />
        @endif
        @if ($fax_data)
            <action application="set" data="tone_detect_hits=1" inline="true" />
            <action application="set"
                data="execute_on_tone_detect=transfer {{ $fax_data->fax_extension }} XML ${domain_name}"
                inline="true" />
            <action application="tone_detect" data="fax 1100 r +3000" />
        @endif

        @if ($phone_number->destination_type_fax == 1)
            <action application="export" data="fax_enable_t38_request=true" />
            <action application="export" data="fax_enable_t38=true" />
            <action application="export" data="fax_use_ecm=true" />
            <action application="set" data="inbound-proxy-media=true" />
        @endif

        @if (!empty($phone_number->destination_actions))
            @php
                $destination_actions = json_decode($phone_number->destination_actions, true);
            @endphp
            @foreach ($destination_actions as $row)
                <action application="{{ $row['destination_app'] }}" data="{{ $row['destination_data'] }}" />
            @endforeach
        @endif
    </condition>
</extension>
