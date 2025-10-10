<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Statistik Pengunjung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CDN -->
    <script src="<?php echo base_url('assets/js/tailwindcss.js'); ?>"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex flex-col items-center py-8">

    <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-blue-700 mb-4 text-center">Statistik Pengunjung</h2>
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
            <div class="bg-blue-100 rounded-lg p-4 flex-1 text-center shadow">
                <div class="text-sm text-blue-600 font-semibold">Total Pengunjung</div>
                <div class="text-3xl font-bold text-blue-800"><?= $stat_total['total_pengunjung'] ?? 0 ?></div>
            </div>
            <div class="bg-green-100 rounded-lg p-4 flex-1 text-center shadow">
                <div class="text-sm text-green-600 font-semibold">Total Pencarian</div>
                <div class="text-3xl font-bold text-green-800"><?= $stat_total['total_pencarian'] ?? 0 ?></div>
            </div>
        </div>
        <hr class="my-6 border-blue-200">
        <div>
            <div class="text-lg font-semibold text-gray-700 mb-2">Pencarian 30 Hari Terakhir</div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg shadow text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-blue-700">Tanggal</th>
                            <th class="px-4 py-2 text-left text-blue-700">Jumlah Pencarian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stat_harian as $row): ?>
                            <tr class="hover:bg-blue-50">
                                <td class="px-4 py-2"><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stat_harian)): ?>
                            <tr>
                                <td colspan="2" class="px-4 py-2 text-center text-gray-400">Belum ada data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>