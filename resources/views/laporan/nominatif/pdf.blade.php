<<<<<<< HEAD
<!DOCTYPE html>
<html>

<head>
    <title>Laporan Daftar Nominatif Mutasi {{ ucfirst($type) }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 5px 0 0;
            font-size: 11pt;
            font-weight: normal;
        }

        .meta {
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .meta table {
            width: 100%;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.data th,
        table.data td {
            border: 1px solid #999;
            padding: 6px;
        }

        table.data th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>DAFTAR NOMINATIF MUTASI {{ strtoupper($type) }} SISWA</h1>
        @if($user->role !== 'Sekolah')
            <h2>DINAS PENDIDIKAN KOTA</h2>
        @else
            <h2>{{ $user->sekolah->nama ?? 'SEKOLAH' }}</h2>
        @endif
    </div>

    <div class="meta">
        <table>
            <tr>
                <td width="150"><strong>Periode Laporan</strong></td>
                <td>: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y') }} -
                    {{ \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Jenis Mutasi</strong></td>
                <td>: {{ ucfirst($type) }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="70">Tanggal</th>
                <th>Nama Siswa</th>
                <th width="80">NISN</th>
                <th width="40">Kelas</th>
                <th>Sekolah Asal</th>
                <th>Sekolah Tujuan</th>
                <th width="70">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $item->nama_siswa }}</td>
                    <td class="text-center">{{ $item->nisn }}</td>
                    <td class="text-center">{{ $item->kelas }}</td>
                    <td>{{ $item->asal }}</td>
                    <td>{{ $item->tujuan }}</td>
                    <td class="text-center">{{ $item->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        Data tidak ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Kota Bogor, {{ now()->translatedFormat('j F Y') }}</p>
        <p>Dicetak oleh: {{ $user->nama_pengguna ?? $user->username }}</p>
    </div>
</body>

=======
<!DOCTYPE html>
<html>

<head>
    <title>Laporan Daftar Nominatif Mutasi {{ ucfirst($type) }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 5px 0 0;
            font-size: 11pt;
            font-weight: normal;
        }

        .meta {
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .meta table {
            width: 100%;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.data th,
        table.data td {
            border: 1px solid #999;
            padding: 6px;
        }

        table.data th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>DAFTAR NOMINATIF MUTASI {{ strtoupper($type) }} SISWA</h1>
        @if($user->role !== 'Sekolah')
            <h2>DINAS PENDIDIKAN KOTA</h2>
        @else
            <h2>{{ $user->sekolah->nama ?? 'SEKOLAH' }}</h2>
        @endif
    </div>

    <div class="meta">
        <table>
            <tr>
                <td width="150"><strong>Periode Laporan</strong></td>
                <td>: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y') }} -
                    {{ \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Jenis Mutasi</strong></td>
                <td>: {{ ucfirst($type) }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="70">Tanggal</th>
                <th>Nama Siswa</th>
                <th width="80">NISN</th>
                <th width="40">Kelas</th>
                <th>Sekolah Asal</th>
                <th>Sekolah Tujuan</th>
                <th width="70">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $item->nama_siswa }}</td>
                    <td class="text-center">{{ $item->nisn }}</td>
                    <td class="text-center">{{ $item->kelas }}</td>
                    <td>{{ $item->asal }}</td>
                    <td>{{ $item->tujuan }}</td>
                    <td class="text-center">{{ $item->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        Data tidak ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Kota Bogor, {{ now()->translatedFormat('j F Y') }}</p>
        <p>Dicetak oleh: {{ $user->nama_pengguna ?? $user->username }}</p>
    </div>
</body>

>>>>>>> 9466a07 (Upload project pertama kali)
</html>