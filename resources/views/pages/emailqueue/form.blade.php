@extends('layouts.app')

@section('content')
<livewire:email-queue-form :emailQueueUuid="$emailQueueUuid ?? null"/>
@endsection
