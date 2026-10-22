@extends('layouts.admin')
@section('title', 'Uji QRIS DOKU')
@section('content')
<style>
.page{padding:54px 0 80px}.card{max-width:680px;margin:0 auto;background:#141416;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:28px;text-align:center}.qr{display:flex;justify-content:center;margin:25px 0}.qr canvas,.qr img{background:#fff;padding:16px;border-radius:10px}.muted{color:#8d8d96}.status{font-size:20px;font-weight:800;margin:12px 0}.back{color:#aaa}
</style>
<div class="page"><div class="card"><h1>QRIS DOKU Sandbox</h1><p class="muted">{{ $transaction->merchant_reference }}</p><h2>Rp {{ number_format($transaction->amount,0,',','.') }}</h2><div id="qrcode" class="qr"></div><div id="status" class="status">{{ $transaction->status }}</div><p class="muted">Scan QRIS menggunakan skenario pembayaran sandbox DOKU.</p><a class="back" href="{{ route('admin.doku.index') }}">← Kembali</a></div></div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), {text:@json($transaction->qr_content), width:280, height:280, correctLevel:QRCode.CorrectLevel.M});
const status=document.getElementById('status');
async function poll(){try{const r=await fetch(@json(route('admin.doku.status',$transaction->public_id)),{headers:{'Accept':'application/json'}});const j=await r.json();status.textContent=j.status;if(j.status==='PENDING')setTimeout(poll,5000);}catch(e){setTimeout(poll,7000)}}
if(@json($transaction->status)==='PENDING')setTimeout(poll,3000);
</script>
@endsection
