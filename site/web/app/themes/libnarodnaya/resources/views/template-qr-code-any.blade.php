{{--
  Template Name: QR Code Custom
--}}

<html lang="ru">
<head>
</head>
<body>
<div class="qr-codes">
    <div class="qr-code">
        <div class="qr-code__image">
            {!! \App\generate_qr_code($url = 'https://vk.com/narodnayabadvideo') !!}
        </div>
        <h2 class="qr-code__url">{!! $url !!}</h2>
    </div>
</div>
</body>
</html>
