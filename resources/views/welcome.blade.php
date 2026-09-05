<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Phomoria - Photobooth Platform</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
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

        .container {
            width: min(1120px, calc(100% - 40px));
            margin: 0 auto;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11, 11, 13, 0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        nav {
            min-height: 74px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            color: #ffffff;
            font-weight: 800;
            font-size: 22px;
            letter-spacing: 3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            color: #ccccd2;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #ffffff;
        }

        .nav-login {
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 18px;
            border-radius: 999px;
        }

        .hero {
            min-height: 680px;
            display: flex;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            filter: blur(30px);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 880px;
            margin: 0 auto;
        }

        .eyebrow {
            color: #a7a7af;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0;
            font-size: clamp(52px, 8vw, 96px);
            letter-spacing: -4px;
            line-height: 0.98;
        }

        .hero p {
            max-width: 680px;
            margin: 30px auto 0;
            font-size: 19px;
            line-height: 1.7;
            color: #b9b9c1;
        }

        .hero-buttons {
            margin-top: 38px;
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 26px;
            border-radius: 999px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .button-primary {
            background: #ffffff;
            color: #111114;
        }

        .button-primary:hover {
            transform: translateY(-2px);
            background: #ededf0;
        }

        .button-secondary {
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }

        .button-secondary:hover {
            border-color: rgba(255, 255, 255, 0.4);
        }

        .section {
            padding: 100px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .section-title {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-title h2 {
            font-size: 42px;
            margin: 0 0 16px;
        }

        .section-title p {
            margin: 0;
            color: #a9a9b1;
            line-height: 1.7;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }

        .feature {
            border: 1px solid rgba(255, 255, 255, 0.09);
            background: rgba(255, 255, 255, 0.025);
            border-radius: 20px;
            padding: 28px 22px;
            min-height: 150px;
        }

        .feature strong {
            display: block;
            margin-bottom: 12px;
            font-size: 17px;
        }

        .feature span {
            color: #9999a2;
            font-size: 14px;
            line-height: 1.6;
        }

        .download-card {
            max-width: 780px;
            margin: 0 auto;
            padding: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.055),
                    rgba(255, 255, 255, 0.015)
                );
        }

        .download-card h2 {
            margin-top: 0;
            font-size: 38px;
        }

        .download-card p {
            color: #aaaab2;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        footer {
            padding: 40px 0;
            color: #76767f;
            font-size: 14px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 900px) {
            .features {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .container {
                width: min(100% - 28px, 1120px);
            }

            .nav-links a:not(.nav-login) {
                display: none;
            }

            h1 {
                letter-spacing: -2px;
            }

            .hero {
                min-height: 620px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 75px 0;
            }

            .download-card {
                padding: 35px 22px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="container">
        <nav>

            <a href="{{ route('home') }}" class="brand">
                PHOMORIA
            </a>

            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="{{ route('download') }}">Download</a>

                <a href="{{ route('login') }}" class="nav-login">
                    Cloud Login
                </a>
            </div>

        </nav>
    </div>
</header>

<main>

    <section class="hero">
        <div class="container">

            <div class="hero-content">

                <div class="eyebrow">
                    Photobooth Platform
                </div>

                <h1>
                    PHOMORIA
                </h1>

                <p>
                    Professional photobooth software designed for
                    DSLR cameras, live view, custom photo frames,
                    automatic printing, and cloud integration.
                </p>

                <div class="hero-buttons">

                    <a
                        href="{{ route('download') }}"
                        class="button button-primary"
                    >
                        Download Phomoria
                    </a>

                    <a
                        href="{{ route('login') }}"
                        class="button button-secondary"
                    >
                        Phomoria Cloud
                    </a>

                </div>

            </div>

        </div>
    </section>

    <section class="section" id="features">
        <div class="container">

            <div class="section-title">
                <h2>
                    Built for Photobooth
                </h2>

                <p>
                    From camera capture to final print,
                    Phomoria brings the main photobooth workflow
                    into one desktop platform.
                </p>
            </div>

            <div class="features">

                <div class="feature">
                    <strong>DSLR Camera</strong>
                    <span>
                        Connect and control supported DSLR cameras.
                    </span>
                </div>

                <div class="feature">
                    <strong>Live View</strong>
                    <span>
                        Real-time camera preview during sessions.
                    </span>
                </div>

                <div class="feature">
                    <strong>Custom Frames</strong>
                    <span>
                        Use layouts and photo frames from Phomoria Cloud.
                    </span>
                </div>

                <div class="feature">
                    <strong>Auto Print</strong>
                    <span>
                        Print completed photos directly from the application.
                    </span>
                </div>

                <div class="feature">
                    <strong>Cloud</strong>
                    <span>
                        Sync frames and manage photobooth resources online.
                    </span>
                </div>

            </div>

        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="download-card">

                <div class="eyebrow">
                    Windows
                </div>

                <h2>
                    Ready to run your booth?
                </h2>

                <p>
                    Download Phomoria for Windows and prepare your
                    camera for your next photobooth session.
                </p>

                <a
                    href="{{ route('download') }}"
                    class="button button-primary"
                >
                    Go to Downloads
                </a>

            </div>

        </div>
    </section>

</main>

<footer>
    <div class="container">
        © {{ date('Y') }} Phomoria.
    </div>
</footer>

</body>
</html>