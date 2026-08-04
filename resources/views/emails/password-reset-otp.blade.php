@extends('emails.layout')

@section('title', \App\Services\BackMessage::get('email.reset_title'))

@section('content')
<h1 class="headline">{{ \App\Services\BackMessage::get('email.reset_headline') }}</h1>

<p class="body-text">
    {{ \App\Services\BackMessage::get('email.reset_body') }}
</p>

<div class="otp-box">
    {{ $otp }}
</div>

<p class="body-text" style="text-align: center; color: #64748b; font-size: 14px;">
    {{ \App\Services\BackMessage::get('email.otp_expiry', ['minutes' => 15]) }}
</p>

<div class="card" style="margin-top: 32px;">
    <p style="margin: 0; font-size: 14px; color: #475569;">
        {{ \App\Services\BackMessage::get('email.reset_warning') }}
    </p>
</div>
@endsection