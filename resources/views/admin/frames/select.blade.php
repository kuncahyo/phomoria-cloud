@extends('layouts.admin')

@section('title', 'Pilih Frame - Phomoria Cloud')

@section('styles')
<style>
.select-page{padding:42px 0 90px}.heading{margin-bottom:28px}.eyebrow{margin-bottom:9px;color:#888890;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase}h1{margin:0 0 9px;font-size:38px;letter-spacing:-1px}.subtitle{margin:0;color:#9999a2;line-height:1.6}.notice{margin-bottom:22px;padding:14px 17px;border-radius:13px;border:1px solid rgba(120,220,150,.2);background:rgba(120,220,150,.07);color:#b9edc7;font-size:13px}.selector-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:28px}.selector-card{padding:17px;border:1px solid rgba(255,255,255,.09);border-radius:16px;background:rgba(255,255,255,.025)}.selector-card label{display:block;margin-bottom:8px;color:#777780;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase}.selector-card select{width:100%;min-height:46px;padding:0 13px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:#151518;color:#fff;font:inherit;outline:none}.selector-card select:focus{border-color:rgba(255,255,255,.35)}.section-title{display:flex;align-items:flex-end;justify-content:space-between;gap:15px;margin:30px 0 13px}.section-title h2{margin:0;font-size:19px}.section-title p{margin:0;color:#777780;font-size:12px}.current-frames{display:flex;gap:12px;overflow-x:auto;padding:3px 2px 10px}.current-card{flex:0 0 150px;overflow:hidden;border:1px solid rgba(120,220,150,.25);border-radius:14px;background:rgba(120,220,150,.045)}.current-image{height:120px;display:flex;align-items:center;justify-content:center;padding:7px;background-color:#18181b;background-image:linear-gradient(45deg,#222225 25%,transparent 25%),linear-gradient(-45deg,#222225 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#222225 75%),linear-gradient(-45deg,transparent 75%,#222225 75%);background-size:16px 16px;background-position:0 0,0 8px,8px -8px,-8px 0}.current-image img{max-width:100%;max-height:100%;object-fit:contain}.current-name{padding:10px 11px;color:#ddd;font-size:12px;font-weight:650}.frame-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}.frame-option{position:relative}.frame-option input{position:absolute;opacity:0;pointer-events:none}.frame-label{display:block;overflow:hidden;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025);cursor:pointer;transition:.18s ease}.frame-label:hover{border-color:rgba(255,255,255,.25);transform:translateY(-1px)}.frame-option input:checked+.frame-label{border-color:rgba(80,220,130,.65);background:rgba(80,220,130,.07)}.frame-preview{height:170px;display:flex;align-items:center;justify-content:center;padding:9px;background-color:#18181b;background-image:linear-gradient(45deg,#222225 25%,transparent 25%),linear-gradient(-45deg,#222225 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#222225 75%),linear-gradient(-45deg,transparent 75%,#222225 75%);background-size:18px 18px;background-position:0 0,0 9px,9px -9px,-9px 0}.frame-preview img{max-width:100%;max-height:100%;object-fit:contain}.frame-info{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 13px}.frame-name{color:#dddde2;font-size:13px;font-weight:650}.frame-toggle{display:inline-flex;width:22px;height:22px;align-items:center;justify-content:center;border:2px solid #66666e;border-radius:50%;background:#66666e;color:transparent;font-size:12px;flex:0 0 auto}.frame-option input:checked+.frame-label .frame-toggle{border-color:#49d47a;background:#49d47a;color:#07130b}.empty{padding:38px 20px;border:1px dashed rgba(255,255,255,.12);border-radius:16px;color:#777780;text-align:center}.actions{position:sticky;bottom:18px;display:flex;justify-content:flex-end;margin-top:28px}.save-button{min-height:50px;padding:0 25px;border:0;border-radius:999px;background:#fff;color:#111114;font-weight:750;cursor:pointer;box-shadow:0 12px 35px rgba(0,0,0,.35)}.save-button:hover{background:#ededf0}@media(max-width:760px){.selector-grid{grid-template-columns:1fr}.frame-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:470px){.frame-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="select-page">
    <div class="heading">
        <div class="eyebrow">Device Configuration</div>
        <h1>Pilih Frame</h1>
        <p class="subtitle">Pilih device lalu tentukan frame yang boleh digunakan oleh device tersebut.</p>
    </div>

    @if (session('success'))
        <div class="notice">{{ session('success') }}</div>
    @endif

    @if ($isSuperAdmin)
        <form method="GET" action="{{ route('admin.frames.select') }}" class="selector-card" style="margin-bottom:14px;">
            <label for="user_id">Franchise / User</label>
            <select id="user_id" name="user_id" onchange="this.form.submit()">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($targetUser->id === $user->id)>
                        {{ $user->name }} — {{ $user->email }}
                    </option>
                @endforeach
            </select>
        </form>
    @endif

    @if ($devices->isEmpty())
        <div class="empty">Belum ada device untuk {{ $targetUser->name }}.</div>
    @else
        <div class="selector-card" style="margin-bottom:28px;">
            <form method="GET" action="{{ route('admin.frames.select') }}">
                @if ($isSuperAdmin)
                    <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                @endif
                <label for="device_id">Device</label>
                <select id="device_id" name="device_id" onchange="this.form.submit()">
                    @foreach ($devices as $item)
                        <option value="{{ $item->id }}" @selected($device && $device->id === $item->id)>
                            {{ $item->computer_name }} @if($item->device_uuid) — {{ $item->device_uuid }} @endif
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($device)
            <div class="section-title">
                <div>
                    <h2>Frame pada {{ $device->computer_name }}</h2>
                    <p>Frame yang saat ini sudah diberikan ke device.</p>
                </div>
            </div>

            @if ($device->frames->isNotEmpty())
                <div class="current-frames">
                    @foreach ($device->frames()->where('frames.status', 'ACTIVE')->orderBy('frames.name')->get() as $frame)
                        <div class="current-card">
                            <div class="current-image">
                                <img src="{{ asset('storage/' . $frame->image_path) }}" alt="{{ $frame->name }}">
                            </div>
                            <div class="current-name">{{ $frame->name }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty">Belum ada frame yang diberikan ke device ini.</div>
            @endif

            <form method="POST" action="{{ route('admin.frames.select.save') }}">
                @csrf
                @if ($isSuperAdmin)
                    <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                @endif
                <input type="hidden" name="device_id" value="{{ $device->id }}">

                <div class="section-title">
                    <div>
                        <h2>Semua Frame {{ $isSuperAdmin ? 'untuk '.$targetUser->name : '' }}</h2>
                        <p>Hijau = terpasang. Abu-abu = belum terpasang. Klik card untuk mengubah.</p>
                    </div>
                </div>

                @if ($frames->isEmpty())
                    <div class="empty">Belum ada frame aktif untuk user ini. <a href="{{ route('admin.frames.create', $isSuperAdmin ? ['user_id' => $targetUser->id] : []) }}" style="color:#fff;">Upload frame</a>.</div>
                @else
                    <div class="frame-grid">
                        @foreach ($frames as $frame)
                            <div class="frame-option">
                                <input
                                    id="frame-{{ $frame->id }}"
                                    type="checkbox"
                                    name="assignments[]"
                                    value="{{ $frame->id }}"
                                    @checked(in_array($frame->id, $assignedFrameIds, true))
                                >
                                <label class="frame-label" for="frame-{{ $frame->id }}">
                                    <div class="frame-preview">
                                        <img src="{{ asset('storage/' . $frame->image_path) }}" alt="{{ $frame->name }}">
                                    </div>
                                    <div class="frame-info">
                                        <span class="frame-name">{{ $frame->name }}</span>
                                        <span class="frame-toggle">✓</span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="actions">
                    <button type="submit" class="save-button">Simpan Perubahan</button>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection
