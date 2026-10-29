<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? 'Cinema — легальный каталог фильмов и сериалов с официальными вариантами просмотра.' }}">
    <meta name="theme-color" content="#07080c">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:type" content="{{ isset($title) ? 'video.movie' : 'website' }}">
    <meta property="og:title" content="{{ $pageTitle ?? 'Cinema — фильмы и сериалы' }}">
    <meta property="og:description" content="{{ $description ?? 'Находите кино, сохраняйте избранное и выбирайте легальный способ просмотра.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @isset($image)<meta property="og:image" content="{{ $image }}">@endisset
    <title>{{ $pageTitle ?? 'Cinema — фильмы и сериалы' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <noscript>Для работы Cinema необходимо включить JavaScript.</noscript>
    <div id="app"></div>
</body>
</html>
