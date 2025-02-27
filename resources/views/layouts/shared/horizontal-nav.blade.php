

<div class="topnav shadow-sm" style="background: linear-gradient(180deg,#6379c3,#546ee5)">
    <div class="container-fluid">
        <nav class="navbar navbar-dark navbar-expand-lg topnav-menu">

            <!-- LOGO -->
            <a href="/dashboard" class="topnav-logo me-3">
                <span class="topnav-logo-sm">
                    <img src="{{asset('/storage/logo.png')}}" alt="" height="40">
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content" aria-controls="topnav-menu-content" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topnav-menu-content">

                <ul class="navbar-nav me-auto">
                    @foreach (Session::get('menu') as $menu)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" href="@if ($menu->menu_item_link != '') {{ $menu->menu_item_link }} @else '' @endif" 
                            id="topnav-dashboards" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="uil-dashboard me-1"></i>{{ $menu->menu_item_title }} 
                                <i class="mdi mdi-chevron-down d-none d-sm-inline-block align-middle"></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="topnav-dashboards">
                                @foreach ($menu->child_menu as $child_menu)
                                <a href="{{ $child_menu->menu_item_link }}" class="dropdown-item">{{ $child_menu->menu_item_title }}</a>
                                @endforeach
                            </div>
                        </li>
                    @endforeach

                </ul>
            {{-- </div> --}}

            {{-- <div class="float-end" id=""> --}}
                {{-- <ul class="navbar-nav"> --}}
                    <span class="navbar-nav">
                        <span class="nav-item text-nowrap" >
                        <a class="nav-link " @if (Session::get("domain_select")) data-bs-toggle="modal" data-bs-target="#right-modal" @endif href="javascript: void(0);">
                            <i class="uil uil-globe  me-1"></i>{{ Session::get("domain_name") }}
                        </a>
                        </span>
                    </span>
                {{-- </ul> --}}


            </div>
        </nav>
    </div>
</div>