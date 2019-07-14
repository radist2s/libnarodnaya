{{--
  Template Name: QR Codes Generator
--}}

<html lang="ru">
<head>
    <style>
        @media print {
            div{
                page-break-inside: avoid;
            }
        }

        .qr-codes {
            display: flex;
            flex-wrap: wrap;
            flex-grow: 0;
            justify-content: center;
        }

        .book-qr-code {
            display: block;
            padding: 0.1cm;
            margin: 0;
            width: 3cm;
            text-align: center;
            border: 1px solid gray;
        }

        .book-qr-code__image {
            display: block;
            z-index: -1;
            position: relative;
            margin: 0 auto 0.3em;
            max-width: 100%;
            width: 100%;
            height: auto;
            transform: scale(1.1);
        }

        .book-qr-code__title,
        .book-qr-code__url {
            margin: 0;
            font-size: 0.2cm;
            font-family: sans-serif;
            font-weight: normal;
        }

        .book-qr-code__url {
            font-size: 0.3cm;
        }

        .book-qr-code__url strong {
            font-weight: normal;
        }
    </style>
</head>
</html>
<body>
<div class="qr-codes">
    @foreach(\App\Controllers\TemplateQrCodesGenerator::generate_book_qr_codes() as $book_url => $qr_code_data)

        <div class="book-qr-code">
            <div class="book-qr-code__image">
                {!! $qr_code_data['image'] !!}
            </div>
            <h1 class="book-qr-code__title">{!! $qr_code_data['title'] !!}</h1>
            <h2 class="book-qr-code__url">{!! $qr_code_data['formatted_url'] !!}</h2>
        </div>
    @endforeach
</div>
</body>
