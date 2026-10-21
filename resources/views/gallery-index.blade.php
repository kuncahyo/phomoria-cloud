<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
</head>
<body>

    <h2>Gallery</h2>

    <form method="GET" action="{{ route('gallery.index') }}">
        <label for="device_id">Device:</label>
        <select id="device_id" name="device_id" onchange="this.form.submit()">
            <option value="">Semua device</option>
            @foreach ($devices as $device)
                <option value="{{ $device->id }}"
                    @selected((string) $selectedDeviceId === (string) $device->id)>
                    {{ $device->computer_name ?: 'Device #' . $device->id }}
                </option>
            @endforeach
        </select>
    </form>

    <br>

    <table>
        <thead>
            <tr>
                <th align="left">Session</th>
                <th align="left">Device</th>
                <th align="left">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td>
                        <a href="{{ url('/gallery/' . $session->session_code) }}">
                            {{ $session->session_code }}
                        </a>
                    </td>
                    <td>{{ $session->device?->computer_name ?: 'Device tidak diketahui' }}<br></td>
                    <td>{{ $session->created_at?->format('d M Y, H:i') }}<br></td>
                </tr>
                <tr></tr<tr></tr><tr></tr<tr></tr>
            @empty
                <tr>
                    <td colspan="3">Belum ada sesi foto.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($sessions->hasPages())
        <br>
        {{ $sessions->links() }}
    @endif

</body>
</html>
