@extends('layouts.app')

@push('before-scripts')
    @vite('node_modules/jquery/dist/jquery.min.js')
    @vite('node_modules/select2/dist/js/select2.min.js')
    @vite('resources/js/select2-contacts-form.js')
@endpush

@section('content')
<livewire:contact-form :contact="$contact ?? null" />
@endsection
