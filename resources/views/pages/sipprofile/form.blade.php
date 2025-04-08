@extends('layouts.app')

@section('content')
<livewire:sip-profile-form :sipProfile="$sipProfile ?? null" />
@endsection
