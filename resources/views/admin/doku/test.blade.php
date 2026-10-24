@extends('layouts.admin')
@section('title', 'Uji DOKU Checkout')
@section('content')
<style>
.checkout-test{max-width:700px;margin:50px auto;padding:28px;border:1px solid rgba(255,255,255,.1);border-radius:16px;background:#141416}.checkout-muted{color:#92929b;line-height:1.6}.checkout-status{font-size:20px;font-weight:800;margin:18px 0}.checkout-link{display:inline-flex;padding:13px 20px;border-radius:10px;background:#fff;color:#111;text-decoration:none;font-weight:800;margin:12px 0}.checkout-link[aria-disabled="true"]{opacity:.45;pointer-events:none}
</style>
<div class="checkout-test">
    <h1>DOKU Checkout Sandbox</h1>
    <p class="checkout-muted">Invoice: {{ $transaction->merchant_reference }}</p>
    <h2>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</h2>
    <div class="checkout-status" id="payment-status">{{ $transaction->status }}</div>
    <p class="checkout-muted">Klik tombol di bawah untuk membuka halaman pembayaran yang di-host oleh DOKU. Status PAID hanya ditetapkan setelah notifikasi terverifikasi atau Check Status API mengonfirmasi keberhasilan.</p>
    <a class="checkout-link" href="{{ $transaction->checkout_url ?: '#' }}" target="_blank" rel="noopener" @if(!$transaction->checkout_url) aria-disabled="true" @endif>Buka DOKU Checkout</a>
    <p class="checkout-muted" id="last-checked">Status diperbarui otomatis setiap 8 detik setelah batas pengecekan DOKU.</p>
    <a href="{{ route('admin.doku.index') }}" class="checkout-muted">← Kembali ke konfigurasi</a>
</div>
@endsection
@section('scripts')
<script>
const statusNode=document.getElementById('payment-status');
const statusUrl=@json(route('admin.doku.status',$transaction->public_id));
async function refreshStatus(){try{const response=await fetch(statusUrl,{headers:{'Accept':'application/json'}});const data=await response.json();statusNode.textContent=data.status;document.getElementById('last-checked').textContent='Terakhir diperiksa: '+new Date().toLocaleTimeString();if(['PAID','EXPIRED','CANCELLED'].includes(data.status))return;}catch(e){}setTimeout(refreshStatus,60000);}
if(@json($transaction->status)==='PENDING')setTimeout(refreshStatus,60000);
</script>
@endsection
