<section class="w-full">
    {{--
        Settings 页面标题和返回 Dashboard 按钮。
    --}}
    @include('partials.settings-heading')

    {{--
        给屏幕阅读器使用的隐藏标题。
        普通用户不会在画面上看到这个标题。
    --}}
    <flux:heading class="sr-only">
        {{ __('Profile settings') }}
    </flux:heading>

    {{--
        Settings 页面统一内容布局。

        heading：
        当前 Settings 功能名称。

        subheading：
        当前功能的简单说明。
    --}}
    <x-settings.layout
        :heading="__('Profile')"
        :subheading="__('Update your name and email address')"
    >
        {{--
            用户按下 Save 后，
            Livewire 会调用 Profile.php 里面的：

            updateProfileInformation()
        --}}
        <form
            wire:submit="updateProfileInformation"
            class="my-6 w-full space-y-6"
        >
            {{--
                Name Input。

                wire:model="profileName"
                对应 Profile.php 里面的：

                public string $profileName = '';

                不需要额外加入：
                :value="$profileName"

                因为 wire:model 已经负责：
                1. 把 Component 的资料显示在 Input
                2. 把用户输入同步回 Component
            --}}
            <flux:input
                wire:model="profileName"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            <div>
                {{--
                    Email Input。

                    wire:model="profileEmail"
                    对应 Profile.php 里面的：

                    public string $profileEmail = '';

                    同样不需要传入 :value。
                --}}
                <flux:input
                    wire:model="profileEmail"
                    :label="__('Email')"
                    type="email"
                    required
                    autocomplete="email"
                />

                {{--
                    如果当前 User 实现了 MustVerifyEmail，
                    并且 Email 尚未完成验证，
                    就显示重新发送验证邮件的提示。
                --}}
                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link
                                wire:click.prevent="resendVerificationNotification"
                                class="cursor-pointer text-sm"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>
                    </div>
                @endif
            </div>


            <flux:input
                wire:model="phone"
                :label="__('Phone number')"
                type="tel"
                required
                autocomplete="tel"
                placeholder="012-3456789"
            />


            <div class="flex items-center gap-4">
                <flux:button
                    type="submit"
                    variant="primary"
                >
                    {{ __('Save') }}
                </flux:button>

                {{--
                    只有 updateProfileInformation() 正在执行时，
                    才显示 Saving...。
                --}}
                <flux:text
                    wire:loading
                    wire:target="updateProfileInformation"
                    class="text-sm text-zinc-500 dark:text-zinc-400"
                >
                    {{ __('Saving...') }}
                </flux:text>
            </div>
        </form>

        {{--
            如果当前用户允许删除 Account，
            就载入 Delete User Livewire Component。
        --}}
        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>