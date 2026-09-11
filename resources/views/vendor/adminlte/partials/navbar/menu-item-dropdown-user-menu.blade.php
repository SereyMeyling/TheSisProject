@php(
    $logout_url = View::getSection('logout_url')
        ?? config('adminlte.logout_url', 'logout')
)

@php(
    $profile_url = View::getSection('profile_url')
        ?? config('adminlte.profile_url', 'logout')
)

@if (config('adminlte.usermenu_profile_url', false))
@php(
    $profile_url = Auth::user()->adminlte_profile_url()
)
@endif

@if (config('adminlte.use_route_url', false))

@php(
    $profile_url = $profile_url
        ? route($profile_url)
        : ''
)

@php(
        $logout_url = $logout_url
            ? route($logout_url)
            : ''
    )

@else

@php(
    $profile_url = $profile_url
        ? url($profile_url)
        : ''
)

@php(
    $logout_url = $logout_url
        ? url($logout_url)
        : ''
)

@endif


<li class="nav-item dropdown user-menu">

    {{-- =========================================================
    USER MENU TOGGLER
    ========================================================== --}}

    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown">

        {{-- Notification --}}
        <span class="mr-3 position-relative">

            <i class="fas fa-bell" style="font-size: 18px;">
            </i>

            <span class="badge badge-danger navbar-badge" style="font-size: 9px;">
                3
            </span>

        </span>


        {{-- User Information --}}
        <span class="d-flex flex-column mr-2" style="line-height: 1.2;">

            {{-- User Name --}}
            <span id="navbarUserName" style="font-size: 14px; font-weight: 600;">

                {{ Auth::user()->name }}

            </span>


            {{-- Department --}}
            <small id="navbarDepartment" class="text-muted" style="font-size: 11px;">

                {{ Auth::user()->department->department_name ?? 'No Department' }}

            </small>

        </span>


        {{-- Navbar Profile Image --}}
        @if(config('adminlte.usermenu_image'))

            <img id="navbarUserAvatar" src="{{ Auth::user()->adminlte_image() }}" class="user-image img-circle elevation-2"
                alt="{{ Auth::user()->name }}" style="
                            width: 35px;
                            height: 35px;
                            object-fit: cover;
                         ">

        @endif

    </a>


    {{-- =========================================================
    USER MENU DROPDOWN
    ========================================================== --}}

    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">


        {{-- User Menu Header --}}
        @if(
                    !View::hasSection('usermenu_header')
                    && config('adminlte.usermenu_header')
                )

                <li class="user-header
                                {{ config(
                'adminlte.usermenu_header_class',
                'bg-primary'
            ) }}
                                @if(!config('adminlte.usermenu_image'))
                                    h-auto
                                @endif
                            ">

                    {{-- Dropdown Profile Image --}}
                    @if(config('adminlte.usermenu_image'))

                        <img id="dropdownUserAvatar" src="{{ Auth::user()->adminlte_image() }}" class="img-circle elevation-2"
                            alt="{{ Auth::user()->name }}" style="
                                                    width: 90px;
                                                    height: 90px;
                                                    object-fit: cover;
                                                 ">

                    @endif


                    <p class="
                                    @if(!config('adminlte.usermenu_image'))
                                        mt-0
                                    @endif
                                ">

                        <span id="dropdownUserName">
                            {{ Auth::user()->name }}
                        </span>


                        @if(config('adminlte.usermenu_desc'))

                            <small id="dropdownUserDepartment">

                                {{ Auth::user()->adminlte_desc() }}

                            </small>

                        @endif

                    </p>

                </li>

        @else

            @yield('usermenu_header')

        @endif


        {{-- Configured User Menu Links --}}
        @each(
            'adminlte::partials.navbar.dropdown-item',
            $adminlte->menu("navbar-user"),
            'item'
        )


        {{-- User Menu Body --}}
        @hasSection('usermenu_body')

            <li class="user-body">

                @yield('usermenu_body')

            </li>

        @endif


        {{-- =========================================================
        USER MENU FOOTER
        ========================================================== --}}

        <li class="user-footer">

            @if($profile_url)

                <a href="{{ $profile_url }}" class="nav-link btn btn-default btn-flat d-inline-block">

                    <i class="fa fa-fw fa-user text-lightblue"></i>

                    {{ __('adminlte::menu.profile') }}

                </a>

            @endif


            <a class="
                    btn btn-default
                    btn-flat
                    float-right
                    @if(!$profile_url)
                        btn-block
                    @endif
                " href="#" onclick="
                   event.preventDefault();
                   document.getElementById('logout-form').submit();
               ">

                <i class="fa fa-fw fa-power-off text-red"></i>

                {{ __('adminlte::adminlte.log_out') }}

            </a>


            {{-- Logout Form --}}
            <form id="logout-form" action="{{ $logout_url }}" method="POST" style="display: none;">

                @if(config('adminlte.logout_method'))

                                {{ method_field(
                        config('adminlte.logout_method')
                    ) }}

                @endif

                {{ csrf_field() }}

            </form>

        </li>

    </ul>

</li>
