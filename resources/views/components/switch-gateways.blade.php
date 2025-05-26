<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }}>
    <option value=""></option>
    @foreach($options as $option)
		@if(!empty($option->label))
		<optgroup label="{!! $option->label !!}">
		@endif
        @foreach($option->values as $value)
            <option value="{{ $value->id }}" @selected($value->id == $selected)>
                {{ $value->name }}
            </option>
        @endforeach
		@if(!empty($option->label))
        </optgroup>
		@endif
    @endforeach
</select>
