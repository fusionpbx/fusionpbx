@extends('layouts.app')

@section('content')
<livewire:extension-form :extensions="$extensions ?? null"/>
@endsection
