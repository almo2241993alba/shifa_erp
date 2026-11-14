<x-pharmacy-layout title="الملف الشخصي">
    @if (isset($header))
        <div class="page-heading">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}
</x-pharmacy-layout>
