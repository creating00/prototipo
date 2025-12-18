<x-admin-lte.guest-layout>
    <p class="register-box-msg">Register a new membership</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <x-admin-lte.input-group id="name" type="text" name="name" label="Full Name" icon="person"
            :value="old('name')" required autofocus autocomplete="name" />

        <!-- Email Address -->
        <x-admin-lte.input-group id="email" type="email" name="email" label="Email" icon="envelope"
            :value="old('email')" required autocomplete="username" />

        <!-- Password -->
        <x-admin-lte.input-group id="password" type="password" name="password" label="Password" icon="lock-fill"
            required autocomplete="new-password" />

        <!-- Confirm Password -->
        <x-admin-lte.input-group id="password_confirmation" type="password" name="password_confirmation"
            label="Confirm Password" icon="lock-fill" required autocomplete="new-password" />

        <!-- Terms Agreement -->
        <div class="row mb-3">
            <div class="col-8 d-inline-flex align-items-center">
                <x-admin-lte.checkbox name="terms" id="terms" label='I agree to the <a href="#">terms</a>'
                    required />
                @error('terms')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <!-- /.col -->
            <div class="col-4">
                <div class="d-grid gap-2">
                    <x-admin-lte.primary-button class="btn-block">
                        {{ __('Register') }}
                    </x-admin-lte.primary-button>
                </div>
            </div>
            <!-- /.col -->
        </div>

        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0">
                    <a href="{{ route('login') }}" class="link-primary text-center">
                        I already have a membership
                    </a>
                </p>
            </div>
        </div>
    </form>
</x-admin-lte.guest-layout>
