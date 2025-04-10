<tr class="repeater-item">
	<td>
		<select class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_tag]">
			<option value=""></option>
			<option value="condition" {{ old('dialplan_detail_tag', $detail->dialplan_detail_tag ?? '') === 'condition' ? 'selected' : '' }}>Condition</option>
			<option value="regex" {{ old('dialplan_detail_tag', $detail->dialplan_detail_tag ?? '') === 'regex' ? 'selected' : '' }}>Regular Expression</option>
			<option value="action" {{ old('dialplan_detail_tag', $detail->dialplan_detail_tag ?? '') === 'action' ? 'selected' : '' }}>Action</option>
			<option value="anti-action" {{ old('dialplan_detail_tag', $detail->dialplan_detail_tag ?? '') === 'anti-action' ? 'selected' : '' }}>Anti-Action</option>
		</select>
	</td>
	<td>
		<input list="type_list" class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_type]" value="{{ old('dialplan_detail_type', $detail->dialplan_detail_type ?? '') }}">
		<datalist id="type_list">
			@foreach($types as $type)
				<option value="{{ $type['key'] }}">{{ $type['value'] }}</option>
			@endforeach
		</datalist>
	</td>
	<td>
		<input type="text" class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_data]" value="{{ old('dialplan_detail_data', $detail->dialplan_detail_data ?? '') }}">
	</td>
	<td>
		<select class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_break]">
			<option value=""></option>
			<option value="on-true" {{ old('dialplan_detail_break', $detail->dialplan_detail_break ?? '') === 'on-true' ? 'selected' : '' }}>On True</option>
			<option value="on-false" {{ old('dialplan_detail_break', $detail->dialplan_detail_break ?? '') === 'on-false' ? 'selected' : '' }}>On False</option>
			<option value="always" {{ old('dialplan_detail_break', $detail->dialplan_detail_break ?? '') === 'always' ? 'selected' : '' }}>Always</option>
			<option value="never" {{ old('dialplan_detail_break', $detail->dialplan_detail_break ?? '') === 'never' ? 'selected' : '' }}>Never</option>
		</select>
	</td>
	<td>
		<select class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_inline]>
			<option value=""></option>
			<option value="true" {{ old('dialplan_detail_inline', $detail->dialplan_detail_inline ?? '') === 'true' ? 'selected' : '' }}>True</option>
			<option value="false" {{ old('dialplan_detail_inline', $detail->dialplan_detail_inline ?? '') === 'false' ? 'selected' : '' }}>False</option>
		</select>
	</td>
	<td>
		<input type="number" class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_group]" value="{{ old('dialplan_detail_group', $detail->dialplan_detail_group ?? '') }}">
	</td>
	<td>
		<input type="number" step="5" min="0" max="100" class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_order]" value="{{ old('dialplan_detail_order', $detail->dialplan_detail_order ?? '') }}">
	</td>
	<td>
		<select class="form-control" name="dialplan_details[{{$index}}][dialplan_detail_enabled]">
			<option value=""></option>
			<option value="true" {{ old('dialplan_detail_enabled', $detail->dialplan_detail_enabled ?? '') === 'true' ? 'selected' : '' }}>True</option>
			<option value="false" {{ old('dialplan_detail_enabled', $detail->dialplan_detail_enabled ?? '') === 'false' ? 'selected' : '' }}>False</option>
		</select>
	</td>
	<td>
		<button class="btn btn-outline-secondary form-control repeater-remove" type="button">
			<i class="fas fa-trash"></i>
		</button>
	</td>
</tr>
