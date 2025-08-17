<div class="landing-builder-header d-flex align-items-center bg-white px-24 px-md-0">
    <div class="landing-builder-header__logo px-md-32">
        <a href="/" class="d-inline-flex">
            @if(!empty($generalSettings['logo']))
                <img src="{{ $generalSettings['logo'] }}" class="img-fluid" alt="site logo">
            @endif
        </a>
    </div>

    <div class="flex-1 d-flex align-items-center justify-content-between px-md-32 h-100 border-bottom-gray-200">

        <div class="">

            <a href="{{ getAdminPanelUrl("/") }}" class="d-none d-lg-flex align-items-center gap-4">
                <x-iconsax-lin-arrow-left class="icons text-gray-500" width="20px" height="20px"/>
                <span class="text-gray-500">{{ trans('update.back_to_admin_panel') }}</span>
            </a>

        </div>

        <div class="d-flex align-items-center">

            <div class="size-32 position-relative d-flex-center bg-gray-100 rounded-8">
                <x-iconsax-lin-notification class="icons text-gray-500" width="20px" height="20px"/>
                <span class="landing-builder-header__badge-counter badge-counter">3</span>
            </div>

            <div class="ml-16 pl-16 border-left-gray-100">
                @include('landingBuilder.admin.includes.auth_user')
            </div>
        </div>
    </div>
</div>
