@extends('layouts.app')

@section('content')
<livewire:dialplan-form :dialplan="$dialplan ?? null" :domains=$domains :types=$types :dialplan_default_context=$dialplan_default_context />
@endsection
