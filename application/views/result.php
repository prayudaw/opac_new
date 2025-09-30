<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen text-gray-900">
    <div class="max-w-5xl mx-auto px-2 sm:px-6 py-4 sm:py-10">
        <!-- Form Search -->
        <div class="sticky top-0 bg-gray-100 z-20 pb-2">
            <form id="form-search" action="<?php echo base_url('result'); ?>" method="get" class="mb-6 z-10">
                <div
                    class="flex flex-col sm:flex-row gap-3 bg-white rounded-2xl shadow-lg px-2 sm:px-4 py-3 sm:py-4 items-center border border-gray-300">
                    <!-- Logo OPAC -->
                    <a href="<?php echo base_url(); ?>"
                        class="text-xl sm:text-2xl font-bold text-blue-600 mr-0 sm:mr-2 mb-2 sm:mb-0">OPAC</a>
                    <input type="text" name="q" id="search-query"
                        value="<?php echo isset($query) ? htmlspecialchars($query) : ''; ?>"
                        placeholder="Cari sesuatu..."
                        class="w-full sm:flex-1 outline-none bg-transparent text-base sm:text-lg px-3 py-2 sm:py-3 rounded-xl border border-gray-300 focus:border-blue-400 transition placeholder-gray-400 text-gray-900"
                        autofocus>
                    <button type="submit"
                        class="w-full sm:w-auto px-4 py-2 sm:py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                        </svg>
                        Search
                    </button>
                </div>
            </form>
        </div>
        <!-- Tab Menu -->
        <!-- Tab Kategori Search -->
        <?php
        $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';
        $kategoriList = ['Semua', 'Buku', 'Jurnal', 'Artikel', 'Skripsi', 'Tesis', 'Disertasi', 'Lainnya'];
        // Kumpulkan kategori lain yang tidak ada di kategoriList
        $kategoriLainnya = [];
        if (isset($results) && is_array($results)) {
            foreach ($results as $buku) {
                if (isset($buku['kategori']) && !in_array($buku['kategori'], $kategoriList) && !in_array($buku['kategori'], $kategoriLainnya)) {
                    $kategoriLainnya[] = $buku['kategori'];
                }
            }
        }
        // Jika ada kategori lain, tambahkan ke tab 'Lainnya' (tidak perlu ubah tampilan tab, hanya data di bawah)
        ?>
        <div class="flex flex-wrap gap-2 sm:gap-6 border-b border-gray-300 mb-6 text-xs sm:text-sm overflow-x-auto">
            <?php foreach ($kategoriList as $kat): ?>
                <a href="?q=<?php echo urlencode(isset($query) ? $query : ''); ?>&kategori=<?php echo urlencode($kat); ?>"
                    class="<?php echo ($kategori == $kat) ? 'text-blue-600 font-semibold border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-200'; ?> pb-2 px-2 focus:outline-none transition">
                    <?php echo $kat; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Hasil Pencarian Card -->
        <?php
        // Fungsi highlight kata kunci
        if (!function_exists('highlight_phrase')) {
            function highlight_phrase($str, $phrase = '', $tag_open = '<b>', $tag_close = '</b>')
            {
                if ($phrase != '' && strlen($phrase) > 0) {
                    return preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tag_open . '$1' . $tag_close, $str);
                }
                return $str;
            }
        }
        ?>
        <div id="search-result" class="space-y-4 sm:space-y-6">
            <?php if (!isset($query) || trim($query) === ''): ?>
                <div class="text-red-500">Masukkan kata kunci pencarian.</div>
            <?php elseif (isset($results) && is_array($results) && count($results) > 0): ?>
                <?php
                $filtered = [];
                if ($kategori == 'Lainnya') {
                    // Tampilkan data dengan kategori yang tidak ada di kategoriList
                    foreach ($results as $buku) {
                        if (isset($buku['kategori']) && !in_array($buku['kategori'], $kategoriList)) {
                            $filtered[] = $buku;
                        }
                    }
                } else {
                    foreach ($results as $buku) {
                        // Filter kategori, asumsikan field 'kategori' pada data buku
                        if ($kategori == 'Semua' || (isset($buku['kategori']) && strtolower($buku['kategori']) == strtolower($kategori))) {
                            // Pastikan data 'Lainnya' tidak muncul di tab lain
                            if ($kategori != 'Semua' && isset($buku['kategori']) && !in_array($buku['kategori'], $kategoriList)) continue;
                            $filtered[] = $buku;
                        }
                    }
                }
                foreach ($filtered as $buku): ?>
                    <div
                        class="bg-gradient-to-br from-blue-50 via-white to-blue-100 rounded-2xl shadow-lg hover:shadow-2xl transition p-3 sm:p-5 flex flex-col md:flex-row gap-3 md:gap-5 items-center border border-blue-100">
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 bg-blue-200 rounded-full flex items-center justify-center shadow">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 19.5A2.5 2.5 0 006.5 22h11a2.5 2.5 0 002.5-2.5V6a2 2 0 00-2-2H6a2 2 0 00-2 2v13.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg sm:text-xl font-bold text-blue-700 mb-1">
                                <?php echo highlight_phrase(htmlspecialchars($buku['judul']), isset($query) ? $query : '', '<span class="bg-yellow-200 text-blue-900 rounded px-1">', '</span>'); ?>
                            </div>
                            <div class="text-gray-700 text-sm sm:text-base mb-1">
                                <span class="font-semibold">Pengarang:</span>
                                <?php echo htmlspecialchars($buku['pengarang']); ?>
                            </div>
                            <div class="text-gray-500 text-xs sm:text-sm">
                                <span class="font-semibold">Tahun:</span> <?php echo htmlspecialchars($buku['tahun']); ?>
                            </div>
                            <div class="text-gray-500 text-xs sm:text-sm">
                                <span class="font-semibold">Kategori:</span>
                                <?php echo isset($buku['kategori']) ? htmlspecialchars($buku['kategori']) : '-'; ?>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition text-xs sm:text-sm">Detail</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (isset($query) && $query): ?>
                <div class="text-gray-500">Tidak ditemukan hasil untuk "<?php echo htmlspecialchars($query); ?>"</div>
            <?php endif; ?>
        </div>
        <?php
        // Fungsi highlight kata kunci
        if (!function_exists('highlight_phrase')) {
            function highlight_phrase($str, $phrase = '', $tag_open = '<b>', $tag_close = '</b>')
            {
                if ($phrase != '' && strlen($phrase) > 0) {
                    return preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tag_open . '$1' . $tag_close, $str);
                }
                return $str;
            }
        }
        ?>
</body>
<!-- No jQuery or AJAX needed. Form submits via GET and reloads the page with results. -->
</div>
</body>

</html>