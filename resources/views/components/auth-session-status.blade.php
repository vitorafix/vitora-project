<?php // این خط ضروری نیست اما در برخی پروژه‌ها برای وضوح اضافه می‌شود ?>
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600 dark:text-green-400']) }}>
        {{ $status }}
    </div>
@endif
