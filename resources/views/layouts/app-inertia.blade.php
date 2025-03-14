
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layouts.shared/title-meta', ['title' => $title ?? null])
    @yield('scss')

    @vite(['resources/scss/tailwind.scss'])

    @vite(['resources/js/vue.js'])


    @stack('head.end')
    @inertiaHead
</head>


@if (request()->is('login'))

    <body class="m-0 font-nunito text-gray-600 bg-gray-100">
        <div class="max-w-95 mx-auto">

            {{-- @auth --}}

                <div class=" px-3">
                    <div class="content">


                        @yield('content')
                        @inertia

                    </div>

                </div>
            {{-- @else
                @yield('content')

            @endauth --}}

        </div>

    </body>
@endif


@if ( !request()->is('login'))

    <body class="m-0 font-nunito text-gray-600 bg-gray-100">
        <div class="max-w-95 mx-auto">

            {{-- @auth --}}

                <div class=" px-3">
                    <div class="content">


                        @yield('content')
                        @inertia

                    </div>

                </div>
            {{-- @else
                @yield('content')

            @endauth --}}

        </div>

    </body>
@endif

</html>
