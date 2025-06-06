@extends('layouts.app')

@push('before-scripts')
    @vite('resources/js/select2-contacts-form.js')
@endpush

@section('content')
<livewire:contact-form :contact="$contact ?? null" />
@endsection
