@extends('layouts.admin')
@section('title', 'DOKU Checkout')
@section('content')
<style>
.doku-page{padding:42px 0 80px}.doku-muted{color:#92929b;line-height:1.6}.doku-card{background:#141416;border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:24px;margin:18px 0}.doku-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.doku-field{display:flex;flex-direction:column;gap:7px;margin-bottom:15px}.doku-field label{font-size:11px;color:#aaaab2;font-weight:700;letter-spacing:.5px}.doku-field input,.doku-field select{width:100%;background:#0e0e10;border:1px solid #303036;border-radius:9px;color:#fff;padding:12px}.doku-button{border:0;border-radius:9px;background:#fff;color:#111;padding:12px 18px;font-weight:700;cursor:pointer}.doku-alert{padding:13px 16px;border-radius:10px;margin:14px 0}.doku-ok{background:#12351f;color:#c2f0d0}.doku-error{background:#3b1717;color:#ffd0d0}@media(max-width:700px){.doku-grid{grid-template-columns:1fr}}
</style>
<div class="doku-page">
    <h1>DOKU Checkout</h1>
    <p class="doku-muted">Hubungkan Phomoria Cloud dengan DOKU Hosted Checkout. Integrasi ini menggunakan Client ID dan Secret Key non-SNAP. Credential disimpan terenkripsi di database.</p>
    @if(session('success'))<div class="doku-alert doku-ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="doku-alert doku-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.doku.save') }}" class="doku-card">
        @csrf
        <div class="doku-grid">
            <div class="doku-field"><label>Nama Konfigurasi</label><input name="name" value="{{ old('name', $account->name ?? 'DOKU Checkout') }}" required></div>
            <div class="doku-field"><label>Environment</label><select name="environment"><option value="sandbox" @selected(old('environment', $account->environment ?? 'sandbox') === 'sandbox')>Sandbox</option><option value="production" @selected(old('environment', $account->environment ?? '') === 'production')>Production</option></select></div>
            <div class="doku-field"><label>Client ID</label><input name="client_id" value="{{ old('client_id', $account->client_id ?? '') }}" required autocomplete="off"></div>
            <div class="doku-field"><label>Secret Key</label><input type="password" name="client_secret" value="" placeholder="{{ $account && $account->client_secret ? 'Tersimpan — kosongkan untuk mempertahankan' : 'Masukkan Secret Key' }}" autocomplete="new-password" {{ !$account || !$account->client_secret ? 'required' : '' }}></div>
        </div>
        <button class="doku-button" type="submit">Simpan Konfigurasi</button>
        <p class="doku-muted" style="font-size:12px;margin-top:12px">Merchant ID, Terminal ID, Postal Code, dan Merchant Private Key tidak digunakan oleh DOKU Checkout API ini. Jangan masukkan credential SNAP di sini.</p>
    </form>

    <div class="doku-card">
        <h2>Uji Checkout Sandbox</h2>
        <p class="doku-muted">Sistem mengambil harga langsung dari session_price perangkat. Setelah transaksi dibuat, halaman uji akan menyediakan tautan menuju Hosted Checkout DOKU.</p>
        <form method="POST" action="{{ route('admin.doku.test.generate') }}">
            @csrf
            <div class="doku-field"><label>Perangkat</label><select name="device_id" required><option value="">Pilih perangkat</option>@foreach($devices as $device)<option value="{{ $device->id }}">{{ $device->computer_name }} — Rp {{ number_format($device->session_price ?? 0, 0, ',', '.') }}</option>@endforeach</select></div>
            <button class="doku-button" type="submit">Buat Checkout Sandbox</button>
        </form>
    </div>

    <div class="doku-card">
        <h3>Notification URL</h3>
        <p class="doku-muted">Daftarkan URL berikut pada pengaturan Notification URL DOKU Back Office untuk environment yang sesuai:</p>
        <code>{{ url('/api/webhooks/doku/checkout') }}</code>
    </div>
</div>
@endsection
