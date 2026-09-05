<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Frames - Phomoria Cloud</title>

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
            width: min(1100px, calc(100% - 40px));
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
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 3px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-link {
            color: #9999a2;
            font-size: 14px;
        }

        .nav-link:hover {
            color: #ffffff;
        }

        .logout {
            padding: 9px 17px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            background: transparent;
            color: #c7c7ce;
            cursor: pointer;
            font-size: 13px;
        }

        .logout:hover {
            border-color: rgba(255, 255, 255, 0.35);
            color: #ffffff;
        }

        main {
            padding: 70px 0 100px;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 25px;
            margin-bottom: 35px;
        }

        .eyebrow {
            margin-bottom: 10px;
            color: #888890;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 42px;
            letter-spacing: -1px;
        }

        .subtitle {
            margin: 10px 0 0;
            color: #9999a2;
            line-height: 1.6;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            background: #ffffff;
            color: #111114;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            transition: 0.2s ease;
        }

        .primary-button:hover {
            transform: translateY(-1px);
            background: #ededf0;
        }

        .success {
            margin-bottom: 24px;
            padding: 15px 18px;
            border-radius: 14px;
            border: 1px solid rgba(120, 220, 150, 0.2);
            background: rgba(120, 220, 150, 0.07);
            color: #b9edc7;
            font-size: 14px;
        }

        .warning {
            margin-bottom: 24px;
            padding: 15px 18px;
            border-radius: 14px;
            border: 1px solid rgba(240, 190, 80, 0.2);
            background: rgba(240, 190, 80, 0.07);
            color: #eed99b;
            font-size: 14px;
        }

        .table-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.025);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 18px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.09);
            color: #777780;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            padding: 15px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            color: #c7c7ce;
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.025);
        }

        .frame-preview {
            width: 76px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background-color: #18181b;
            background-image:
                linear-gradient(45deg, #222225 25%, transparent 25%),
                linear-gradient(-45deg, #222225 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #222225 75%),
                linear-gradient(-45deg, transparent 75%, #222225 75%);
            background-size: 16px 16px;
            background-position: 0 0, 0 8px, 8px -8px, -8px 0;
            padding: 4px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .frame-preview:hover {
            transform: scale(1.04);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .frame-preview img {
            display: block;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .frame-name {
            color: #ffffff;
            font-weight: 600;
        }

        .category {
            color: #b0b0b8;
        }

        .status {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.07);
            color: #c9c9d0;
            font-size: 12px;
        }

        .empty {
            padding: 65px 30px;
            text-align: center;
        }

        .empty h2 {
            margin: 0 0 10px;
            font-size: 21px;
        }

        .empty p {
            margin: 0 0 25px;
            color: #85858e;
        }

        .modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 30px;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(8px);
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            position: relative;
            width: min(900px, 100%);
            max-height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }

        .modal-image-wrapper {
            max-width: 100%;
            max-height: calc(100vh - 130px);
            padding: 10px;
            overflow: auto;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 18px;
            background-color: #18181b;
            background-image:
                linear-gradient(45deg, #222225 25%, transparent 25%),
                linear-gradient(-45deg, #222225 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #222225 75%),
                linear-gradient(-45deg, transparent 75%, #222225 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0;
        }

        .modal-image {
            display: block;
            max-width: 100%;
            max-height: calc(100vh - 150px);
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .modal-title {
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
        }

        .modal-close {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 42px;
            height: 42px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            background: rgba(20, 20, 22, 0.95);
            color: #ffffff;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            z-index: 2;
        }

        .modal-close:hover {
            background: #ffffff;
            color: #111114;
        }

        footer {
            padding: 35px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #5f5f67;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            .container {
                width: min(100% - 28px, 1100px);
            }

            main {
                padding-top: 50px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            h1 {
                font-size: 34px;
            }

            .primary-button {
                width: 100%;
            }

            .table-card {
                overflow-x: auto;
            }

            table {
                min-width: 760px;
            }

            .modal {
                padding: 15px;
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

            <div class="nav-right">

                <a href="{{ route('download') }}" class="nav-link">
                    Downloads
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="logout">
                        Logout
                    </button>
                </form>

            </div>

        </nav>
    </div>
</header>

<main>
    <div class="container">

        <div class="page-heading">

            <div>
                <div class="eyebrow">
                    Cloud Admin
                </div>

                <h1>Frames</h1>

                <p class="subtitle">
                    Manage photo frames available to Phomoria devices.
                </p>
            </div>

            <a
                href="{{ route('admin.frames.create') }}"
                class="primary-button"
            >
                + Upload Frame
            </a>

        </div>

        @if (session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="warning">
                {{ session('warning') }}
            </div>
        @endif

        @if ($frames->isEmpty())

            <div class="table-card">

                <div class="empty">

                    <h2>Belum ada frame</h2>

                    <p>
                        Upload frame PNG pertama untuk mulai digunakan
                        oleh perangkat Phomoria.
                    </p>

                    <a
                        href="{{ route('admin.frames.create') }}"
                        class="primary-button"
                    >
                        Upload Frame
                    </a>

                </div>

            </div>

        @else

            <div class="table-card">

                <table>

                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Ukuran</th>
                            <th>Version</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($frames as $frame)

                            <tr>

                                <td>
                                    <button
                                        type="button"
                                        class="frame-preview"
                                        onclick="openFramePreview(
                                            '{{ asset('storage/' . $frame->image_path) }}',
                                            '{{ addslashes($frame->name) }}'
                                        )"
                                        title="Klik untuk melihat frame"
                                    >
                                        <img
                                            src="{{ asset('storage/' . $frame->image_path) }}"
                                            alt="{{ $frame->name }}"
                                        >
                                    </button>
                                </td>

                                <td class="frame-name">
                                    {{ $frame->name }}
                                </td>

                                <td class="category">
                                    @if ($frame->category === 'standar')
                                        Standar
                                    @elseif ($frame->category === 'split')
                                        Split
                                    @else
                                        {{ $frame->category ?? '-' }}
                                    @endif
                                </td>

                                <td>
                                    {{ $frame->width }} × {{ $frame->height }}
                                </td>

                                <td>
                                    {{ $frame->version }}
                                </td>

                                <td>
                                    <span class="status">
                                        {{ $frame->status }}
                                    </span>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>
</main>

<footer>
    <div class="container">
        © {{ date('Y') }} Phomoria Cloud
    </div>
</footer>

<div
    id="frameModal"
    class="modal"
    onclick="closeFramePreview(event)"
>

    <div class="modal-content">

        <button
            type="button"
            class="modal-close"
            onclick="closeFramePreview()"
            aria-label="Tutup preview"
        >
            ×
        </button>

        <div class="modal-image-wrapper">

            <img
                id="frameModalImage"
                class="modal-image"
                src=""
                alt=""
            >

        </div>

        <div
            id="frameModalTitle"
            class="modal-title"
        ></div>

    </div>

</div>

<script>
    function openFramePreview(imageUrl, title) {
        const modal = document.getElementById('frameModal');
        const image = document.getElementById('frameModalImage');
        const modalTitle = document.getElementById('frameModalTitle');

        image.src = imageUrl;
        image.alt = title;
        modalTitle.textContent = title;

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeFramePreview(event) {
        if (
            event &&
            event.target !== document.getElementById('frameModal')
        ) {
            return;
        }

        const modal = document.getElementById('frameModal');
        const image = document.getElementById('frameModalImage');

        modal.classList.remove('open');
        image.src = '';

        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFramePreview();
        }
    });
</script>

</body>
</html>