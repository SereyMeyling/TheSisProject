@extends('adminlte::page')

@section('title', 'Dashboard')



@section('content')
@include('form.home.welcome')
@stop

@section('css')
<link rel="stylesheet" href="/css/custom.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Battambang:wght@100;300;400;700;900&display=swap" rel="stylesheet">
@stop

@section('js')
<script> console.log('Dashboard loaded!'); </script>
@stop
