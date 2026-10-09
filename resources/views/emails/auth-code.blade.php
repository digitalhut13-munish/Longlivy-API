Hello,

@if($purpose === 'password_reset')
Your password reset code is: {{ $code }}

It expires in 60 minutes and can only be used once.
@else
Your email verification code is: {{ $code }}

It expires in 30 minutes.
@endif

If you did not request this, you can ignore this email.