<!DOCTYPE html>
<html lang="en">

<head>

    @include('layout.header')

    <title>Login</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

</head>

<body>

    <main class="login-page">
        <section class="login-shell">
            <div class="brand-panel">
                <div class="brand-mark">
                    <span class="brand-logo" aria-hidden="true">
                        <img src="{{ asset('images/logo.png') }}"
                            alt="eBook Logo">
                    </span>
                    <span>UNITI</span>
                </div>
                <h1>Welcome to eBook Portal</h1>
                <p class="tagline">Smart Digital Library Management</p>
                <p class="subline">Manage | Upload | Share | Control</p>
                <div class="book-stack" aria-hidden="true"></div>
            </div>

            <div class="form-panel">
                <div class="form-card">
                    <h2>
                        <span class="login-title-icon" aria-hidden="true">
                            <img src="{{ asset('images/logo.png') }}"
                                alt="eBook Logo">
                        </span>
                        <span>Login</span>
                    </h2>

                    @if (session('error'))
                        <p class="error">{{ session('error') }}</p>
                    @endif

                    <form method="POST" action="{{ url('/serp-login') }}">
                        @csrf

                        <div class="form-field">
                            <input id="username" type="text" name="username" placeholder=" " required>
                            <label for="username" class="floating-label">
                                <span class="label-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img">
                                        <path
                                            d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4z" />
                                    </svg>
                                </span>
                                <span>User ID</span>
                            </label>
                            <span class="field-line" aria-hidden="true"></span>
                        </div>

                        <div class="form-field">
                            <input id="password" type="password" name="password" placeholder=" " required>
                            <label for="password" class="floating-label">
                                <span class="label-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img">
                                        <path
                                            d="M17 8h-1V6a4 4 0 1 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zm-7-2a2 2 0 0 1 4 0v2h-4z" />
                                    </svg>
                                </span>
                                <span>Password</span>
                            </label>
                            <span class="field-line" aria-hidden="true"></span>
                        </div>

                        <button type="submit">Login</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

</body>

</html>



