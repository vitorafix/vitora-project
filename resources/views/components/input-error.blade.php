@props(['messages'])

{{-- Check if there are any messages to display --}}
@if ($messages)
    {{-- Merge attributes for the unordered list, applying default Tailwind classes --}}
    <ul {{ $attributes->merge(['class' => 'text-red-600 text-sm mt-1']) }}>
        {{-- Loop through each message and display it as a list item --}}
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
