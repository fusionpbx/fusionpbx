@extends('layouts.app')

@section('content')
<livewire:phrase-form :phrase="$phrase ?? null" :domains="$domains" />
@endsection
