<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian</title>

    <!-- Tambahkan link Google Fonts di <head> -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Pacifico&display=swap"
        rel="stylesheet">
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
                    <a href="<?php echo base_url(); ?>" class="text-3xl sm:text-4xl font-bold mb-2 sm:mb-0" style="
       font-family: 'Playfair Display', serif;
       color: #2563eb;
       letter-spacing: 2px;
       text-shadow: 0 2px 8px rgba(37,99,235,0.10), 0 1px 0 #fff;
       font-weight: 700;
   ">
                        OPAC
                    </a>
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
            <!-- Tab Menu -->
            <div class="bg-gray-100">
                <div
                    class="flex flex-wrap gap-2 sm:gap-6 border-b border-gray-300 mb-6 text-xs sm:text-sm overflow-x-auto">
                    <?php
                    $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';
                    $kategoriList = ['Semua', 'Buku', 'Jurnal', 'Artikel', 'Skripsi', 'Tesis', 'Disertasi', 'Lainnya'];
                    foreach ($kategoriList as $kat): ?>
                        <a href="?q=<?php echo urlencode(isset($query) ? $query : ''); ?>&kategori=<?php echo urlencode($kat); ?>"
                            class="<?php echo ($kategori == $kat) ? 'text-blue-600 font-semibold border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-200'; ?> pb-2 px-2 focus:outline-none transition">
                            <?php echo $kat; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>


        <!-- PERBAIKAN: Tambahkan blok untuk menampilkan pesan error validasi -->
        <?php if (!empty($search_error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                <p class="font-bold">Peringatan</p>
                <p><?php echo $search_error; ?></p>
            </div>
        <?php endif; ?>


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

        $benchmarkEnded = microtime(true);
        $duration = isset($benchmarkStarted) ? round($benchmarkEnded - $benchmarkStarted, 4) : null;
        ?>
        <div id="search-result" class="space-y-4 sm:space-y-8">
            <div class="mb-4 text-xs text-gray-500">
                <?php if ($duration): ?>
                    <span>Pencarian diproses dalam <?php echo $duration; ?> detik.</span>
                <?php endif; ?>
            </div>
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
                            <div
                                class="w-20 h-20 bg-gradient-to-br from-blue-300 via-blue-100 to-blue-50 rounded-full flex items-center justify-center shadow">
                                <!-- Icon Book Open (Heroicons) -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v12m8-12v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6m16 0a2 2 0 00-2-2H6a2 2 0 00-2 2m16 0H4" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg sm:text-xl font-bold text-blue-700 mb-1">
                                <?php echo isset($buku['highlight_phrase']) ? $buku['highlight_phrase'] : htmlspecialchars($buku['judul']); ?>
                            </div>
                            <?php if (isset($buku['pembimbing']) && strtolower($buku['kategori']) == 'skripsi' && !empty($buku['pembimbing'])): ?>
                                <div class="text-gray-700 text-sm sm:text-base mb-1">
                                    <span class="font-semibold">Pembimbing:</span>
                                    <?php echo isset($buku['pembimbing']) ? $buku['pembimbing'] : htmlspecialchars($buku['pembimbing']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-gray-700 text-sm sm:text-base mb-1">
                                <span class="font-semibold">Pengarang:</span>
                                <?php echo isset($buku['highlight_pengarang']) ? $buku['highlight_pengarang'] : htmlspecialchars($buku['pengarang']); ?>
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
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition text-xs sm:text-sm"
                                onclick="showDetailModal(<?php echo htmlspecialchars(json_encode($buku), ENT_QUOTES, 'UTF-8'); ?>)">
                                Detail
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (isset($query) && $query && empty($search_error)): ?>
                <div class="text-gray-500">Tidak ditemukan hasil untuk "<?php echo htmlspecialchars($query); ?>"</div>
            <?php endif; ?>
        </div>

        <!-- Button Scroll to Top -->
        <button id="scrollToTopBtn" title="Kembali ke atas"
            class="fixed bottom-6 right-6 z-50 bg-blue-600 text-white rounded-full p-3 shadow-lg hover:bg-blue-700 transition hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>
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

        <!-- Popup Modal Detail -->
        <div id="detailModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 transition-all duration-300 hidden">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-8 relative border border-blue-100">
                <button onclick="closeDetailModal()"
                    class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl font-bold transition">
                    &times;
                </button>
                <div id="modalContent" class="space-y-4">
                    <!-- Konten detail akan diisi via JS -->
                </div>
            </div>
        </div>

</body>
<script>
    function showDetailModal(buku) {
        let html = `
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-300 via-blue-100 to-blue-50 rounded-full flex items-center justify-center shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v12m8-12v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6m16 0a2 2 0 00-2-2H6a2 2 0 00-2 2m16 0H4" />
                    </svg>
                </div>
            </div>
            <div>
                <div class="text-xl font-bold text-blue-700 mb-1">${buku.highlight_phrase || buku.judul}</div>
                <div class="text-gray-700 text-sm mb-1">
                    <span class="font-semibold">Pengarang:</span> ${buku.highlight_pengarang || buku.pengarang}
                </div>
                <div class="text-gray-500 text-xs mb-1">
                    <span class="font-semibold">Tahun:</span> ${buku.tahun}
                </div>
                <div class="text-gray-500 text-xs">
                    <span class="font-semibold">Kategori:</span> ${buku.kategori || '-'}
                </div>
            </div>
        </div>
        <hr class="my-4 border-blue-100">
        <div class="text-gray-600 text-sm">
            <span class="font-semibold">Detail Lainnya:</span>
            <ul class="list-disc pl-5 mt-2">
                <li><span class="font-semibold">Judul:</span> ${buku.judul}</li>
                <li><span class="font-semibold">Pengarang:</span> ${buku.pengarang}</li>
                <li><span class="font-semibold">Tahun:</span> ${buku.tahun}</li>
                <li><span class="font-semibold">Kategori:</span> ${buku.kategori || '-'}</li>
                <!-- Tambahkan detail lain jika ada -->
            </ul>
        </div>
    `;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }
    // Tampilkan tombol jika scroll > 200px
    const scrollBtn = document.getElementById('scrollToTopBtn');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 200) {
            scrollBtn.classList.remove('hidden');
        } else {
            scrollBtn.classList.add('hidden');
        }
    });
    // Scroll ke atas saat tombol diklik
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
</script>
</div>


</body>

</html>