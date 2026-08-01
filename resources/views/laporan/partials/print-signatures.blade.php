@php
    $supervisor = $printIdentity['supervisor'];
    $printer = $printIdentity['printer'];
    $printedAt = $printIdentity['printed_at'];
@endphp
<div style="margin-top: 32px; page-break-inside: avoid; font-size: 9pt;">
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr>
                <td style="width: 45%; border: 0; padding: 0; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 4px;">Mengetahui,</p>
                    <p style="margin: 0;">
                        {{ $supervisor['jabatan'] ?: 'Jabatan: ______________________________' }}
                    </p>
                    <div style="height: 65px;"></div>
                    @if($supervisor['nama'])
                        <p style="margin: 0; font-weight: bold; text-decoration: underline;">{{ $supervisor['nama'] }}</p>
                    @else
                        <p style="margin: 0;">Nama: _________________________________</p>
                    @endif
                    <p style="margin: 4px 0 0;">
                        {{ $supervisor['nip'] ? 'NIP. '.$supervisor['nip'] : 'NIP: __________________________________' }}
                    </p>
                </td>
                <td style="width: 10%; border: 0; padding: 0;"></td>
                <td style="width: 45%; border: 0; padding: 0; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 4px;">Kota Bogor, {{ $printedAt->translatedFormat('d F Y') }}</p>
                    <p style="margin: 0;">Dicetak oleh,</p>
                    <div style="height: 65px;"></div>
                    <p style="margin: 0; font-weight: bold; text-decoration: underline;">{{ $printer['nama'] }}</p>
                    <p style="margin: 4px 0 0;">Akun: {{ $printer['username'] }}</p>
                    <p style="margin: 4px 0 0;">Waktu cetak: {{ $printedAt->translatedFormat('H:i') }} WIB</p>
                </td>
            </tr>
        </table>
    </div>
