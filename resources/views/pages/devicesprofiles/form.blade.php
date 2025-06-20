@extends('layouts.app')

@section('content')
<livewire:device-profile-form :deviceProfileUuid="$deviceProfileUuid ?? null"/>
@endsection
