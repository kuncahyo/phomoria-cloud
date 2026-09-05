<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cloud Login - Phomoria</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #0b0b0d;
            color: #ffffff;
        }

        a {
            text-decoration: none;
        }

        .page {
            width: min(430px, calc(100% - 32px));
        }

        .brand {
            display: block;
            text-align: center;

            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 4px;

            margin-bottom: 45px;
        }

        .card {
            padding: 38px;

            border-radius: 26px;

            border: 1px solid rgba(255, 255, 255, 0.1);

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.055),
                    rgba(255, 255, 255, 0.018)
                );

            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.35);
        }

        .heading {
            text-align: center;
            margin-bottom: 32px;
        }

        .heading h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        .heading p {
            margin: 0;
            color: #9898a1;
            font-size: 14px;
            line-height: 1.6;
        }

        .error {
            margin-bottom: 22px;
            padding: 14px 16px;

            border-radius: 12px;

            background: rgba(255, 80, 80, 0.08);
            border: 1px solid rgba(255, 80, 80, 0.2);

            color: #ffb0b0;
            font-size: 13px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;

            color: #c8c8cf;
            font-size: 13px;
            font-weight: 600;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 50px;

            padding: 0 15px;

            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;

            outline: none;

            background: rgba(255, 255, 255, 0.045);
            color: #ffffff;

            font-size: 15px;

            transition: border-color 0.2s, background 0.2s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.07);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 9px;

            margin: 5px 0 25px;

            color: #9999a2;
            font-size: 13px;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: #ffffff;
        }

        button {
            width: 100%;
            height: 52px;

            border: 0;
            border-radius: 999px;

            background: #ffffff;
            color: #111114;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            transition: transform 0.2s, background 0.2s;
        }

        button:hover {
            transform: translateY(-1px);
            background: #ededf0;
        }

        .back {
            display: block;

            margin-top: 28px;

            text-align: center;

            color: #85858e;
            font-size: 13px;
        }

        .back:hover {
            color: #ffffff;
        }

        .footer {
            margin-top: 28px;

            text-align: center;

            color: #55555d;
            font-size: 12px;
        }

        @media (max-width: 500px) {
            .card {
                padding: 30px 22px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <a href="{{ route('home') }}" class="brand">
        PHOMORIA
    </a>

    <div class="card">

        <div class="heading">
            <h1>Cloud Login</h1>

            <p>
                Sign in to manage your Phomoria Cloud resources.
            </p>
        </div>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">

                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                >

            </div>

            <div class="field">

                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >

            </div>

            <label class="remember">

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                <span>Ingat saya</span>

            </label>

            <button type="submit">
                Login
            </button>

        </form>

        <a href="{{ route('home') }}" class="back">
            ← Back to Home
        </a>

    </div>

    <div class="footer">
        © {{ date('Y') }} Phomoria
    </div>

</div>

</body>
</html>