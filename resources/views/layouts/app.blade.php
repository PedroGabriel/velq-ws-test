<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Velq Guestbook')</title>

    {{--
        Server-render the LIVE Pusher app key into the page. Velq injects the key
        at runtime (via /bindings), after the Vite build, so it cannot be baked
        into the JS bundle. The Echo client (resources/js/echo.js) reads this.
        Pure Laravel config; nothing Velq-specific.
    --}}
    <script>
        window.Velq = {
            broadcasting: {
                key: @json(config('broadcasting.connections.pusher.key')),
            },
        };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto max-w-2xl px-4 py-10">
        @yield('content')
    </div>
</body>
</html>
