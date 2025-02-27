<!-- ========== Horizontal Menu Start ========== -->
<div class="topnav" style="background: linear-gradient(180deg,#6379c3,#546ee5)">
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg">
            <div class="collapse navbar-collapse" id="topnav-menu-content">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-dashboards" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="uil-dashboard"></i>Dashboards
                            <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-dashboards">
                            <a href="{{route('second', ['dashboards', 'analytics'])}}"
                               class="dropdown-item">Analytics</a>
                            <a href="{{route('any', 'index')}}" class="dropdown-item">Ecommerce</a>
                            <a href="{{route('second', ['dashboards', 'projects'])}}" class="dropdown-item">Projects</a>
                            <a href="{{route('second', ['dashboards', 'crm'])}}" class="dropdown-item">CRM</a>
                            <a href="{{route('second', ['dashboards', 'wallet'])}}" class="dropdown-item">E-Wallet</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-apps" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="uil-apps"></i>Apps
                            <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-apps">
                            <a href="{{route('second', ['apps', 'calendar'])}}" class="dropdown-item">Calendar</a>
                            <a href="{{route('second', ['apps', 'chat'])}}" class="dropdown-item">Chat</a>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-crm"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    CRM
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-crm">
                                    <a href="{{route('second', ['crm', 'projects'])}}" class="dropdown-item">Project</a>
                                    <a href="{{route('second', ['crm', 'orders-list'])}}" class="dropdown-item">Orders
                                        List</a>
                                    <a href="{{route('second', ['crm', 'clients'])}}" class="dropdown-item">Clients</a>
                                    <a href="{{route('second', ['crm', 'management'])}}" class="dropdown-item">Management</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-ecommerce"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Ecommerce
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ecommerce">
                                    <a href="{{route('second', ['ecommerce', 'products'])}}" class="dropdown-item">Products</a>
                                    <a href="{{route('second', ['ecommerce', 'products-details'])}}"
                                       class="dropdown-item">Products Details</a>
                                    <a href="{{route('second', ['ecommerce', 'orders'])}}"
                                       class="dropdown-item">Orders</a>
                                    <a href="{{route('second', ['ecommerce', 'orders-details'])}}"
                                       class="dropdown-item">Order Details</a>
                                    <a href="{{route('second', ['ecommerce', 'customers'])}}" class="dropdown-item">Customers</a>
                                    <a href="{{route('second', ['ecommerce', 'shopping-cart'])}}" class="dropdown-item">Shopping
                                        Cart</a>
                                    <a href="{{route('second', ['ecommerce' , 'checkout']) }}" class="dropdown-item">Checkout</a>
                                    <a href="{{route('second', ['ecommerce', 'sellers'])}}" class="dropdown-item">Sellers</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-email"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Email
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-email">
                                    <a href="{{route('second', ['email', 'inbox'])}}" class="dropdown-item">Inbox</a>
                                    <a href="{{route('second', ['email', 'read'])}}" class="dropdown-item">Read
                                        Email</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-project"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Projects
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-project">
                                    <a href="{{route('second', ['projects', 'list'])}}" class="dropdown-item">List</a>
                                    <a href="{{route('second', ['projects', 'details'])}}"
                                       class="dropdown-item">Details</a>
                                    <a href="{{route('second', ['projects', 'gantt'])}}" class="dropdown-item">Gantt</a>
                                    <a href="{{ route('second', ['projects', 'add']) }}" class="dropdown-item">Create
                                        Project</a>
                                </div>
                            </div>
                            <a href="{{ route('second', ['apps', 'social-feed']) }}" class="dropdown-item">Social
                                Feed</a>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-tasks"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Tasks
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-tasks">
                                    <a href="{{ route('second', ['tasks', 'tasks']) }}" class="dropdown-item">List</a>
                                    <a href="{{ route('second', ['tasks', 'details']) }}"
                                       class="dropdown-item">Details</a>
                                    <a href="{{ route('second', ['tasks', 'kanban']) }}" class="dropdown-item">Kanban
                                        Board</a>
                                </div>
                            </div>
                            <a href="{{ route('second', ['apps', 'file-manager']) }}" class="dropdown-item">File
                                Manager</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-pages" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="uil-copy-alt"></i>Pages
                            <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-pages">
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-auth"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Authenitication
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-auth">
                                    <a href="{{ route('second', ['auth', 'login']) }}" class="dropdown-item">Login</a>
                                    <a href="{{ route('second', ['auth', 'login-2']) }}" class="dropdown-item">Login
                                        2</a>
                                    <a href="{{ route('second', ['auth', 'register']) }}"
                                       class="dropdown-item">Register</a>
                                    <a href="{{ route('second', ['auth', 'register-2']) }}" class="dropdown-item">Register
                                        2</a>
                                    <a href="{{ route('second', ['auth', 'logout']) }}" class="dropdown-item">Logout</a>
                                    <a href="{{ route('second', ['auth', 'logout-2']) }}" class="dropdown-item">Logout
                                        2</a>
                                    <a href="{{ route('second', ['auth', 'recoverpw']) }}" class="dropdown-item">Recover
                                        Password</a>
                                    <a href="{{ route('second', ['auth', 'recoverpw-2']) }}" class="dropdown-item">Recover
                                        Password 2</a>
                                    <a href="{{ route('second', ['auth', 'lock-screen']) }}" class="dropdown-item">Lock
                                        Screen</a>
                                    <a href="{{ route('second', ['auth', 'lock-screen-2']) }}" class="dropdown-item">Lock
                                        Screen 2</a>
                                    <a href="{{ route('second', ['auth', 'confirm-mail']) }}" class="dropdown-item">Confirm
                                        Mail</a>
                                    <a href="{{ route('second', ['auth', 'confirm-mail-2']) }}" class="dropdown-item">Confirm
                                        Mail 2</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-error"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Error
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-error">
                                    <a href="{{ route('second', ['error', '404']) }}" class="dropdown-item">Error
                                        404</a>
                                    <a href="{{ route('second', ['error', '404-alt']) }}" class="dropdown-item">Error
                                        404-alt</a>
                                    <a href="{{ route('second', ['error', '500']) }}" class="dropdown-item">Error
                                        500</a>
                                </div>
                            </div>
                            <a href="{{ route('second', ['pages', 'starter']) }}" class="dropdown-item">Starter Page</a>
                            <a href="{{ route('second', ['pages', 'preloader']) }}" class="dropdown-item">With
                                Preloader</a>
                            <a href="{{ route('second', ['pages', 'profile']) }}" class="dropdown-item">Profile</a>
                            <a href="{{ route('second', ['pages', 'profile-2']) }}" class="dropdown-item">Profile 2</a>
                            <a href="{{ route('second', ['pages', 'invoice']) }}" class="dropdown-item">Invoice</a>
                            <a href="{{ route('second', ['pages', 'faq']) }}" class="dropdown-item">FAQ</a>
                            <a href="{{ route('second', ['pages', 'pricing']) }}" class="dropdown-item">Pricing</a>
                            <a href="{{ route('second', ['pages', 'maintenance']) }}"
                               class="dropdown-item">Maintenance</a>
                            <a href="{{ route('second', ['pages', 'timeline']) }}" class="dropdown-item">Timeline</a>
                            <a href="{{ route('any', 'landing') }}" class="dropdown-item">Landing</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-components" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="uil-package"></i>Components
                            <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-components">
                            <a href="{{ route('any', 'widgets') }}" class="dropdown-item">Widgets</a>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-ui-kit"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Base UI 1
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ui-kit">
                                    <a href="{{ route('second', ['ui', 'accordions']) }}" class="dropdown-item">Accordions</a>
                                    <a href="{{ route('second', ['ui', 'alerts']) }}" class="dropdown-item">Alerts</a>
                                    <a href="{{ route('second', ['ui', 'avatars']) }}" class="dropdown-item">Avatars</a>
                                    <a href="{{ route('second', ['ui', 'badges']) }}" class="dropdown-item">Badges</a>
                                    <a href="{{ route('second', ['ui', 'breadcrumb']) }}" class="dropdown-item">Breadcrumb</a>
                                    <a href="{{ route('second', ['ui', 'buttons']) }}" class="dropdown-item">Buttons</a>
                                    <a href="{{ route('second', ['ui', 'cards']) }}" class="dropdown-item">Cards</a>
                                    <a href="{{ route('second', ['ui', 'carousel']) }}"
                                       class="dropdown-item">Carousel</a>
                                    <a href="{{ route('second', ['ui', 'dropdowns']) }}"
                                       class="dropdown-item">Dropdowns</a>
                                    <a href="{{ route('second', ['ui', 'embed-video']) }}" class="dropdown-item">Embed
                                        Video</a>
                                    <a href="{{ route('second', ['ui', 'grid']) }}" class="dropdown-item">Grid</a>
                                    <a href="{{ route('second', ['ui', 'list-group']) }}" class="dropdown-item">List
                                        Group</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-ui-kit2"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Base UI 2
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ui-kit2">
                                    <a href="{{ route('second', ['ui', 'modals']) }}" class="dropdown-item">Modals</a>
                                    <a href="{{ route('second', ['ui', 'notifications']) }}" class="dropdown-item">Notifications</a>
                                    <a href="{{ route('second', ['ui', 'offcanvas']) }}"
                                       class="dropdown-item">Offcanvas</a>
                                    <a href="{{ route('second', ['ui', 'placeholders']) }}" class="dropdown-item">Placeholders</a>
                                    <a href="{{ route('second', ['ui', 'pagination']) }}" class="dropdown-item">Pagination</a>
                                    <a href="{{ route('second', ['ui', 'popovers']) }}"
                                       class="dropdown-item">Popovers</a>
                                    <a href="{{ route('second', ['ui', 'progress']) }}"
                                       class="dropdown-item">Progress</a>
                                    <a href="{{ route('second', ['ui', 'ribbons']) }}" class="dropdown-item">Ribbons</a>
                                    <a href="{{ route('second', ['ui', 'spinners']) }}"
                                       class="dropdown-item">Spinners</a>
                                    <a href="{{ route('second', ['ui', 'tabs']) }}" class="dropdown-item">Tabs</a>
                                    <a href="{{ route('second', ['ui', 'tooltips']) }}"
                                       class="dropdown-item">Tooltips</a>
                                    <a href="{{ route('second', ['ui', 'links']) }}" class="dropdown-item">Links</a>
                                    <a href="{{ route('second', ['ui', 'typography']) }}" class="dropdown-item">Typography</a>
                                    <a href="{{ route('second', ['ui', 'utilities']) }}"
                                       class="dropdown-item">Utilities</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-extended-ui"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Extended UI
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-extended-ui">
                                    <a href="{{ route('second', ['extended', 'dragula']) }}" class="dropdown-item">Dragula</a>
                                    <a href="{{ route('second', ['extended', 'range-slider']) }}" class="dropdown-item">Range
                                        Slider</a>
                                    <a href="{{ route('second', ['extended', 'ratings']) }}" class="dropdown-item">Ratings</a>
                                    <a href="{{ route('second', ['extended', 'scrollbar']) }}" class="dropdown-item">Scrollbar</a>
                                    <a href="{{ route('second', ['extended', 'scrollspy']) }}" class="dropdown-item">Scrollspy</a>
                                    <a href="{{ route('second', ['extended', 'treeview']) }}" class="dropdown-item">Treeview</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-forms"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Forms
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-forms">
                                    <a href="{{ route('second', ['forms', 'elements']) }}" class="dropdown-item">Basic
                                        Elements</a>
                                    <a href="{{ route('second', ['forms', 'advanced']) }}" class="dropdown-item">Form
                                        Advanced</a>
                                    <a href="{{ route('second', ['forms', 'validation']) }}" class="dropdown-item">Validation</a>
                                    <a href="{{ route('second', ['forms', 'wizard']) }}"
                                       class="dropdown-item">Wizard</a>
                                    <a href="{{ route('second', ['forms', 'fileuploads']) }}" class="dropdown-item">File
                                        Uploads</a>
                                    <a href="{{ route('second', ['forms', 'editors']) }}"
                                       class="dropdown-item">Editors</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-charts"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Charts
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-charts">
                                    <a href="{{ route('second', ['charts', 'chartjs-area']) }}" class="dropdown-item">Chartjs</a>
                                    <a href="{{ route('second', ['charts', 'brite']) }}" class="dropdown-item">Britecharts</a>
                                    <a href="{{ route('second', ['charts', 'apex-line']) }}" class="dropdown-item">Apex
                                        Charts</a>
                                    <a href="{{ route('second', ['charts', 'sparkline']) }}" class="dropdown-item">Sparklines</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-tables"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Tables
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-tables">
                                    <a href="{{ route('second', ['tables', 'basic']) }}" class="dropdown-item">Basic
                                        Tables</a>
                                    <a href="{{ route('second', ['tables', 'datatable']) }}" class="dropdown-item">Data
                                        Tables</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-icons"
                                   role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Icons
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-icons">
                                    <a href="{{ route('second', ['icons', 'remixicons']) }}" class="dropdown-item">Remix
                                        Icons</a>
                                    <a href="{{ route('second', ['icons', 'mdi']) }}" class="dropdown-item">Material
                                        Design</a>
                                    <a href="{{ route('second', ['icons', 'unicons']) }}"
                                       class="dropdown-item">Unicons</a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-maps"
                                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Maps
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-maps">
                                    <a href="{{ route('second', ['maps', 'google']) }}" class="dropdown-item">Google
                                        Maps</a>
                                    <a href="{{ route('second', ['maps', 'vector']) }}" class="dropdown-item">Vector
                                        Maps</a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-layouts" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="uil-window"></i>Layouts
                            <div class="arrow-down"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-layouts">
                            <a href="{{route('second', ['layouts-eg', 'vertical'])}}" target="_blank" class="dropdown-item">Vertical</a>
                            <a href="{{route('second', ['layouts-eg', 'horizontal'])}}" class="dropdown-item"
                               target="_blank">Horizontal</a>
                            <a href="{{route('any', 'index')}}" class="dropdown-item"
                               target="_blank">Detached</a>
                            <a href="{{route('second', ['layouts-eg', 'full'])}}" class="dropdown-item" target="_blank">Full</a>
                            <a href="{{route('second', ['layouts-eg', 'fullscreen'])}}" class="dropdown-item"
                               target="_blank">Fullscreen</a>
                            <a href="{{route('second', ['layouts-eg', 'hover'])}}" class="dropdown-item"
                               target="_blank">Hover Menu</a>
                            <a href="{{route('second', ['layouts-eg', 'compact'])}}" class="dropdown-item"
                               target="_blank">Compact Menu</a>
                            <a href="{{route('second', ['layouts-eg', 'icon-view'])}}" class="dropdown-item"
                               target="_blank">Icon View</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
<!-- ========== Horizontal Menu End ========== -->
