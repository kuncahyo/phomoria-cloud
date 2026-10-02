@extends('layouts.admin')

@section('title', 'Pilih Frame - Phomoria Cloud')

@section('styles')
<style>
    .select-page {
        padding: 45px 0 90px;
    }

    .heading {
        margin-bottom: 30px;
    }

    .eyebrow {
        margin-bottom: 9px;
        color: #888890;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 3px;
        text-transform: uppercase;
    }

    h1 {
        margin: 0 0 10px;
        font-size: 38px;
        letter-spacing: -1px;
    }

    .subtitle {
        margin: 0;
        color: #9999a2;
        line-height: 1.6;
    }

    .notice {
        margin-bottom: 24px;
        padding: 14px 17px;
        border-radius: 13px;
        border: 1px solid rgba(120,220,150,.2);
        background: rgba(120,220,150,.07);
        color: #b9edc7;
        font-size: 13px;
    }

    .device-card {
        margin-bottom: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.09);
        border-radius: 18px;
        background: rgba(255,255,255,.025);
    }

    .device-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 19px 22px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .device-name {
        color: #fff;
        font-size: 15px;
        font-weight: 700;
    }

    .device-meta {
        margin-top: 5px;
        color: #707078;
        font-size: 11px;
    }

    .device-status {
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.06);
        color: #a8a8b0;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .frame-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 12px;
        padding: 18px;
    }

    .frame-option {
        position: relative;
    }

    .frame-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .frame-label {
        display: block;
        min-height: 82px;
        padding: 15px;
        border: 1px solid rgba(255,255,255,.09);
        border-radius: 13px;
        background: rgba(255,255,255,.025);
        cursor: pointer;
        transition: .18s ease;
    }

    .frame-label:hover {
        border-color: rgba(255,255,255,.22);
        background: rgba(255,255,255,.045);
    }

    .frame-option input:checked + .frame-label {
        border-color: rgba(255,255,255,.55);
        background: rgba(255,255,255,.09);
    }

    .frame-check {
        display: inline-flex;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        margin-right: 7px;
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 5px;
        color: transparent;
        vertical-align: -4px;
        font-size: 11px;
    }

    .frame-option input:checked + .frame-label .frame-check {
        border-color: #fff;
        background: #fff;
        color: #111;
    }

    .frame-name {
        color: #dddde2;
        font-size: 13px;
        font-weight: 650;
    }

    .frame-category {
        margin-top: 10px;
        color: #6f6f77;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .empty {
        padding: 45px 20px;
        border: 1px dashed rgba(255,255,255,.12);
        border-radius: 16px;
        color: #777780;
        text-align: center;
    }

    .actions {
        position: sticky;
        bottom: 18px;
        display: flex;
        justify-content: flex-end;
        margin-top: 28px;
    }

    .save-button {
        min-height: 50px;
        padding: 0 25px;
        border: 0;
        border-radius: 999px;
        background: #fff;
        color: #111114;
        font-weight: 750;
        cursor: pointer;
        box-shadow: 0 12px 35px rgba(0,0,0,.35);
    }

    .save-button:hover {
        background: #ededf0;
    }
</style>
@endsection

@section('content')
<div class="select-page">
    <div class="heading">
        <div class="eyebrow">Device Configuration</div>
        <h1>Pilih Frame</h1>
        <p class="subtitle">
            Tentukan frame yang tersedia untuk setiap perangkat.
            @if ($isAdmin)
                Sebagai admin, Anda dapat mengatur seluruh perangkat.
            @else
                Anda hanya dapat mengatur perangkat milik akun Anda.
            @endif
        </p>
    </div>

    @if (session('success'))
        <div class="notice">{{ session('success') }}</div>
    @endif

    @if ($devices->isEmpty())
        <div class="empty">
            Belum ada perangkat yang terdaftar pada akun ini.
        </div>
    @elseif ($frames->isEmpty())
        <div class="empty">
            Belum ada frame aktif yang tersedia.
            <br><br>
            <a href="{{ route('admin.frames.create') }}" style="color:#fff;">
                Upload frame
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('admin.frames.select.save') }}">
            @csrf

            @foreach ($devices as $device)
                <section class="device-card">
                    <div class="device-header">
                        <div>
                            <div class="device-name">
                                {{ $device->computer_name }}
                            </div>
                            <div class="device-meta">
                                {{ $device->device_uuid }}
                                @if ($device->windows_user)
                                    · {{ $device->windows_user }}
                                @endif
                            </div>
                        </div>

                        <div class="device-status">
                            {{ $device->status }}
                        </div>
                    </div>

                    <div class="frame-grid">
                        @foreach ($frames as $frame)
                            @php
                                $checked = in_array(
                                    $frame->id,
                                    $assignments[$device->id] ?? [],
                                    true
                                );
                            @endphp

                            <div class="frame-option">
                                <input
                                    id="device-{{ $device->id }}-frame-{{ $frame->id }}"
                                    type="checkbox"
                                    name="assignments[{{ $device->id }}][]"
                                    value="{{ $frame->id }}"
                                    @checked($checked)
                                >

                                <label
                                    class="frame-label"
                                    for="device-{{ $device->id }}-frame-{{ $frame->id }}"
                                >
                                    <span class="frame-check">✓</span>
                                    <span class="frame-name">
                                        {{ $frame->name }}
                                    </span>

                                    <div class="frame-category">
                                        {{ $frame->category ?: 'Tanpa kategori' }}
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="actions">
                <button type="submit" class="save-button">
                    Simpan Pengaturan Frame
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
