<!DOCTYPE html>
<html>
<head>
    <title>Phomoria Gallery</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; text-align: center; margin: 40px; }
        .result { width: 500px; max-width: 100%; border-radius: 12px; box-shadow: 0 0 15px #aaa; }
        .thumb { width: 180px; max-height: 240px; object-fit: contain; margin: 10px; border-radius: 8px; box-shadow: 0 0 10px #bbb; background: #111; }
        .photos { display: flex; justify-content: center; flex-wrap: wrap; align-items: flex-start; }
        a { display: inline-block; margin-top: 20px; padding: 12px 25px; background: #2196F3; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Phomoria Gallery</h1>
    <h3>{{ $session->session_code }}</h3>
    @php
        $result = $session->photos->where('is_result', true)->first();
        $livePhoto = $session->photos->firstWhere('is_live_photo', true);
    @endphp

    @if($result)
        <img class="result" src="{{ $result->url }}" alt="Hasil foto">
    @endif

    @if($livePhoto)
        <h2>LIVE PHOTO</h2>
        <video class="result" controls playsinline preload="metadata">
            <source src="{{ $livePhoto->url }}" type="video/mp4">
            Browser tidak mendukung pemutaran video.
        </video>
    @endif

    <hr>
    <div class="photos">
        @foreach($session->photos as $photo)
            @if(!$photo->is_result && !$photo->is_live_photo)
                <img class="thumb" src="{{ $photo->url }}" alt="{{ $photo->original_name }}">
            @endif
        @endforeach
    </div>

    @if($result)
        <div style="margin-top:25px;">
            <a href="{{ route('gallery.download.result', $session->session_code) }}">Download Result</a>
            <a href="{{ route('gallery.download', $session->session_code) }}" style="margin-left:10px;background:#4CAF50;">Download Semua Foto (ZIP)</a>
        </div>
    @endif
</body>
</html>
