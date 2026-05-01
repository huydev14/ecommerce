@props(['placeholder' => 'Choose an option'])

<select {{ $attributes->merge(['class' => 'form-select tw-w-full']) }}>
    <option value="">{{ $placeholder }}</option>
    {{ $slot }}
</select>
