<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi Mutasi</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11pt;
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
            font-size: 16pt;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 12pt;
            font-weight: normal;
        }
        .meta {
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .meta table {
            width: 100%;
        }
        .meta td {
            vertical-align: top;
        }
        .bg-blue { background-color: #e6f3ff; }
        .bg-orange { background-color: #fff7e6; }
        
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        table.data th, table.data td {
            border: 1px solid #999;
            padding: 8px;
        }
        table.data th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10pt;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN REKAPITULASI MUTASI SISWA JENJANG SMP</h1>
        @if(!in_array($user->role, ['Sekolah', 'Operator']))
            <h2>DINAS PENDIDIKAN KOTA</h2>
        @else
            <h2>{{ $user->sekolah->nama ?? 'SEKOLAH' }}</h2>
        @endif
    </div>

    <div class="meta">
        <table>
            <tr>
                <td width="150"><strong>Periode Laporan</strong></td>
                <td>: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y') }} - {{ \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: {{ now()->translatedFormat('j F Y H:i') }} WIB</td>
            </tr>
            <tr>
                <td><strong>Dicetak Oleh</strong></td>
                <td>: {{ $user->nama_pengguna ?? $user->username }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2" width="30">No</th>
                @if(in_array($user->role, ['Sekolah', 'Operator']))
                    <th rowspan="2">Periode Bulan</th>
                @else
                    <th rowspan="2">Nama Sekolah</th>
                @endif
                <th colspan="4" class="bg-blue">Mutasi Masuk</th>
                <th colspan="4" class="bg-orange">Mutasi Keluar</th>
                @if(!in_array($user->role, ['Sekolah', 'Operator']))
                    <th rowspan="2" width="40">Jml</th>
                @endif
            </tr>
            <tr>
                <!-- Masuk -->
                <th width="30" class="bg-blue">VII</th>
                <th width="30" class="bg-blue">VIII</th>
                <th width="30" class="bg-blue">IX</th>
                <th width="40" class="bg-blue">Total</th>
                <!-- Keluar -->
                <th width="30" class="bg-orange">VII</th>
                <th width="30" class="bg-orange">VIII</th>
                <th width="30" class="bg-orange">IX</th>
                <th width="40" class="bg-orange">Total</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalMasukVII_L = 0; $totalMasukVII_P = 0;
                $totalMasukVIII_L = 0; $totalMasukVIII_P = 0;
                $totalMasukIX_L = 0; $totalMasukIX_P = 0; 
                $totalMasukAll = 0;

                $totalKeluarVII_L = 0; $totalKeluarVII_P = 0;
                $totalKeluarVIII_L = 0; $totalKeluarVIII_P = 0;
                $totalKeluarIX_L = 0; $totalKeluarIX_P = 0; 
                $totalKeluarAll = 0;
            @endphp
            @forelse($data as $index => $item)
                @php
                    $totalMasukVII_L += $item['masuk_vii']['l'];
                    $totalMasukVII_P += $item['masuk_vii']['p'];
                    $totalMasukVIII_L += $item['masuk_viii']['l'];
                    $totalMasukVIII_P += $item['masuk_viii']['p'];
                    $totalMasukIX_L += $item['masuk_ix']['l'];
                    $totalMasukIX_P += $item['masuk_ix']['p'];
                    $totalMasukAll += $item['masuk'];
                    
                    $totalKeluarVII_L += $item['keluar_vii']['l'];
                    $totalKeluarVII_P += $item['keluar_vii']['p'];
                    $totalKeluarVIII_L += $item['keluar_viii']['l'];
                    $totalKeluarVIII_P += $item['keluar_viii']['p'];
                    $totalKeluarIX_L += $item['keluar_ix']['l'];
                    $totalKeluarIX_P += $item['keluar_ix']['p'];
                    $totalKeluarAll += $item['keluar'];
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if(in_array($user->role, ['Sekolah', 'Operator']))
                        <td>{{ $item['periode'] }}</td>
                    @else
                        <td>{!! html_entity_decode($item['nama_sekolah']) !!}</td>
                    @endif
                    
                    <!-- Masuk -->
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['masuk_vii']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['masuk_vii']['l'] }} P:{{ $item['masuk_vii']['p'] }}</div>
                    </td>
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['masuk_viii']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['masuk_viii']['l'] }} P:{{ $item['masuk_viii']['p'] }}</div>
                    </td>
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['masuk_ix']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['masuk_ix']['l'] }} P:{{ $item['masuk_ix']['p'] }}</div>
                    </td>
                    <td class="text-center bg-blue"><strong>{{ $item['masuk'] }}</strong></td>
                    
                    <!-- Keluar -->
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['keluar_vii']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['keluar_vii']['l'] }} P:{{ $item['keluar_vii']['p'] }}</div>
                    </td>
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['keluar_viii']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['keluar_viii']['l'] }} P:{{ $item['keluar_viii']['p'] }}</div>
                    </td>
                    <td class="text-center">
                        <div style="font-weight: bold">{{ $item['keluar_ix']['t'] }}</div>
                        <div style="font-size: 8pt; color: #555;">L:{{ $item['keluar_ix']['l'] }} P:{{ $item['keluar_ix']['p'] }}</div>
                    </td>
                    <td class="text-center bg-orange"><strong>{{ $item['keluar'] }}</strong></td>

                    @if(!in_array($user->role, ['Sekolah', 'Operator']))
                        <td class="text-center">
                            {{ $item['netto'] > 0 ? '+' . $item['netto'] : ($item['netto'] == 0 ? '-' : $item['netto']) }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ in_array($user->role, ['Sekolah', 'Operator']) ? 10 : 11 }}" class="text-center" style="padding: 20px;">
                        Data tidak ditemukan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAL</td>
                <!-- Masuk Totals -->
                <td class="text-center">
                    <div>{{ $totalMasukVII_L + $totalMasukVII_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalMasukVII_L }} P:{{ $totalMasukVII_P }}</div>
                </td>
                <td class="text-center">
                    <div>{{ $totalMasukVIII_L + $totalMasukVIII_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalMasukVIII_L }} P:{{ $totalMasukVIII_P }}</div>
                </td>
                <td class="text-center">
                    <div>{{ $totalMasukIX_L + $totalMasukIX_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalMasukIX_L }} P:{{ $totalMasukIX_P }}</div>
                </td>
                <td class="text-center bg-blue">{{ $totalMasukAll }}</td>
                <!-- Keluar Totals -->
                <td class="text-center">
                    <div>{{ $totalKeluarVII_L + $totalKeluarVII_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalKeluarVII_L }} P:{{ $totalKeluarVII_P }}</div>
                </td>
                <td class="text-center">
                    <div>{{ $totalKeluarVIII_L + $totalKeluarVIII_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalKeluarVIII_L }} P:{{ $totalKeluarVIII_P }}</div>
                </td>
                <td class="text-center">
                    <div>{{ $totalKeluarIX_L + $totalKeluarIX_P }}</div>
                    <div style="font-size: 7pt; font-weight: normal;">L:{{ $totalKeluarIX_L }} P:{{ $totalKeluarIX_P }}</div>
                </td>
                <td class="text-center bg-orange">{{ $totalKeluarAll }}</td>
                
                @if(!in_array($user->role, ['Sekolah', 'Operator']))
                    <td class="text-center">{{ $totalMasukAll - $totalKeluarAll }}</td>
                @endif
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Kota Bogor, {{ now()->translatedFormat('j F Y') }}</p>
    </div>
</body>
</html>
