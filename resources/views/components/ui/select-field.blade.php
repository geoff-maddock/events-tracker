@props([
    'name',
    'options' => [],
    'selected' => null,
])
@php
    // Native replacement for Form::select. Renders a <select> with one <option>
    // per $options entry (value => label), marking $selected (single value or
    // array) as selected. Any extra attributes (id, class, multiple, data-*)
    // pass straight through so JS hooks (select2, .auto-submit) are preserved.
    $selectedValues = array_map('strval', is_array($selected) ? $selected : (is_null($selected) ? [] : [$selected]));
@endphp
<select name="{{ $name }}" {{ $attributes }}>
    @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>{{ $optionLabel }}</option>
    @endforeach
</select>
