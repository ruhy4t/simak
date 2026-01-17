<<<<<<< HEAD
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
        .signature {
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN REKAPITULASI MUTASI SISWA JENJANG SMP</h1>
        <?php if($user->role !== 'Sekolah'): ?>
            <h2>DINAS PENDIDIKAN KOTA</h2>
        <?php else: ?>
            <h2><?php echo e($user->sekolah->nama ?? 'SEKOLAH'); ?></h2>
        <?php endif; ?>
    </div>

    <!-- Meta and Table sections hidden for brevity in replacement argument, assume unchanged parts remain -->
    <!-- I need to target specific chunks actually since they are far apart. -->
    <!-- Retrying with separate tool calls or multi_replace if supported, or just careful replacement. -->
    <!-- The tool replace_file_content is for SINGLE CONTIGUOUS block. I should use multi_replace_file_content. -->

    <div class="meta">
        <table>
            <tr>
                <td width="150"><strong>Periode Laporan</strong></td>
                <td>: <?php echo e(\Carbon\Carbon::parse($start_date)->translatedFormat('d F Y')); ?> - <?php echo e(\Carbon\Carbon::parse($end_date)->translatedFormat('d F Y')); ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: <?php echo e(now()->translatedFormat('d F Y H:i')); ?> WIB</td>
            </tr>
            <tr>
                <td><strong>Dicetak Oleh</strong></td>
                <td>: <?php echo e($user->nama_pengguna ?? $user->username); ?></td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No</th>
                <?php if($user->role === 'Sekolah'): ?>
                    <th>Periode Bulan</th>
                <?php else: ?>
                    <th>Nama Sekolah</th>
                <?php endif; ?>
                <th>Mutasi Masuk</th>
                <th>Mutasi Keluar</th>
                <?php if($user->role !== 'Sekolah'): ?>
                    <th>Jumlah</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
                                $totalMasuk = 0;
                $totalKeluar = 0; 
            ?>
            <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $totalMasuk += $item['masuk'];
                    $totalKeluar += $item['keluar'];
                ?>
                <tr>
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <?php if($user->role === 'Sekolah'): ?>
                        <td><?php echo e($item['periode']); ?></td>
                    <?php else: ?>
                        <td><?php echo e($item['nama_sekolah']); ?></td>
                    <?php endif; ?>
                    <td class="text-center bg-blue"><?php echo e($item['masuk']); ?></td>
                    <td class="text-center bg-orange"><?php echo e($item['keluar']); ?></td>
                    <?php if($user->role !== 'Sekolah'): ?>
                        <td class="text-center">
                            <?php echo e($item['netto'] > 0 ? '+' . $item['netto'] : $item['netto']); ?>

                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e($user->role === 'Sekolah' ? 4 : 5); ?>" class="text-center" style="padding: 20px;">
                        Data tidak ditemukan pada periode ini.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="<?php echo e($user->role === 'Sekolah' ? 2 : 2); ?>" class="text-right">TOTAL</td>
                <td class="text-center"><?php echo e($totalMasuk); ?></td>
                <td class="text-center"><?php echo e($totalKeluar); ?></td>
                <?php if($user->role !== 'Sekolah'): ?>
                    <td class="text-center"><?php echo e($totalMasuk - $totalKeluar); ?></td>
                <?php endif; ?>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Kota Bogor, <?php echo e(now()->translatedFormat('d F Y')); ?></p>
    </div>
</body>
</html>
=======
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
        .signature {
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN REKAPITULASI MUTASI SISWA JENJANG SMP</h1>
        <?php if($user->role !== 'Sekolah'): ?>
            <h2>DINAS PENDIDIKAN KOTA</h2>
        <?php else: ?>
            <h2><?php echo e($user->sekolah->nama ?? 'SEKOLAH'); ?></h2>
        <?php endif; ?>
    </div>

    <!-- Meta and Table sections hidden for brevity in replacement argument, assume unchanged parts remain -->
    <!-- I need to target specific chunks actually since they are far apart. -->
    <!-- Retrying with separate tool calls or multi_replace if supported, or just careful replacement. -->
    <!-- The tool replace_file_content is for SINGLE CONTIGUOUS block. I should use multi_replace_file_content. -->

    <div class="meta">
        <table>
            <tr>
                <td width="150"><strong>Periode Laporan</strong></td>
                <td>: <?php echo e(\Carbon\Carbon::parse($start_date)->translatedFormat('d F Y')); ?> - <?php echo e(\Carbon\Carbon::parse($end_date)->translatedFormat('d F Y')); ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: <?php echo e(now()->translatedFormat('d F Y H:i')); ?> WIB</td>
            </tr>
            <tr>
                <td><strong>Dicetak Oleh</strong></td>
                <td>: <?php echo e($user->nama_pengguna ?? $user->username); ?></td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="30">No</th>
                <?php if($user->role === 'Sekolah'): ?>
                    <th>Periode Bulan</th>
                <?php else: ?>
                    <th>Nama Sekolah</th>
                <?php endif; ?>
                <th>Mutasi Masuk</th>
                <th>Mutasi Keluar</th>
                <?php if($user->role !== 'Sekolah'): ?>
                    <th>Jumlah</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
                                $totalMasuk = 0;
                $totalKeluar = 0; 
            ?>
            <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $totalMasuk += $item['masuk'];
                    $totalKeluar += $item['keluar'];
                ?>
                <tr>
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <?php if($user->role === 'Sekolah'): ?>
                        <td><?php echo e($item['periode']); ?></td>
                    <?php else: ?>
                        <td><?php echo e($item['nama_sekolah']); ?></td>
                    <?php endif; ?>
                    <td class="text-center bg-blue"><?php echo e($item['masuk']); ?></td>
                    <td class="text-center bg-orange"><?php echo e($item['keluar']); ?></td>
                    <?php if($user->role !== 'Sekolah'): ?>
                        <td class="text-center">
                            <?php echo e($item['netto'] > 0 ? '+' . $item['netto'] : $item['netto']); ?>

                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e($user->role === 'Sekolah' ? 4 : 5); ?>" class="text-center" style="padding: 20px;">
                        Data tidak ditemukan pada periode ini.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="<?php echo e($user->role === 'Sekolah' ? 2 : 2); ?>" class="text-right">TOTAL</td>
                <td class="text-center"><?php echo e($totalMasuk); ?></td>
                <td class="text-center"><?php echo e($totalKeluar); ?></td>
                <?php if($user->role !== 'Sekolah'): ?>
                    <td class="text-center"><?php echo e($totalMasuk - $totalKeluar); ?></td>
                <?php endif; ?>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Kota Bogor, <?php echo e(now()->translatedFormat('d F Y')); ?></p>
    </div>
</body>
</html>
>>>>>>> 9466a07 (Upload project pertama kali)
<?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\laporan\rekap\pdf.blade.php ENDPATH**/ ?>