<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-{{setting('theme_contrast')}}-{{setting('theme_color')}} shadow">
     <!-- Brand Logo (statique en haut) -->
    <div class="brand-link border-bottom-0 {{setting('logo_bg_color','bg-white')}} flex-shrink-0">
        <a href="{{url('dashboard')}}" class="d-flex align-items-center">
            <img src="{{$app_logo ?? ''}}" alt="{{setting('app_name')}}" class="brand-image me-2">
            <span class="brand-text font-weight-light">{{setting('app_name')}}</span>
        </a>
    </div>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                @include('layouts.menu',['icons'=>true])
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
