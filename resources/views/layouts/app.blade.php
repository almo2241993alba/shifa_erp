<x-pharmacy-layout title="Account profile">
    @if (isset($header))
        <div class="page-heading">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}
</x-pharmacy-layout>
