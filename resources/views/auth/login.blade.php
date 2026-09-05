<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Phomoria Cloud</title>
</head>
<body>

<h1>Phomoria Cloud</h1>

<h2>Login Admin</h2>

@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div>
        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
        >
    </div>

    <div>
        <label for="password">Password</label>
        <input
            id="password"
            type="password"
            name="password"
            required
        >
    </div>

    <div>
        <label>
            <input type="checkbox" name="remember" value="1">
            Ingat saya
        </label>
    </div>

    <button type="submit">Login</button>
</form>

</body>
</html>