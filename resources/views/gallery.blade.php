<!DOCTYPE html>
<html>
<head>
    <title>Phomoria Gallery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(!$session->photos->contains(fn ($photo) => $photo->is_result))
        <meta http-equiv="refresh" content="5">
    @endif
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; text-align: center; margin: 40px 16px; }
        .result { width: 500px; max-width: 100%; border-radius: 12px; box-shadow: 0 0 15px #aaa; }
        .thumb { width: 180px; max-width: 42vw; margin: 10px; border-radius: 8px; box-shadow: 0 0 10px #bbb; }
        .photos { display: flex; justify-content: center; flex-wrap: wrap; }
        .media { margin: 24px auto; max-width: 720px; }
        .media img, .media video { width: 100%; max-width: 520px; border-radius: 12px; box-shadow: 0 0 12px #bbb; }
        a { display: inline-block; margin: 10px 5px 0; padding: 12px 25px; background: #2196F3; color: white; text-decoration: none; border-radius: 8px; }
        .waiting { margin: 28px auto; padding: 18px; max-width: 520px; background: white; border-radius: 12px; color: #555; }
    </style>
</head>
<body>
    <h1>Phomoria Gallery</h1>
    <h3>{{ $session->session_code }}</h3>

    @php
        $result = $session->photos->firstWhere('is_result', true);
        $originalPhotos = $session->photos->filter(fn ($photo) => !$photo->is_result && $photo->asset_type === 'photo');
        $gif = $session->photos->firstWhere('asset_type', 'gif');
        $livePhoto = $session->photos->firstWhere('asset_type', 'live_photo');
    @endphp

    @if($result)
        <img class="result" src="{{ $result->url }}" alt="Result photo">
    @else
        <div class="waiting">
            <h2>Foto sedang disiapkan</h2>
            <p>Halaman ini akan diperbarui otomatis setelah foto utama berhasil diunggah.</p>
        </div>
    @endif

    @if($originalPhotos->isNotEmpty())
        <hr>
        <h2>Foto Individual</h2>
        <div class="photos">
            @foreach($originalPhotos as $photo)
                <img class="thumb" src="{{ $photo->url }}" alt="Foto {{ $loop->iteration }}">
            @endforeach
        </div>
    @endif

    @if($gif || $livePhoto)
        <hr>
        <h2>Media Tambahan</h2>
        @if($gif)
            <div class="media">
                <h3>Animated GIF</h3>
                <img src="{{ $gif->url }}" alt="Animated GIF">
            </div>
        @endif
        @if($livePhoto)
            <div class="media">
                <h3>Live Photo</h3>
                <video controls playsinline preload="metadata">
                    <source src="{{ $livePhoto->url }}" type="video/mp4">
                    Browser Anda tidak mendukung pemutaran video.
                </video>
            </div>
        @endif
    @endif

    @if($result)
        <div style="margin-top:25px;">
            <a href="{{ route('gallery.download.result', $session->session_code) }}">Download Result</a>
            <a href="{{ route('gallery.download', $session->session_code) }}" style="background:#4CAF50;">Download Semua Foto (ZIP)</a>
        </div>
    @endif

    <script>
        // Keep an already-open gallery fresh when GIF/Live Photo arrive later.
        (() => {
            const statusUrl = @json(url('/gallery/' . $session->session_code . '/status'));
            let knownCount = {{ $session->photos->count() }};
            window.setInterval(async () => {
                try {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const state = await response.json();
                    if (Number(state.asset_count) !== knownCount) window.location.reload();
                } catch (_) {
                    // A temporary network error must not affect the already loaded gallery.
                }
            }, 5000);
        })();
    </script>
</body>
</html>
