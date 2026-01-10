<x-adminlte.guest-layout>
    <!-- Session Status -->
    <x-adminlte.auth-session-status class="mb-4" :status="session('status')" />

    <p class="login-box-msg">{{ __('Sign in to start your session') }}</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <x-adminlte.input-group id="email" type="email" name="email" label="Email" icon="envelope" :value="old('email')"
            required autofocus autocomplete="username" />

        <!-- Password -->
        <x-adminlte.input-group id="password" type="password" name="password" label="Password" icon="lock-fill"
            required autocomplete="current-password" />

        <!-- Remember Me -->
        <div class="row mb-3 align-items-center">
            <div class="col-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me"
                        {{ old('remember') ? 'checked' : '' }} />
                    <label class="form-check-label" for="remember_me">
                        {{ __('Remember me') }}
                    </label>
                </div>
            </div>

            <div class="col-5">
                <x-adminlte.primary-button class="btn-block text-nowrap">
                    {{ __('Log in') }}
                </x-adminlte.primary-button>
            </div>
        </div>

        {{-- <div class="row">
            <div class="col-12">
                @if (Route::has('password.request'))
                    <p class="mb-1">
                        <a href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    </p>
                @endif

                @if (Route::has('register'))
                    <p class="mb-0">
                        <a href="{{ route('register') }}" class="text-center">
                            {{ __('Register a new membership') }}
                        </a>
                    </p>
                @endif
            </div>
        </div> --}}
    </form>
</x-adminlte.guest-layout>
