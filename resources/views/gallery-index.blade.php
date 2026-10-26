<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        form { display: flex; flex-wrap: wrap; align-items: end; gap: 12px; margin: 18px 0 24px; }
        label { display: block; margin-bottom: 5px; }
        select, input, button { padding: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .actions { display: flex; gap: 8px; }
    </style>
</head>
<body>
    <h2>Gallery</h2>

    <form method="GET" action="{{ route('gallery.index') }}">
        <div>
            <label for="device_id">Device</label>
            <select id="device_id" name="device_id">
                <option value="">Semua device</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}" @selected((string) $selectedDeviceId === (string) $device->id)>
                        {{ $device->computer_name ?: 'Device #' . $device->id }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from">Dari waktu</label>
            <input type="datetime-local" id="from" name="from" value="{{ $from }}">
        </div>
        <div>
            <label for="to">Sampai waktu</label>
            <input type="datetime-local" id="to" name="to" value="{{ $to }}">
        </div>
        <div class="actions">
            <button type="submit">Cari sesi</button>
            <a href="{{ route('gallery.index') }}">Reset</a>
        </div>
    </form>

    <p>{{ $sessions->total() }} sesi ditemukan.</p>

    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Device</th>
                <th>Waktu layanan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td><a href="{{ url('/gallery/' . $session->session_code) }}">{{ $session->session_code }}</a></td>
                    <td>{{ $session->device?->computer_name ?: 'Device tidak diketahui' }}</td>
                    <td>{{ $session->created_at?->format('d M Y, H:i:s') }}</td>
                    <td>{{ $session->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada sesi yang cocok dengan pencarian.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($sessions->hasPages())
        {{ $sessions->links() }}
    @endif
</body>
</html>
