<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
{{--    <meta name="csrf-token" content="{{ csrf_token() }}">--}}

    <title>Log</title>

    <!-- Fonts -->
{{--    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">--}}
    {{--    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">--}}

{{--    <link rel="stylesheet" href="{{ asset('css/app.css') }}">--}}
    @yield('template_linked_css')
</head>
<body>
<div id="app">
    @yield('content')
</div>

{{--<script src="{{ asset('js/app.js') }}"></script>--}}
@yield('footer_scripts')
</body>
</html>
