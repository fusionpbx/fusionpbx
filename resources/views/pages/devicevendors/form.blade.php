@extends('layouts.app')

@section('content')
<livewire:device-vendor-form :vendorUuid="$vendorUuid ?? null"/>
@endsection