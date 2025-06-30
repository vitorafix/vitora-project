<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 w-full"> {{-- Added w-full to ensure it takes full width within max-w --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="max-w-xl mx-auto"> {{-- Added mx-auto to center the content within the box --}}
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="max-w-xl mx-auto"> {{-- Added mx-auto to center the content within the box --}}
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="max-w-xl mx-auto"> {{-- Added mx-auto to center the content within the box --}}
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
