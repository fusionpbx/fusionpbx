@extends('layouts.app')

@section('content')
<livewire:device-form :deviceUuid="$deviceUuid ?? null"/>
@endsection
