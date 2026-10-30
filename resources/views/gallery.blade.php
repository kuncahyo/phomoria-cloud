<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#211a35"><title>Phomoria Gallery</title>
<style>
:root{--ink:#211d2b;--muted:#777181;--paper:#f7f5fa;--line:#ebe7f0;--accent:#7254c7;--deep:#49367b}
*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
.shell{width:min(1080px,calc(100% - 32px));margin:auto;padding:28px 0 50px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px}.brand{font-weight:850;letter-spacing:.14em;font-size:14px}.tag,.hint{color:var(--muted);font-size:12px}
.hero{background:linear-gradient(135deg,#211a35,#49366f);color:white;border-radius:24px;padding:clamp(25px,5vw,48px);text-align:center;box-shadow:0 18px 50px #30234a20}.eyebrow{text-transform:uppercase;letter-spacing:.2em;font-size:11px;color:#d5c9f6}.hero h1{font-size:clamp(29px,5vw,46px);margin:12px 0 8px}.hero p{margin:0;color:#ddd5ec}.status{display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:8px 13px;border-radius:30px;background:#ffffff18;font-size:12px}.dot{width:7px;height:7px;border-radius:50%;background:#9ce2bd}
.section{margin-top:30px}.section-head{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-bottom:14px}.section h2{font-size:19px;margin:0}.feature{background:white;border:1px solid var(--line);border-radius:20px;padding:clamp(12px,3vw,22px);text-align:center}.feature img,.feature video{display:block;width:auto;max-width:100%;max-height:680px;margin:auto;border-radius:12px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:14px}.tile{background:white;border:1px solid var(--line);border-radius:16px;padding:10px;text-align:center;min-width:0}.tile img{width:100%;height:230px;object-fit:contain;background:#f0edf4;border-radius:10px}.tile p{font-size:12px;color:var(--muted);margin:9px 0 2px}
.actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:18px}.btn{display:inline-flex;justify-content:center;align-items:center;min-height:46px;padding:0 19px;border-radius:12px;background:var(--accent);color:white;text-decoration:none;font-weight:700;font-size:14px;transition:transform .15s,background .15s}.btn:hover{background:var(--deep);transform:translateY(-1px)}.btn.dark{background:#292435}.pending,.empty{padding:30px 12px;color:var(--muted);background:#f1eef5;border-radius:12px}footer{text-align:center;color:#9892a2;font-size:12px;margin-top:40px}
@media(max-width:600px){.shell{width:calc(100% - 22px);padding-top:18px}.top{margin-bottom:16px}.hero{border-radius:18px}.btn{width:100%}.actions{gap:8px}.section{margin-top:24px}.tile img{height:190px}}
</style></head><body><main class="shell">
<header class="top"><div class="brand">PHOMORIA</div><div class="tag">YOUR MOMENT, YOUR STORY</div></header>
@php
$parts=explode('-', $session->session_code);
$public=count($parts)===3 && preg_match('/^[a-f0-9]{8}$/',$parts[0]) && preg_match('/^\\d{8}$/',$parts[1]) && preg_match('/^\\d{6}$/',$parts[2]);
$base=$public?url('/gallery/'.$parts[0].'/'.$parts[1].'/'.$parts[2]):url('/gallery/'.$session->session_code);
$byName=$session->photos->keyBy('filename');
$resultAsset=$session->assets->firstWhere('asset_type','result');
$result=$resultAsset?$byName->get($resultAsset->filename):$session->photos->firstWhere('is_result',true);
$liveAsset=$session->assets->firstWhere('asset_type','live_photo');
$live=$liveAsset?$byName->get($liveAsset->filename):$session->photos->firstWhere('is_live_photo',true);
$gifAsset=$session->assets->firstWhere('asset_type','gif');
$gif=$gifAsset?$byName->get($gifAsset->filename):$session->photos->first(fn($p)=>strtolower(pathinfo($p->filename,PATHINFO_EXTENSION))==='gif');
$liveReady=$live && (!$liveAsset || $liveAsset->status==='UPLOADED');
$gifReady=$gif && (!$gifAsset || $gifAsset->status==='UPLOADED');
$ready=$session->assets->where('status','UPLOADED')->count();
@endphp
<section class="hero"><div class="eyebrow">A little moment to remember</div><h1>Your Photo Gallery</h1><p>{{ $session->frame_name ?: 'Captured with Phomoria' }}</p>
<div class="status"><span class="dot"></span><span id="upload-state">{{ $session->status==='COMPLETED'?'Semua file siap':($ready?'Beberapa file sudah tersedia':'Foto sedang diproses') }}</span></div></section>
@if($result)<section class="section"><div class="section-head"><h2>Foto Utama</h2><span class="hint">Hasil terbaik dari sesi ini</span></div><div class="feature"><img src="{{ $result->url }}" alt="Hasil foto Phomoria"><div class="actions"><a class="btn" href="{{ $base }}/download-result">↓ Download Foto</a></div></div></section>
@elseif($resultAsset)<section class="section"><div class="feature pending">Foto utama sedang diunggah.</div></section>@endif
@if($liveAsset || $live)<section class="section"><div class="section-head"><h2>Live Photo</h2><span class="hint">Momen bergerak dalam video</span></div><div class="feature">@if($liveReady)<video controls playsinline preload="metadata"><source src="{{ $live->url }}" type="video/mp4"></video><div class="actions"><a class="btn" href="{{ $base }}/download-live-photo">↓ Download Live Photo</a></div>@else<div class="pending">Live Photo sedang diunggah.</div>@endif</div></section>@endif
@if($gifAsset || $gif)<section class="section"><div class="section-head"><h2>Animated GIF</h2><span class="hint">Versi animasi fotomu</span></div><div class="feature">@if($gifReady)<img src="{{ $gif->url }}" alt="Animated GIF"><div class="actions"><a class="btn" href="{{ $base }}/download-gif">↓ Download GIF</a></div>@else<div class="pending">GIF sedang diunggah.</div>@endif</div></section>@endif
@php $originals=$session->assets->where('asset_type','photo')->where('status','UPLOADED')->sortBy('sort_order'); @endphp
@if($originals->isNotEmpty() || ($session->assets->isEmpty() && $session->photos->where('is_result',false)->where('is_live_photo',false)->isNotEmpty()))
<section class="section"><div class="section-head"><h2>Foto Lainnya</h2><span class="hint">Koleksi dari sesi ini</span></div><div class="grid">
@if($session->assets->isNotEmpty()) @foreach($originals as $asset) @php $photo=$byName->get($asset->filename); @endphp @if($photo)<div class="tile"><img src="{{ $photo->url }}" alt="{{ $photo->original_name }}"><p>{{ $photo->original_name }}</p></div>@endif @endforeach
@else @foreach($session->photos->where('is_result',false)->where('is_live_photo',false) as $photo) @if(strtolower(pathinfo($photo->filename,PATHINFO_EXTENSION))!=='gif')<div class="tile"><img src="{{ $photo->url }}" alt="{{ $photo->original_name }}"><p>{{ $photo->original_name }}</p></div>@endif @endforeach @endif
</div></section>@endif
<section class="section"><div class="actions">@if($session->status==='COMPLETED')<a class="btn dark" href="{{ $base }}/download">↓ Download Semua File (ZIP)</a>@else<span class="hint">Download semua file tersedia setelah proses upload selesai.</span>@endif</div></section>
<footer>Made with ♥ by Phomoria · {{ $session->session_code }}</footer></main>
@if($session->status!=='COMPLETED')<script>
(function poll(){fetch(@json($base.'/status'),{cache:'no-store'}).then(r=>r.ok?r.json():Promise.reject()).then(data=>{
const state=document.getElementById('upload-state'),count=Array.isArray(data.assets)?data.assets.filter(a=>a.status==='UPLOADED').length:0;
if(state)state.textContent=data.status==='COMPLETED'?'Semua file siap':(count?'Beberapa file sudah tersedia':'Foto sedang diproses');
if(data.status==='COMPLETED'||count!=={{ $ready }})window.location.reload();else setTimeout(poll,5000);
}).catch(()=>setTimeout(poll,8000));})();
</script>@endif
</body></html>