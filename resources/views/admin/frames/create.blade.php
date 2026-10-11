@extends('layouts.admin')

@section('title', 'Upload Frame - Phomoria Cloud')

@section('styles')
<style>
.page{padding:42px 0 90px;max-width:820px;margin:auto}.eyebrow{color:#888890;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:9px}h1{margin:0 0 10px;font-size:38px}.sub{color:#9999a2;line-height:1.6;margin:0 0 28px}.card{padding:28px;border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(255,255,255,.025)}.field{margin-bottom:22px}.field label{display:block;margin-bottom:8px;color:#c9c9d0;font-size:13px;font-weight:600}.field input,.field select{width:100%;min-height:48px;padding:0 13px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:#151518;color:#fff;font:inherit}.field input[type=file]{padding:10px}.field select option{background:#151518}.hint{margin-top:7px;color:#707078;font-size:12px}.error{margin-bottom:20px;padding:14px;border:1px solid rgba(255,80,80,.2);border-radius:12px;background:rgba(255,80,80,.07);color:#ffb4b4;font-size:13px}.preview{display:none;margin-top:18px;padding:15px;border:1px solid rgba(255,255,255,.08);border-radius:14px;text-align:center;background:#18181b}.preview img{max-width:100%;max-height:480px}.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:28px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 21px;border-radius:999px;font-weight:700;cursor:pointer}.secondary{border:1px solid rgba(255,255,255,.14);color:#bbb;background:transparent}.primary{border:0;background:#fff;color:#111}
</style>
@endsection

@section('content')
<div class="page">
    <div class="eyebrow">Cloud Admin</div>
    <h1>Upload Frame</h1>
    <p class="sub">Upload frame PNG dan tentukan franchise/user pemiliknya.</p>

    <div class="card">
        @if ($errors->any())
            <div class="error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('admin.frames.store') }}" enctype="multipart/form-data">
            @csrf

            @if ($isSuperAdmin)
                <div class="field">
                    <label for="user_id">Franchise / User Pemilik</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Pilih franchise / user</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('user_id', optional($targetUser)->id) === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    <div class="hint">Frame menjadi milik user ini dan hanya bisa diberikan ke device miliknya.</div>
                </div>
            @endif

            <div class="field">
                <label for="name">Nama Frame</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="field">
                <label for="category">Kategori</label>
                <select id="category" name="category" required>
                    <option value="">Pilih kategori</option>
                    <option value="standar" @selected(old('category') === 'standar')>Standar</option>
                    <option value="split" @selected(old('category') === 'split')>Split</option>
                </select>
            </div>

            <div class="field">
                <label for="image">PNG Frame</label>
                <input id="image" type="file" name="image" accept="image/png" required>
                <div class="hint">Gunakan PNG dengan transparansi sesuai kebutuhan frame photobooth.</div>
                <div id="preview" class="preview"><img id="previewImage" alt="Preview"></div>
            </div>

            <div class="actions">
                <a href="{{ route('admin.frames.index', $isSuperAdmin && $targetUser ? ['user_id' => $targetUser->id] : []) }}" class="button secondary">Batal</a>
                <button type="submit" class="button primary">Upload Frame</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('image').addEventListener('change', function () {
    const file = this.files[0], preview = document.getElementById('preview'), image = document.getElementById('previewImage');
    if (!file) { preview.style.display = 'none'; return; }
    image.src = URL.createObjectURL(file);
    preview.style.display = 'block';
});
</script>
@endsection
