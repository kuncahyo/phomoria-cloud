<!DOCTYPE html>

<html>

<head>

    <title>Phomoria Gallery</title>

    <style>

        body{

            font-family:Arial;

            background:#f5f5f5;

            text-align:center;

            margin:40px;

        }

        .result{

            width:500px;

            max-width:100%;

            border-radius:12px;

            box-shadow:0 0 15px #aaa;

        }

        .thumb{

            width:180px;

            margin:10px;

            border-radius:8px;

            box-shadow:0 0 10px #bbb;

        }

        .photos{

            display:flex;

            justify-content:center;

            flex-wrap:wrap;

        }

        a{

            display:inline-block;

            margin-top:20px;

            padding:12px 25px;

            background:#2196F3;

            color:white;

            text-decoration:none;

            border-radius:8px;

        }

    </style>

</head>

<body>

    <h1>

    Phomoria Gallery

    </h1>

    <h3>

    {{ $session->session_code }}

    </h3>

@php

$result =
$session->photos
        ->where(
            "is_result",
            true
        )
        ->first();

@endphp

@if($result)

<img

class="result"

src="{{ $result->url }}">

@endif

<hr>

<div class="photos">

@foreach($session->photos as $photo)

@if(!$photo->is_result)

<img

class="thumb"

src="{{ $photo->url }}">

@endif

@endforeach

</div>

@if($result)

<div style="margin-top:25px;">

<a
href="{{ $result->url }}"
download>

Download Result

</a>

<a
href="{{ url('/gallery/'.$session->session_code.'/download') }}"
style="margin-left:10px;background:#4CAF50;">

Download Semua Foto (.ZIP)

</a>

</div>

@endif

</body>

</html>