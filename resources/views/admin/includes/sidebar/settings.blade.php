@if($authUser->can('admin_settings'))
    <li class="menu-header">{{ trans('admin/main.settings') }}</li>
@endif

@can('admin_translator')
    <li class="nav-item {{ (request()->is(getAdminPanelUrl('/translator*', false))) ? 'active' : '' }}">
        <a href="{{ getAdminPanelUrl() }}/translator" class="nav-link">
        <x-iconsax-bul-translate class="icons" width="24px" height="24px"/>
            <span>{{ trans('update.translator') }}</span>
        </a>
    </li>
@endcan

@can('admin_settings')
    <li class="nav-item {{ (request()->is(getAdminPanelUrl('/licenses', false))) ? 'active' : '' }}">
        <a href="{{ getAdminPanelUrl() }}/licenses" class="nav-link">
        <x-iconsax-bul-key class="icons" width="24px" height="24px"/>
            <span>Licenses</span>
        </a>
    </li>
@endcan

@can('admin_settings')
    @php
        $settingClass ='';

        if (request()->is(getAdminPanelUrl('/settings*', false)) and
                !(
                    request()->is(getAdminPanelUrl('/settings/404', false)) or
                    request()->is(getAdminPanelUrl('/settings/contact_us', false)) or
                    request()->is(getAdminPanelUrl('/settings/footer', false)) or
                    request()->is(getAdminPanelUrl('/settings/navbar_links', false))
                )
            ) {
                $settingClass = 'active';
            }
    @endphp

    <li class="nav-item {{ $settingClass ?? '' }}">
        <a href="{{ getAdminPanelUrl() }}/settings" class="nav-link">
        <x-iconsax-bul-setting-2 class="icons" width="24px" height="24px"/>
            <span>{{ trans('admin/main.settings') }}</span>
        </a>
    </li>
@endcan()
