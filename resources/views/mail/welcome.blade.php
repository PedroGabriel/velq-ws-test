<x-mail::message>
# Welcome, {{ $user->name }}

Thanks for signing up to the Velq guestbook demo.

Post a message and watch it appear live for everyone connected, with no page
refresh. Your post can include an image attachment, stored on object storage.

<x-mail::button :url="config('app.url')">
Open the guestbook
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
