@extends('emails.layout')

@section('title', \App\Services\BackMessage::get('email.welcome_title'))

@section('content')
<h1 class="headline">{{ \App\Services\BackMessage::get('email.welcome_headline', ['name' => $user->name['en'] ?? $user->name]) }}</h1>

<p class="body-text">
    {{ \App\Services\BackMessage::get('email.welcome_body') }}
</p>

<div class="card">
    <div class="info-group">
        <div class="info-label">{{ \App\Services\BackMessage::get('email.email_address') }}</div>
        <div class="info-value">{{ $user->email }}</div>
    </div>

    <div class="info-group">
        <div class="info-label">{{ \App\Services\BackMessage::get('email.temp_password') }}</div>
        <div class="info-value">
            <span class="password-value">{{ $password }}</span>
        </div>
    </div>
</div>

<div class="btn-container" style="text-align: center;">
    <a href="{{ config('app.url') }}" class="btn">{{ \App\Services\BackMessage::get('email.enter_workspace') }}</a>
</div>

<p class="body-text" style="margin-top: 40px; font-size: 14px; text-align: center; color: #9ca3af;">
    {{ \App\Services\BackMessage::get('email.security_note') }}
</p>
@endsection