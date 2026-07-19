@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
@stack('css')
@yield('css')
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
<div class="wrapper">

    {{-- Preloader Animation (fullscreen mode) --}}
    @if($preloaderHelper->isPreloaderEnabled())
        @include('adminlte::partials.common.preloader')
    @endif

    {{-- Top Navbar --}}
    @if($layoutHelper->isLayoutTopnavEnabled())
        @include('adminlte::partials.navbar.navbar-layout-topnav')
    @else
        @include('adminlte::partials.navbar.navbar')
    @endif

    {{-- Left Main Sidebar --}}
    @if(!$layoutHelper->isLayoutTopnavEnabled())
        @include('adminlte::partials.sidebar.left-sidebar')
    @endif

    {{-- Content Wrapper --}}
    @empty($iFrameEnabled)
        @include('adminlte::partials.cwrapper.cwrapper-default')
    @else
        @include('adminlte::partials.cwrapper.cwrapper-iframe')
    @endempty

    {{-- Footer --}}
    @hasSection('footer')
        @include('adminlte::partials.footer.footer')
    @endif

    {{-- Right Control Sidebar --}}
    @if($layoutHelper->isRightSidebarEnabled())
        @include('adminlte::partials.sidebar.right-sidebar')
    @endif

</div>
@stop

@section('adminlte_js')
@stack('js')
@yield('js')
@stop
@section('js')
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000
        };

        @if(session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if(session('error'))
            toastr.error(@json(session('error')));
        @endif

        @if(session('warning'))
            toastr.warning(@json(session('warning')));
        @endif

        @if(session('info'))
            toastr.info(@json(session('info')));
        @endif
    </script>
    @stack('js')
@endsection