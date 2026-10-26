<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phomoria Gallery</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f5f5; text-align:center; margin:30px; color:#222; }
        .result { width:500px; max-width:100%; border-radius:12px; box-shadow:0 0 15px #aaa; }
        .thumb { width:180px; max-height:240px; object-fit:contain; margin:10px; border-radius:8px; background:#111; }
        .photos { display:flex; justify-content:center; flex-wrap:wrap; align-items:flex-start; gap:12px; }
        .asset { display:flex; flex-direction:column; align-items:center; min-width:180px; }
        .pending { width:180px; height:180px; display:flex; align-items:center; justify-content:center; background:#e5e7eb; color:#555; border-radius:8px; font-weight:bold; }
        a { display:inline-block; margin:12px 6px; padding:12px 20px; background:#2196F3; color:white; text-decoration:none; border-radius:8px; }
        #upload-state { font-weight:bold; margin:16px; }
    </style>
</head>
<body>
    <h1>Phomoria Gallery</h1>
    <h3>{{ $session->session_code }}</h3>
    @php
        $publicParts = explode('-', $session->session_code);
        $hasPublicId = count($publicParts) === 3
            && preg_match('/^[a-f0-9]{8}$/', $publicParts[0])
            && preg_match('/^\\d{8}$/', $publicParts[1])
            && preg_match('/^\\d{6}$/', $publicParts[2]);
        $galleryBaseUrl = $hasPublicId
            ? url('/gallery/' . $publicParts[0] . '/' . $publicParts[1] . '/' . $publicParts[2])
            : url('/gallery/' . $session->session_code);
        $uploadedAssetCount = $session->assets->where('status', 'UPLOADED')->count();
    @endphp
    <div id="upload-state">{{ $session->status === 'COMPLETED' ? 'UPLOAD SELESAI' : ($uploadedAssetCount > 0 ? 'UPLOAD SELESAI SEBAGIAN' : 'UPLOAD SEDANG BERLANGSUNG') }}</div>

    @php
        $photosByFilename = $session->photos->keyBy('filename');
        $resultAsset = $session->assets->firstWhere('asset_type', 'result');
        $result = $resultAsset ? $photosByFilename->get($resultAsset->filename) : $session->photos->firstWhere('is_result', true);
        $liveAsset = $session->assets->firstWhere('asset_type', 'live_photo');
        $livePhoto = $liveAsset ? $photosByFilename->get($liveAsset->filename) : $session->photos->firstWhere('is_live_photo', true);
    @endphp

    <section id="result-area">
        @if($result)
            <img class="result" src="{{ $result->url }}" alt="Hasil foto">
        @elseif($resultAsset)
            <div class="pending">LOADING UPLOAD</div>
        @endif
    </section>

    @if($liveAsset || $livePhoto)
        <h2>LIVE PHOTO</h2>
        <section id="live-area">
            @if($livePhoto)
                <video class="result" controls playsinline preload="metadata"><source src="{{ $livePhoto->url }}" type="video/mp4"></video>
            @else
                <div class="pending">LOADING UPLOAD</div>
            @endif
        </section>
    @endif

    @php $gifAsset = $session->assets->firstWhere('asset_type', 'gif'); @endphp
    @if($gifAsset)
        <h2>ANIMATION</h2>
        @php $gifPhoto = $photosByFilename->get($gifAsset->filename); @endphp
        <section id="gif-area">
            @if($gifPhoto)
                <img class="result" src="{{ $gifPhoto->url }}" alt="Animation">
            @else
                <div class="pending">LOADING UPLOAD</div>
            @endif
        </section>
    @endif

    <hr>
    <div class="photos" id="photos-area">
        @foreach($session->assets->where('asset_type', 'photo')->sortBy('sort_order') as $asset)
            @php $photo = $photosByFilename->get($asset->filename); @endphp
            <div class="asset">
                @if($photo)
                    <img class="thumb" src="{{ $photo->url }}" alt="{{ $photo->original_name }}">
                @else
                    <div class="pending">LOADING UPLOAD</div>
                @endif
            </div>
        @endforeach
        @if($session->assets->isEmpty())
            @foreach($session->photos->where('is_result', false)->where('is_live_photo', false) as $photo)
                <img class="thumb" src="{{ $photo->url }}" alt="{{ $photo->original_name }}">
            @endforeach
        @endif
    </div>

    @if($result)
        <div style="margin-top:25px;">
            <a href="{{ $galleryBaseUrl . '/download-result' }}">Download Result</a>
            <a href="{{ $galleryBaseUrl . '/download' }}" style="background:#4CAF50;">Download Semua Foto (ZIP)</a>
        </div>
    @endif

    @if($session->status !== 'COMPLETED')
    <script>
        (function pollUploadStatus() {
            fetch(@json($galleryBaseUrl . '/status'), {cache: 'no-store'})
                .then(response => response.ok ? response.json() : Promise.reject())
                .then(data => {
                    const state = document.getElementById('upload-state');
                    const hasUploadedAssets = Array.isArray(data.assets)
                        && data.assets.some(asset => asset.status === 'UPLOADED');
                    if (state) state.textContent = data.status === 'COMPLETED'
                        ? 'UPLOAD SELESAI'
                        : (hasUploadedAssets ? 'UPLOAD SELESAI SEBAGIAN' : 'UPLOAD SEDANG BERLANGSUNG');
                    if (data.status !== 'COMPLETED') setTimeout(() => window.location.reload(), 5000);
                    else window.location.reload();
                })
                .catch(() => setTimeout(pollUploadStatus, 8000));
        })();
    </script>
    @endif
</body>
</html>
