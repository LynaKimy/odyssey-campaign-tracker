@props(['active' => false])

<a {{ $attributes->merge([
    'class' => 'nav-link ' . ($active ? 'nav-link-active' : ''),
]) }}>
    {{ $slot }}
</a>
