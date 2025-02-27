<extension name="{{ $ring_group->ring_group_name }}" continue="" uuid="{{ $ring_group->dialplan_uuid }}">
	<condition field="destination_number" expression="^{{ $ring_group->ring_group_extension }}$">
		<action application="ring_ready" data=""/>
	    <action application="set" data="ring_group_uuid={{ $ring_group->ring_group_uuid }}"/>
        <action application="lua" data="app.lua ring_groups"/>
    </condition>
</extension>