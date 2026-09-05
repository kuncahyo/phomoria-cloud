<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frames - Phomoria Cloud</title>
</head>
<body>

<h1>Phomoria Cloud</h1>

<h2>Frame</h2>

@if (session('success'))
    <p>{{ session('success') }}</p>
@endif

<a href="{{ route('admin.frames.create') }}">
    Upload Frame
</a>

@if ($frames->isEmpty())
    <p>Belum ada frame.</p>
@else
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Ukuran</th>
                <th>Version</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($frames as $frame)
                <tr>
                    <td>{{ $frame->name }}</td>
                    <td>{{ $frame->category ?? '-' }}</td>
                    <td>{{ $frame->width }} × {{ $frame->height }}</td>
                    <td>{{ $frame->version }}</td>
                    <td>{{ $frame->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<hr>

<form method="POST" action="/logout">
    @csrf
    <button type="submit">Logout</button>
</form>

</body>
</html>