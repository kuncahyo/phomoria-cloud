<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Download - Phomoria</title>

    <style>
        * {
            box-sizing: border-box;
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
            width: min(960px, calc(100% - 40px));
            margin: 0 auto;
        }

        header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        nav {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 3px;
            font-size: 22px;
        }

        .back {
            color: #b8b8c0;
            font-size: 14px;
        }

        .back:hover {
            color: #ffffff;
        }

        main {
            padding: 90px 0 120px;
        }

        .heading {
            text-align: center;
            margin-bottom: 60px;
        }

        .heading h1 {
            font-size: 52px;
            margin: 0 0 16px;
        }

        .heading p {
            margin: 0;
            color: #aaaab2;
            line-height: 1.6;
        }

        .downloads {
            display: grid;
            gap: 22px;
        }

        .card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
            padding: 34px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.025);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 26px;
        }

        .card p {
            margin: 0;
            max-width: 540px;
            color: #9f9fa8;
            line-height: 1.65;
        }

        .button {
            flex: 0 0 auto;
            display: inline-flex;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            padding: 0 24px;
            border-radius: 999px;
            background: #ffffff;
            color: #111114;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            background: #ededf0;
        }

        .version {
            margin-top: 8px;
            color: #707078;
            font-size: 13px;
        }

        .note {
            margin-top: 35px;
            text-align: center;
            color: #707078;
            font-size: 13px;
            line-height: 1.6;
        }

        footer {
            padding: 35px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #65656d;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            main {
                padding-top: 60px;
            }

            .heading h1 {
                font-size: 40px;
            }

            .card {
                flex-direction: column;
                align-items: flex-start;
            }

            .button {
                width: 100%;
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

            <a href="{{ route('home') }}" class="back">
                Back to Home
            </a>

        </nav>
    </div>
</header>

<main>
    <div class="container">

        <div class="heading">
            <h1>Downloads</h1>

            <p>
                Download the software and tools needed to prepare
                your Phomoria photobooth.
            </p>
        </div>

        <div class="downloads">

            <div class="card">

                <div>
                    <h2>Phomoria</h2>

                    <p>
                        Windows photobooth application with DSLR camera
                        support, Live View, custom frames, automatic
                        printing, and Phomoria Cloud integration.
                    </p>

                    <div class="version">
                        Version 1.0.0
                    </div>
                </div>

                <a
                    href="{{ route('download.phomoria') }}"
                    class="button"
                >
                    Download
                </a>

            </div>

            <div class="card">

                <div>
                    <h2>Zadig</h2>

                    <p>
                        USB driver utility used when preparing compatible
                        DSLR cameras for Phomoria.
                    </p>

                    <div class="version">
                        Version 2.9
                    </div>
                </div>

                <a
                    href="{{ route('download.zadig') }}"
                    class="button"
                >
                    Download
                </a>

            </div>

        </div>

        <div class="note">
            Phomoria is designed for Windows desktop systems.
            Zadig may be required when configuring the camera USB driver.
        </div>

    </div>
</main>

<footer>
    <div class="container">
        © {{ date('Y') }} Phomoria.
    </div>
</footer>

</body>
</html>