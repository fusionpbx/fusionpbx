<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.shared/head')


</head>

<body class="loading" data-layout="topnav"
    data-layout-config='{"layoutBoxed":false,"darkMode":false,"showRightSidebarOnStart": false}'>
    @if (!empty($page))
        @inertia
    @endif
    <!-- Begin page -->
    <div id="app" class="wrapper">

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                @include('layouts.shared/horizontal-nav')

                @yield('content')

            </div>
            <!-- content -->

            @include('layouts.shared/footer')

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->


    </div>
    <!-- END wrapper -->

    @include('layouts.shared/right-sidebar')

    @include('layouts.shared.footer-script')

</body>

</html>
