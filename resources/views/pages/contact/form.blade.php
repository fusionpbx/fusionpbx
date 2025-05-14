@extends('layouts.app')

@section('content')
<livewire:contact-form :contact="$contact ?? null" />
@endsection
