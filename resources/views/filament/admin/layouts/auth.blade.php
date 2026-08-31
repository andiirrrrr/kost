<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $livewire->getTitle() }} — {{ $livewire->getBusinessName() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
</head>
<body class="min-h-screen bg-[#f9f9f7] text-[#1a1c1b] antialiased">
    {{ $slot }}
    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>
