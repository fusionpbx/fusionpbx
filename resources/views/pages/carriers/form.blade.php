@extends('layouts.app')

@section('content')
<livewire:carrier-form :carrier="$carrier ?? null" :gateways="$gateways" />
@endsection
