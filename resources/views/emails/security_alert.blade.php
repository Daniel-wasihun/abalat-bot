<x-mail::message>
    # Security Alert: New Device Login Detected

    Hello {{ $name }},

    We detected a login to your account from a device we don't recognize.

    **Device Details:**
    * **Device:** {{ $device_name }}
    * **Location:** {{ $location }}
    * **Time:** {{ $time }}

    ---

    ### Is this you?

    If you just logged in, you can safely approve this device to prevent future alerts.

    <x-mail::button :url="$approve_url" color="success">
        Approve This Device
    </x-mail::button>

    ### Was this NOT you?

    If you don't recognize this activity, your account may be at risk. Please take immediate action:

    **Option 1: Terminate This Session**
    Revoke access for this specific device immediately.
    <x-mail::button :url="$terminate_url" color="error">
        Terminate That Session
    </x-mail::button>

    **Option 2: Terminate/Lock My Account**
    If you suspect your password has been stolen, you can temporarily disable your entire account until you contact an administrator.
    <x-mail::button :url="$lock_url" color="error">
        Terminate & Lock My Account
    </x-mail::button>

    ---

    For your security, you can also view all your active sessions and manage them directly in the **Devices Management** section of your profile.

    Thanks,<br>
    {{ config('app.name') }} Team
</x-mail::message>