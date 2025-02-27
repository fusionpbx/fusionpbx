<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.shared/title-meta', ['title' => $title ?? null])
        @include('layouts.shared/head-css', ['mode' => $mode ?? '', 'demo' => $demo ?? ''])
        @vite(['resources/js/app.js','resources/js/hyper-head.js', 'resources/js/hyper-config.js'])
    </head>

    <body class="loading" data-layout="topnav" data-layout-config='{"layoutBoxed":false,"darkMode":false,"showRightSidebarOnStart": false}' >
        <!-- Begin page -->
        <div class="wrapper">

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">
                    
                    
                    @yield('content')

                </div>
                <!-- content -->


            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->


        @include('layouts.shared/footer-scripts')
        @vite(['resources/js/hyper-main.js'])
    </body>
</html>