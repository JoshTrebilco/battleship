<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Battleship</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Quantico:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-linear-to-br from-slate-800 to-slate-900">
        <div class="min-h-screen flex flex-col items-center justify-center bg-linear-to-b from-slate-800 via-slate-700 to-slate-900 w-full">
            {{ $slot }}
        </div>
    </body>
</html>
