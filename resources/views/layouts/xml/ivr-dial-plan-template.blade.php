<extension name="{{ $ivr->ivr_menu_name }}" continue="{{ $dialplan_continue }}" uuid="{{ $ivr->dialplan_uuid }}">
    <condition field="destination_number" expression="^{{ $ivr->ivr_menu_extension }}$">
        <action application="ring_ready" data="" />
        <action application="answer" data="" />
        <action application="sleep" data="1000" />
        <action application="set" data="hangup_after_bridge=true" />
        <action application="set" data="ringback={{ $ivr->ivr_menu_ringback }}" />
        <action application="set" data="transfer_ringback={{ $ivr->ivr_menu_ringback }}" />
        <action application="set" data="ivr_menu_uuid={{ $ivr->ivr_menu_uuid }}" />
        @if (!empty($ivr->ivr_menu_cid_prefix))
            <action application="set" data="caller_id_name={{ $ivr->ivr_menu_cid_prefix }}#${caller_id_name}" />
            <action application="set" data="effective_caller_id_name=${caller_id_name}" />
        @endif
        <action application="ivr" data="{{ $ivr->ivr_menu_uuid }}" />
        <action application="{{ $ivr->ivr_menu_exit_app }}" data="{{ $ivr->ivr_menu_exit_data }}" />
    </condition>
</extension>
