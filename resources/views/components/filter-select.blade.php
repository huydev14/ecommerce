@props(['id', 'label', 'placeholder' => 'Choose an option'])

<select id="{{ $id }}" class="form-select tw-w-full">
    <option value="">{{ $placeholder }}</option>
    {{ $slot }}
</select>
