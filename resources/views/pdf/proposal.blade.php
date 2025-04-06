<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif: {{ $proposal->number }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #ffffff;
            line-height: 1.6;
            color: #2d3748;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #2c5282;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #1a365d;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .company-logo img {
            max-width: 180px;
            height: auto;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .info-box {
            flex: 1;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin: 0 10px;
        }
        .info-title {
            font-weight: 600;
            color: #2c5282;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .info-value {
            font-size: 13px;
            margin-bottom: 8px;
            color: #4a5568;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        th {
            background: #2c5282;
            color: white;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .total-row td {
            font-weight: 700;
            background: #edf2f7;
            font-size: 15px;
            color: #2c5282;
        }
        .footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 2px solid #e2e8f0;
        }
        .terms {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>TEKLİF FORMU</h1>
                <div style="color: #718096;">{{ $proposal->number }}</div>
            </div>
            <div class="company-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Şirket Logosu">
            </div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <div class="info-title">Teklif Detayları</div>
                <div class="info-value"><strong>Teklif No:</strong> {{ $proposal->number }}</div>
                <div class="info-value"><strong>Tarih:</strong> {{ $proposal->created_at->format('d/m/Y') }}</div>
                <div class="info-value"><strong>Geçerlilik:</strong> {{ $proposal->valid_until->format('d/m/Y') }}</div>
            </div>

            <div class="info-box">
                <div class="info-title">Müşteri Bilgileri</div>
                <div class="info-value"><strong>Firma:</strong> {{ $proposal->customer->name }}</div>
                <div class="info-value"><strong>Adres:</strong> {{ $proposal->customer->address }}</div>
                <div class="info-value"><strong>Tel:</strong> {{ $proposal->customer->phone }}</div>
                <div class="info-value"><strong>E-posta:</strong> {{ $proposal->customer->email }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Ürün/Hizmet</th>
                    <th>Açıklama</th>
                    <th>Miktar</th>
                    <th>Birim</th>
                    <th>Birim Fiyat</th>
                    <th>Toplam</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>₺{{ number_format($item->price, 2, ',', '.') }}</td>
                    <td>₺{{ number_format($item->getTotalPrice(), 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5">GENEL TOPLAM</td>
                    <td>₺{{ number_format($totalAmount, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="terms">
            <strong>Teklif Koşulları:</strong>
            <ul>
                <li>Bu teklif 30 gün süreyle geçerlidir.</li>
                <li>Fiyatlara KDV dahil değildir.</li>
                <li>Ödeme koşulları: Peşin</li>
            </ul>
        </div>

        <div class="footer">
            Bu teklif {{ config('app.name') }} tarafından {{ $proposal->created_at->format('d/m/Y H:i') }} tarihinde oluşturulmuştur.
            <br>
            <strong>{{ config('app.name') }}</strong>
        </div>
    </div>
</body>
</html>