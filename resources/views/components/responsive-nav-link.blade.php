    @props(['active'])

    @php
    $classes = ($active ?? false)
                ? 'mobile-nav-link active' // از کلاس های تعریف شده در app.css استفاده می کند
                : 'mobile-nav-link'; // از کلاس های تعریف شده در app.css استفاده می کند
    @endphp

    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
    