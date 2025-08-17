@if($authUser->can('admin_consultants_lists') or
                $authUser->can('admin_appointments_lists')
            )
    <li class="menu-header">{{ trans('site.appointments') }}</li>
@endif

@can('admin_consultants_lists')
    <li class="{{ (request()->is(getAdminPanelUrl('/consultants', false))) ? 'active' : '' }}">
        <a href="{{ getAdminPanelUrl() }}/consultants" class="nav-link">
        <x-iconsax-bul-tag-user class="icons" width="24px" height="24px"/>
        <span>{{ trans('admin/main.consultants') }}</span>
        </a>
    </li>
@endcan

@can('admin_appointments_lists')
    <li class="{{ (request()->is(getAdminPanelUrl('/appointments', false))) ? 'active' : '' }}">
        <a class="nav-link" href="{{ getAdminPanelUrl() }}/appointments">
        <x-iconsax-bul-calendar class="icons" width="24px" height="24px"/>
            <span>{{ trans('admin/main.appointments') }}</span>
        </a>
    </li>
@endcan
