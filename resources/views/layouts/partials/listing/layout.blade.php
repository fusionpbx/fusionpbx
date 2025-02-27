@extends('layouts.app', ['page_title' => $pageTitle])

@section('content')
    <div class="container-fluid">
        <div class="row">
            @include('layouts.partials.subheader', [
                'title' => $pageTitle,
                'breadcrumbs' => $breadcrumbs ?? [],
            ])
        </div>

        @hasSection('widgets')
            <div class="row">
                @yield('widgets')
            </div>
        @endif

        @hasSection('cards')
            @yield('cards')
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            @hasSection('searchbar')
                                <div class="col-xl-8 mb-3">
                                    @yield('searchbar')
                                </div>
                            @endif
                            @hasSection('actionbar')
                                @sectionMissing('searchbar')
                                    <div class="col-xl-8 mb-3"></div>
                                @endif
                                <div class="text-xl-end col-xl-4 mb-3">
                                    @yield('actionbar')
                                </div>
                            @endif
                        </div>
                        @hasSection('pagination')
                            <div class="row">
                                @yield('pagination')
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-centered mb-0">
                                <thead class="table-light">@yield('table-head')</thead>
                                <tbody>@yield('table-body')</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @hasSection('includes')
        @yield('includes')
    @endif
@endsection
