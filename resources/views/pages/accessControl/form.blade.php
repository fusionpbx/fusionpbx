@extends('layouts.app')

@section('content')
    <livewire:access-control-form :accessControlUuid="$accessControlUuid ?? null" />
@endsection