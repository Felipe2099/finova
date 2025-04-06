<div class="space-y-6">
    <h2 class="text-2xl font-bold">Settings</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Site Settings Card -->
        <a href="{{ route('admin.settings.site') }}" wire:navigate
            class="bg-white rounded-xl shadow-sm p-6 cursor-pointer hover:shadow-md transition-shadow">
            <div class="flex flex-col items-center justify-center text-center space-y-4">
                <div class="p-3 bg-primary-50 rounded-full">
                    <x-heroicon-o-globe-alt class="w-8 h-8 text-primary-500"/>
                </div>
                <h3 class="text-lg font-semibold">Site Settings</h3>
                <p class="text-sm text-gray-500">Manage your site settings</p>
            </div>
        </a>

        <!-- Payment Settings Card -->
        <a href="{{ route('admin.settings.payment') }}" wire:navigate
            class="bg-white rounded-xl shadow-sm p-6 cursor-pointer hover:shadow-md transition-shadow">
            <div class="flex flex-col items-center justify-center text-center space-y-4">
                <div class="p-3 bg-primary-50 rounded-full">
                    <x-heroicon-o-credit-card class="w-8 h-8 text-primary-500"/>
                </div>
                <h3 class="text-lg font-semibold">Payment Settings</h3>
                <p class="text-sm text-gray-500">Configure payment gateways</p>
            </div>
        </a>
    </div>
</div> 