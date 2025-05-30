<div class="card {{ Request::is('users*') || Request::is('settings/permissions*') || Request::is('settings/roles*') ? '' : 'collapsed-card' }}">
    <div class="card-header">
        <h3 class="card-title">{{trans('lang.menu')}}</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fa {{ Request::is('users*')  ? 'fa-minus' : 'fa-plus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <ul class="nav nav-pills flex-column">
         
            <li class="nav-item">
                <a href="{!! route('users.index') !!}" class="nav-link {{  Request::is('users*') ? 'selected' : '' }}">
                    <i class="fas fa-users"></i> {{trans('lang.user_plural')}}
                </a>
            </li>

        </ul>
    </div>
</div>


