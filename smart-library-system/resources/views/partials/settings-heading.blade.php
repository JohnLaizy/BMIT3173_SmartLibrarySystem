<div class="relative mb-6 w-full">
    <div
        class="flex flex-col gap-4
               sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <flux:heading size="xl" level="1">
                {{ __('Settings') }}
            </flux:heading>

            <flux:subheading size="lg">
                {{ __('Manage your profile and account settings') }}
            </flux:subheading>
        </div>

        <flux:button
            :href="route('dashboard')"
            variant="ghost"
            icon="arrow-left"
            wire:navigate.hover
        >
            Back to Dashboard
        </flux:button>
    </div>

    <flux:separator variant="subtle" class="mt-6" />
</div>