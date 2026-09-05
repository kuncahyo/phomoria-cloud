<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Frame - Phomoria Cloud</title>
</head>
<body>

<h1>Upload Frame</h1>

@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ route('admin.frames.store') }}"
      enctype="multipart/form-data">

    @csrf

    <div>
        <label for="name">Nama Frame</label>
        <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name') }}"
            required
        >
    </div>

    <div>
        <label for="category">Kategori</label>
        <input
            id="category"
            type="text"
            name="category"
            value="{{ old('category') }}"
        >
    </div>

    <div>
        <label for="image">PNG Frame</label>
        <input
            id="image"
            type="file"
            name="image"
            accept="image/png"
            required
        >
    </div>

    <button type="submit">Upload</button>

    <a href="{{ route('admin.frames.index') }}">
        Batal
    </a>

</form>

</body>
</html>