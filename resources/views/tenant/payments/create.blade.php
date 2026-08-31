@extends('layouts.tenant', ['title' => 'Ajukan Pembayaran'])
@section('content')<livewire:tenant.payment-submission :invoice="$invoice" />@endsection
