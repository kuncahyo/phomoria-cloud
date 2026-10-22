@extends('layouts.admin')

@section('title', 'Harga Perangkat - Phomoria Cloud')

@section('styles')
<style>
.page{padding:42px 0 90px}.top{margin-bottom:25px}.eyebrow{color:#888890;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:9px}h1{margin:0 0 8px;font-size:38px}.sub{margin:0;color:#9999a2;line-height:1.6}.select{margin:22px 0;padding:15px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025);max-width:520px}.select label,.field label{display:block;margin-bottom:8px;color:#888890;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase}.select select,.field input{width:100%;min-height:45px;padding:0 12px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:#151518;color:#fff}.notice{margin:18px 0;padding:14px 17px;border-radius:12px;background:rgba(120,220,150,.07);color:#b9edc7}.error{margin-top:7px;color:#ffaaaa;font-size:12px}.device-list{display:grid;gap:13px}.device{display:grid;grid-template-columns:minmax(180px,1fr) minmax(220px,360px) auto;align-items:end;gap:18px;padding:19px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025)}.device-name{font-weight:700;color:#fff;overflow-wrap:anywhere}.device-meta{margin-top:6px;color:#777780;font-size:11px}.field{min-width:0}.field small{display:block;margin-top:7px;color:#777780;font-size:11px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:45px;padding:0 18px;border:0;border-radius:999px;background:#fff;color:#111;font-size:12px;font-weight:700;cursor:pointer}.empty{padding:50px;border:1px dashed rgba(255,255,255,.12);border-radius:16px;color:#777780;text-align:center}@media(max-width:760px){.device{grid-template-columns:1fr}.button{width:100%}h1{font-size:30px}}
</style>
@endsection

@section('content')
<div class="page">
    <div class="top">
        <div class="eyebrow">Cloud Admin</div>
        <h1>Harga Perangkat</h1>
        <p class="sub">Atur nominal pembayaran untuk setiap sesi foto pada masing-masing perangkat. Harga ditetapkan oleh pemilik akun dan dapat berbeda antarperangkat.</p>
    </div>

    @if ($isSuperAdmin)
        <form method="GET" action="{{ route('admin.devices.pricing') }}" class="select">
            <label for="user_id">Pemilik akun</label>
            <select id="user_id" name="user_id" onchange="this.form.submit()">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($targetUser->id === $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif

    @if ($devices->isEmpty())
        <div class="empty">Belum ada perangkat terdaftar untuk akun ini.</div>
    @else
        <div class="device-list">
            @foreach ($devices as $device)
                <form class="device" method="POST" action="{{ route('admin.devices.pricing.update', array_merge(['device' => $device->id], $isSuperAdmin ? ['user_id' => $targetUser->id] : [])) }}">
                    @csrf
                    @method('PUT')
                    <div>
                        <div class="device-name">{{ $device->computer_name }}</div>
                        <div class="device-meta">{{ $device->device_uuid }}</div>
                        <div class="device-meta">{{ $device->status }}</div>
                    </div>
                    <div class="field">
                        <label for="price-{{ $device->id }}">Harga per sesi (rupiah)</label>
                        <input id="price-{{ $device->id }}" type="number" name="session_price" min="1" step="1" inputmode="numeric" value="{{ old('session_price', $device->session_price) }}" placeholder="Contoh: 15000">
                        <small>Kosongkan jika harga belum ditentukan dan pembayaran belum boleh digunakan.</small>
                        @error('session_price')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button class="button" type="submit">Simpan Harga</button>
                </form>
            @endforeach
        </div>
    @endif
</div>
@endsection
