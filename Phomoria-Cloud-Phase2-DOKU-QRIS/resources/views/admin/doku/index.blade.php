@extends('layouts.admin')
@section('title', 'DOKU Payment')
@section('content')
<style>
.page{padding:54px 0 80px}.muted{color:#8d8d96}.card{background:#141416;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:24px;margin-bottom:18px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:flex;flex-direction:column;gap:7px;margin-bottom:15px}.field label{font-size:12px;color:#aaaab2}.field input,.field select,.field textarea{width:100%;background:#0e0e10;border:1px solid #2a2a2e;border-radius:9px;color:#fff;padding:11px 12px}.field textarea{min-height:130px;font-family:monospace;font-size:12px}.button{border:0;border-radius:9px;background:#fff;color:#111;padding:11px 18px;font-weight:700;cursor:pointer}.error{background:#3a1515;border:1px solid #713333;padding:12px;border-radius:9px;margin-bottom:15px}.success{background:#12351f;border:1px solid #285f3c;padding:12px;border-radius:9px;margin-bottom:15px}.badge{font-size:11px;color:#aaa}.test-link{color:#fff}
</style>
<div class="page">
<h1>DOKU Payment</h1><p class="muted">Konfigurasi DOKU di server. Secret dan private key disimpan terenkripsi di database.</p>
@if(session('success'))<div class="success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.doku.save') }}" class="card">@csrf
<div class="grid">
<div class="field"><label>Nama</label><input name="name" value="{{ old('name',$account->name ?? 'DOKU Sandbox') }}" required></div>
<div class="field"><label>Environment</label><select name="environment"><option value="sandbox" @selected(old('environment',$account->environment ?? 'sandbox')==='sandbox')>Sandbox</option><option value="production" @selected(old('environment',$account->environment ?? '')==='production')>Production</option></select></div>
<div class="field"><label>Client ID</label><input name="client_id" value="{{ old('client_id',$account->client_id ?? '') }}" required></div>
<div class="field"><label>Client Secret</label><input type="password" name="client_secret" value="{{ old('client_secret') }}" required autocomplete="new-password"></div>
<div class="field"><label>Merchant ID</label><input name="merchant_id" value="{{ old('merchant_id',$account->merchant_id ?? '') }}" required></div>
<div class="field"><label>Terminal ID</label><input name="terminal_id" value="{{ old('terminal_id',$account->terminal_id ?? '') }}" required></div>
<div class="field"><label>Postal Code</label><input name="postal_code" value="{{ old('postal_code',$account->postal_code ?? '') }}" required></div>
</div>
<div class="field"><label>Merchant Private Key (PEM)</label><textarea name="merchant_private_key" required placeholder="-----BEGIN PRIVATE KEY----- ...">{{ old('merchant_private_key') }}</textarea></div>
<div class="field"><label>DOKU Public Key (optional, stored encrypted)</label><textarea name="doku_public_key">{{ old('doku_public_key',$account->doku_public_key ?? '') }}</textarea></div>
<div class="field"><label>Merchant Public Key (optional, stored encrypted)</label><textarea name="merchant_public_key">{{ old('merchant_public_key',$account->merchant_public_key ?? '') }}</textarea></div>
<button class="button">Simpan Konfigurasi DOKU</button>
</form>
<div class="card"><h2>Uji Generate QRIS</h2><p class="muted">Nominal diambil dari <code>session_price</code> perangkat. Tidak menerima nominal dari browser.</p>
<form method="POST" action="{{ route('admin.doku.test.generate') }}">@csrf
<div class="field"><label>Perangkat</label><select name="device_id" required><option value="">Pilih perangkat</option>@foreach($devices as $device)<option value="{{ $device->id }}">{{ $device->computer_name }} — Rp {{ number_format($device->session_price ?? 0,0,',','.') }}</option>@endforeach</select></div>
<button class="button">Generate QRIS Sandbox</button></form></div>
</div>
@endsection
