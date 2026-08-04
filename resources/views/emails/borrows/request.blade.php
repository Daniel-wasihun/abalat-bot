@component('mail::message')
# {{ \App\Services\BackMessage::get('borrow.email_confirmation_title') }}

{{ \App\Services\BackMessage::get('borrow.email_hello') }} {{ is_array($borrow->borrower->name) ? ($borrow->borrower->name[\App\Services\BackMessage::current()] ?? reset($borrow->borrower->name)) : $borrow->borrower->name }},

{{ \App\Services\BackMessage::get('borrow.email_confirm_request_for') }}

<div style="padding: 15px; background-color: #f3f4f6; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="margin-top: 0; color: #111827;">{{ $borrow->book->title[\App\Services\BackMessage::current()] ?? ($borrow->book->title['en'] ?? '') }}</h3>
    <p style="margin: 5px 0; color: #4b5563;"><strong>{{ \App\Services\BackMessage::get('borrow.email_author') }}:</strong> {{ $borrow->book->author ?? 'Unknown' }}</p>
    <p style="margin: 5px 0; color: #4b5563;"><strong>{{ \App\Services\BackMessage::get('borrow.email_type') }}:</strong> {{ \App\Services\BackMessage::get($borrow->borrow_type === 'home' ? 'borrow.dorm_borrowing' : 'borrow.in_library_use') }}</p>
    <hr style="border: 0; border-top: 1px solid #d1d5db; margin: 10px 0;">
    <p style="margin: 5px 0; color: #4b5563;"><strong>{{ \App\Services\BackMessage::get('borrow.email_start_date') }}:</strong> {{ \Carbon\Carbon::parse($borrow->borrow_date)->format('M d, Y') }}</p>
    <p style="margin: 5px 0; color: #4b5563;"><strong>{{ \App\Services\BackMessage::get('borrow.email_return_date') }}:</strong> {{ \Carbon\Carbon::parse($borrow->due_date)->format('M d, Y') }}</p>
</div>

@component('mail::button', ['url' => 'http://localhost:5173/borrow/confirm/' . $borrow->confirmation_token, 'color' => 'success'])
{{ \App\Services\BackMessage::get('borrow.email_confirm_button') }}
@endcomponent

{{ \App\Services\BackMessage::get('borrow.email_not_requested_warning') }}

@component('mail::button', ['url' => 'http://localhost:5173/borrow/cancel/' . $borrow->confirmation_token, 'color' => 'error'])
{{ \App\Services\BackMessage::get('borrow.email_decline_button') }}
@endcomponent

{{ \App\Services\BackMessage::get('borrow.email_regards') }},
<br>
{{ \App\Services\BackMessage::get('borrow.email_library_team') }}
@endcomponent