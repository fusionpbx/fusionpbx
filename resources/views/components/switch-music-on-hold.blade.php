<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }}>
    <option value=""></option>
    @foreach($options as $option)
        <optgroup label="{{ $option->label }}">
        @foreach($option->values as $value)
            <option value="{{ $value->id }}" @selected($value->id == $selected)>
                {{ $value->name }}
            </option>
        @endforeach
        </optgroup>
    @endforeach
</select>
