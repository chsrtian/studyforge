<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- User Stats Card -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Study Statistics') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Your learning progress and activity.') }}
                            </p>
                        </header>

                        <div class="mt-6 flex space-x-8">
                            <div class="text-center">
                                <span class="block text-3xl font-bold text-primary">{{ $totalSessions }}</span>
                                <span class="block text-sm font-medium text-gray-500 uppercase tracking-wider">Sessions</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-3xl font-bold text-accent">{{ $totalFlashcards }}</span>
                                <span class="block text-sm font-medium text-gray-500 uppercase tracking-wider">Flashcards</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-3xl font-bold text-success">{{ $totalQuizzes }}</span>
                                <span class="block text-sm font-medium text-gray-500 uppercase tracking-wider">Quizzes</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

