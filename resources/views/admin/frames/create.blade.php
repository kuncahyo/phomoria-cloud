<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Upload Frame - Phomoria Cloud</title>

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
            width: min(820px, calc(100% - 40px));
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

        .back {
            color: #9999a2;
            font-size: 14px;
        }

        .back:hover {
            color: #ffffff;
        }

        main {
            padding: 70px 0 100px;
        }

        .heading {
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
            margin: 0 0 12px;
            font-size: 42px;
            letter-spacing: -1px;
        }

        .subtitle {
            margin: 0;
            color: #9999a2;
            line-height: 1.6;
        }

        .card {
            padding: 36px;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.025);
        }

        .error {
            margin-bottom: 25px;
            padding: 15px 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 80, 80, 0.2);
            background: rgba(255, 80, 80, 0.07);
            color: #ffb4b4;
            font-size: 13px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 23px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #c9c9d0;
            font-size: 13px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            min-height: 50px;
            padding: 0 15px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            outline: none;
            background: rgba(255, 255, 255, 0.045);
            color: #ffffff;
            font: inherit;
        }

        input[type="text"]:focus,
        input[type="file"]:focus,
        select:focus {
            border-color: rgba(255, 255, 255, 0.4);
        }

        input[type="file"] {
            padding: 10px;
            cursor: pointer;
        }

        select {
            cursor: pointer;
        }

        select option {
            background: #171719;
            color: #ffffff;
        }

        .hint {
            margin-top: 8px;
            color: #707078;
            font-size: 12px;
            line-height: 1.5;
        }

        .preview-section {
            display: none;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .preview-section.visible {
            display: block;
        }

        .preview-label {
            margin-bottom: 12px;
            color: #c9c9d0;
            font-size: 13px;
            font-weight: 600;
        }

        .preview-wrapper {
            width: 100%;
            min-height: 180px;
            max-height: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
            padding: 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            background-color: #18181b;
            background-image:
                linear-gradient(45deg, #222225 25%, transparent 25%),
                linear-gradient(-45deg, #222225 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #222225 75%),
                linear-gradient(-45deg, transparent 75%, #222225 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0;
        }

        .preview-image {
            display: none;
            max-width: 100%;
            max-height: 480px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .preview-info {
            margin-top: 10px;
            color: #707078;
            font-size: 12px;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 32px;
        }

        .button {
            min-height: 50px;
            padding: 0 23px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .button-primary {
            border: 0;
            background: #ffffff;
            color: #111114;
        }

        .button-primary:hover {
            background: #ededf0;
        }

        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #bdbdc5;
        }

        .button-secondary:hover {
            border-color: rgba(255, 255, 255, 0.35);
            color: #ffffff;
        }

        footer {
            padding: 35px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #5f5f67;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .container {
                width: min(100% - 28px, 820px);
            }

            main {
                padding-top: 50px;
            }

            h1 {
                font-size: 34px;
            }

            .card {
                padding: 25px 20px;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .button,
            .button-secondary {
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

            <a
                href="{{ route('admin.frames.index') }}"
                class="back"
            >
                Back to Frames
            </a>

        </nav>
    </div>
</header>

<main>
    <div class="container">

        <div class="heading">

            <div class="eyebrow">
                Cloud Admin
            </div>

            <h1>Upload Frame</h1>

            <p class="subtitle">
                Add a PNG frame that can be synchronized to Phomoria devices.
            </p>

        </div>

        <div class="card">

            @if ($errors->any())
                <div class="error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.frames.store') }}"
                enctype="multipart/form-data"
            >

                @csrf

                <div class="field">

                    <label for="name">
                        Nama Frame
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                    >

                </div>

                <div class="field">

                    <label for="category">
                        Kategori
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >
                        <option value="">
                            Pilih kategori
                        </option>

                        <option
                            value="standar"
                            @selected(old('category') === 'standar')
                        >
                            Standar
                        </option>

                        <option
                            value="split"
                            @selected(old('category') === 'split')
                        >
                            Split
                        </option>
                    </select>

                    <div class="hint">
                        Pilih tipe layout frame yang akan digunakan.
                    </div>

                </div>

                <div class="field">

                    <label for="image">
                        PNG Frame
                    </label>

                    <input
                        id="image"
                        type="file"
                        name="image"
                        accept="image/png"
                        required
                    >

                    <div class="hint">
                        Gunakan file PNG dengan transparansi sesuai
                        kebutuhan frame photobooth.
                    </div>

                </div>

                <div
                    id="previewSection"
                    class="preview-section"
                >

                    <div class="preview-label">
                        Preview Frame
                    </div>

                    <div class="preview-wrapper">

                        <img
                            id="previewImage"
                            class="preview-image"
                            src=""
                            alt="Preview frame"
                        >

                    </div>

                    <div
                        id="previewInfo"
                        class="preview-info"
                    ></div>

                </div>

                <div class="actions">

                    <a
                        href="{{ route('admin.frames.index') }}"
                        class="button button-secondary"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        Upload Frame
                    </button>

                </div>

            </form>

        </div>

    </div>
</main>

<footer>
    <div class="container">
        © {{ date('Y') }} Phomoria Cloud
    </div>
</footer>

<script>
    const imageInput = document.getElementById('image');
    const previewSection = document.getElementById('previewSection');
    const previewImage = document.getElementById('previewImage');
    const previewInfo = document.getElementById('previewInfo');

    imageInput.addEventListener('change', function () {
        const file = this.files[0];

        if (!file) {
            previewSection.classList.remove('visible');
            previewImage.style.display = 'none';
            previewImage.src = '';
            previewInfo.textContent = '';
            return;
        }

        if (file.type !== 'image/png') {
            previewSection.classList.remove('visible');
            previewImage.style.display = 'none';
            previewImage.src = '';
            previewInfo.textContent = '';
            return;
        }

        const objectUrl = URL.createObjectURL(file);

        previewImage.onload = function () {
            previewSection.classList.add('visible');
            previewImage.style.display = 'block';

            previewInfo.textContent =
                `${previewImage.naturalWidth} × ${previewImage.naturalHeight}px`;

            URL.revokeObjectURL(objectUrl);
        };

        previewImage.src = objectUrl;
    });
</script>

</body>
</html>