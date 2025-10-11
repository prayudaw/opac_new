<?php
class Book_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function search_books($query)
    {

        // Hilangkan noise word dan pecah kata
        $dictionary = $this->build_dictionary();
        $words = array_diff(preg_split('/\s+/', strtolower($query)), $dictionary['noise']);

        // Terapkan similarity
        $expanded_words = [];
        $similarity_flipped = array_flip($dictionary['similarity']);

        foreach ($words as $word) {
            $expanded_words[] = $word;
            if (isset($dictionary['similarity'][$word])) {
                $expanded_words[] = $dictionary['similarity'][$word];
            } elseif (isset($similarity_flipped[$word])) {
                $expanded_words[] = $similarity_flipped[$word];
            }
        }
        $words = array_values(array_unique($expanded_words)); // Gunakan kata yang sudah diperluas


        // Jika tidak ada kata kunci yang valid setelah filter, kembalikan array kosong
        if (empty($words)) {
            return [];
        }

        // Bangun ekspresi relevance untuk semua kata
        $relevance_sql = [];
        foreach ($words as $word) {
            $word = $this->db->escape_like_str($word);
            $relevance_sql[] = "(CASE
            WHEN buku.judul LIKE '%{$word}%' THEN 10
            WHEN buku.penulis1 LIKE '%{$word}%' THEN 8
            WHEN buku.penulis2 LIKE '%{$word}%' THEN 7
            WHEN buku.penulis3 LIKE '%{$word}%' THEN 7
            ELSE 0 END)";
        }
        $relevance_expr = implode(' + ', $relevance_sql);

        $this->db->select("
        buku.judul, 
         buku.gol_pustaka, 
         buku.kd_jns_buku, 
        buku.th_terbit as tahun, 
    (CASE 
        WHEN buku.penulis2 IS NULL OR buku.penulis2 = '' THEN buku.penulis1
        WHEN buku.penulis3 IS NULL OR buku.penulis3 = '' THEN CONCAT(buku.penulis1, ', ', buku.penulis2)
        ELSE CONCAT(buku.penulis1, ', ', buku.penulis2, ', ', buku.penulis3)
    END) AS Pengarang, 
         buku.editor as editor,
         item_buku.label1 as jenis_buku,
         item_buku.label2 as no_buku,
         item_buku.label3 as label3,
         item_buku.label4 as label4,
         item_buku.label5 as label5,
        jenis_buku.jns_buku as kategori,
        ($relevance_expr) AS relevance
    ");
        $this->db->from('buku');
        $this->db->join('item_buku', 'buku.kd_buku = item_buku.kd_buku', 'left');
        $this->db->join('jenis_buku', 'buku.kd_jns_buku = jenis_buku.kd_jns_buku', 'left');

        // Kondisi pencarian (WHERE) berdasarkan kata/frasa
        $this->db->group_start();
        foreach ($words as $word) {
            $this->db->or_like('buku.judul', $word);
            $this->db->or_like('buku.penulis1', $word);
            $this->db->or_like('buku.penulis2', $word);
            $this->db->or_like('buku.penulis3', $word);
            $this->db->or_like('buku.th_terbit', $word);
        }
        $this->db->group_end();

        $this->db->limit(300);
        $this->db->order_by('relevance', 'DESC');
        // $this->db->order_by('buku.th_terbit', 'DESC');
        $this->db->order_by('buku.judul', 'ASC');

        $results = $this->db->get()->result_array();

        foreach ($results as &$row) {
            if (isset($row['kategori']) && strtolower($row['kategori']) == 'sirkulasi') {
                $row['kategori'] = 'Buku';
            }

            if (isset($row['gol_pustaka'])) {
                if ($row['gol_pustaka'] == 'SK') { // Skripsi, Tesis, Disertasi
                    $skripsi_codes = ['DT', 'SK', 'TS', '13', 'DS'];
                    if (in_array($row['kd_jns_buku'], $skripsi_codes)) {
                        $row['pembimbing'] = $row['editor'];
                        $row['ruangan'] = 'Lantai 2 Ruang Skripsi Sebelah timur';
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3']);
                        $row['rak'] = $this->getRakSkripsi($row['jenis_buku']);
                    }
                } elseif ($row['gol_pustaka'] == 'BK') { // Buku
                    if (isset($corner_map[$row['kd_jns_buku']])) { // buku selain AS ,SR
                        $no_buku_bersih = trim($row['no_buku']);

                        // Tentukan rak
                        if (strpos($no_buku_bersih, '.') !== false && stripos($no_buku_bersih, 'x') !== false) {
                            $row['rak'] = strtoupper(substr($no_buku_bersih, 0, strpos($no_buku_bersih, '.')));
                        }
                        //  elseif (is_numeric($no_buku_bersih)) {
                        //     // Bulatkan ke bawah ke ratusan terdekat, minimal 100
                        //     $rak_angka = max(100, floor(floatval($no_buku_bersih) / 100) * 100);
                        //     $row['rak'] = (string)$rak_angka;
                        // } else {
                        //     $row['rak'] = strtoupper($no_buku_bersih);
                        // }
                        $row['ruangan'] = $corner_map[$row['kd_jns_buku']];
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3']);
                    } else {
                        $no_buku_bersih = trim($row['no_buku']);
                        // Tentukan rak
                        if (strpos($no_buku_bersih, '.') !== false && stripos($no_buku_bersih, 'x') !== false) {
                            $row['rak'] = strtoupper(substr($no_buku_bersih, 0, strpos($no_buku_bersih, '.')));
                        } elseif (is_numeric($no_buku_bersih)) {
                            // Bulatkan ke bawah ke ratusan terdekat, minimal 100
                            $rak_angka = max(100, floor(floatval($no_buku_bersih) / 100) * 100);
                            $row['rak'] = (string)$rak_angka;
                        } else {
                            $row['rak'] = strtoupper($no_buku_bersih);
                        }
                        // Tentukan ruangan dan label
                        $row['ruangan'] = (stripos($no_buku_bersih, '2X') === 0) ? 'Lantai 3' : 'Lantai 4';
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                    }
                } elseif ($row['gol_pustaka'] == 'AV') {
                    $row['ruangan'] = 'Lantai 3 Meja Petugas';
                    $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                } elseif ($row['gol_pustaka'] == 'LP') {
                    $row['ruangan'] = 'Lantai 3 Meja Petugas';
                    $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                } elseif ($row['gol_pustaka'] == 'AL') {
                }
            }
            // Highlight hanya kata penting (abaikan noise) pada judul
            $row['highlight_phrase'] = $row['judul'];
            $row['highlight_pengarang'] = $row['Pengarang'];
            foreach ($words as $word) {
                if (strlen($word) < 1) continue;
                if (!in_array($word, $dictionary['noise']) && strlen($word) > 2) {
                    $row['highlight_phrase'] = preg_replace(
                        '/(' . preg_quote($word, '/') . ')/i',
                        '<span class="bg-yellow-200 text-black font-semibold">$1</span>',
                        $row['highlight_phrase']
                    );
                    $row['highlight_pengarang'] = preg_replace(
                        '/(' . preg_quote($word, '/') . ')/i',
                        '<span class="bg-yellow-200 text-black font-semibold">$1</span>',
                        $row['highlight_pengarang']
                    );
                }
            }
        }
        unset($row);
        return $results;
    }



    public function search_books_loose($query)
    {
        $dictionary = $this->build_dictionary();
        if (is_array($query)) {
            $words = array_diff($query, $dictionary['noise']);
        } else {
            $words = array_diff(preg_split('/\s+/', strtolower($query)), $dictionary['noise']);
        }

        // [FIX] Tambahkan pengecekan ini
        // Jika tidak ada kata kunci yang valid setelah filter, kembalikan array kosong
        if (empty($words)) {
            return [];
        }

        // Bangun ekspresi relevance untuk semua kata
        $relevance_sql = [];
        foreach ($words as $word) {
            $word = $this->db->escape_like_str($word);
            $relevance_sql[] = "(CASE
            WHEN buku.judul LIKE '%{$word}%' THEN 10
            WHEN buku.penulis1 LIKE '%{$word}%' THEN 8
            WHEN buku.penulis2 LIKE '%{$word}%' THEN 7
            WHEN buku.penulis3 LIKE '%{$word}%' THEN 7
            ELSE 0 END)";
        }
        $relevance_expr = implode(' + ', $relevance_sql);

        $this->db->select("
        buku.judul, 
        buku.kd_jns_buku, 
        buku.th_terbit as tahun,
        buku.gol_pustaka,
          (CASE 
        WHEN buku.penulis2 IS NULL OR buku.penulis2 = '' THEN buku.penulis1
        WHEN buku.penulis3 IS NULL OR buku.penulis3 = '' THEN CONCAT(buku.penulis1, ', ', buku.penulis2)
        ELSE CONCAT(buku.penulis1, ', ', buku.penulis2, ', ', buku.penulis3)
    END) AS Pengarang,  
        GROUP_CONCAT(DISTINCT buku.editor) as editor,
        GROUP_CONCAT(DISTINCT item_buku.label1) as jenis_buku,
        GROUP_CONCAT(DISTINCT item_buku.label2) as no_buku,
        GROUP_CONCAT(DISTINCT item_buku.label3) as label3,
        GROUP_CONCAT(DISTINCT item_buku.label4) as label4,
        GROUP_CONCAT(DISTINCT item_buku.label5) as label5,
        GROUP_CONCAT(DISTINCT jenis_buku.jns_buku) as kategori,
        ($relevance_expr) AS relevance
    ");
        $this->db->from('buku');
        $this->db->join('jenis_buku', 'buku.kd_jns_buku = jenis_buku.kd_jns_buku', 'left');
        $this->db->join('item_buku', 'buku.kd_buku = item_buku.kd_buku', 'left');
        $this->db->group_by('buku.kd_buku');


        $this->db->group_start();
        foreach ($words as $word) {
            $this->db->or_like('buku.judul', $word);
            $this->db->or_like('buku.penulis1', $word);
            $this->db->or_like('buku.penulis2', $word);
            $this->db->or_like('buku.penulis3', $word);
            $this->db->or_like('buku.th_terbit', $word);
        }
        $this->db->group_end();

        $this->db->limit(500);
        $this->db->order_by('relevance', 'DESC');
        // $this->db->order_by('buku.th_terbit', 'DESC');
        $this->db->order_by('buku.judul', 'ASC');

        $results = $this->db->get()->result_array();

        // Pada bagian foreach hasil query (search_books dan search_books_loose)
        foreach ($results as &$row) {

            $corner_map = $this->getCornerMap();

            if (isset($row['kategori']) && strtolower($row['kategori']) == 'sirkulasi') {
                $row['kategori'] = 'Buku';
            }

            if (isset($row['gol_pustaka'])) {
                if ($row['gol_pustaka'] == 'SK') { // Skripsi, Tesis, Disertasi
                    $skripsi_codes = ['DT', 'SK', 'TS', '13', 'DS'];
                    if (in_array($row['kd_jns_buku'], $skripsi_codes)) {
                        $row['pembimbing'] = $row['editor'];
                        $row['ruangan'] = 'Lantai 2 Ruang Skripsi Sebelah timur';
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3']);
                        $row['rak'] = $this->getRakSkripsi($row['jenis_buku']);
                    }
                } elseif ($row['gol_pustaka'] == 'BK') { // Buku
                    if (isset($corner_map[$row['kd_jns_buku']])) {
                        $no_buku_bersih = trim($row['no_buku']);
                        // Tentukan rak
                        if (strpos($no_buku_bersih, '.') !== false && stripos($no_buku_bersih, 'x') !== false) {
                            $row['rak'] = strtoupper(substr($no_buku_bersih, 0, strpos($no_buku_bersih, '.')));
                        }
                        //  elseif (is_numeric($no_buku_bersih)) {
                        //     // Bulatkan ke bawah ke ratusan terdekat, minimal 100
                        //     $rak_angka = max(100, floor(floatval($no_buku_bersih) / 100) * 100);
                        //     $row['rak'] = (string)$rak_angka;
                        // } else {

                        //     $row['rak'] = strtoupper($no_buku_bersih);
                        // }

                        $row['ruangan'] = $corner_map[$row['kd_jns_buku']];
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3']);
                    } else {
                        $no_buku_bersih = trim($row['no_buku']);
                        // Tentukan rak
                        if (strpos($no_buku_bersih, '.') !== false && stripos($no_buku_bersih, 'x') !== false) {
                            $row['rak'] = strtoupper(substr($no_buku_bersih, 0, strpos($no_buku_bersih, '.')));
                        } elseif (is_numeric($no_buku_bersih)) {
                            // Bulatkan ke bawah ke ratusan terdekat, minimal 100
                            $rak_angka = max(100, floor(floatval($no_buku_bersih) / 100) * 100);
                            $row['rak'] = (string)$rak_angka;
                        } else {

                            $row['rak'] = strtoupper($no_buku_bersih);
                        }

                        // Tentukan ruangan dan label
                        $row['ruangan'] = (stripos($no_buku_bersih, '2X') === 0) ? 'Lantai 3' : 'Lantai 4';
                        $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                    }
                } elseif ($row['gol_pustaka'] == 'AV') {
                    $row['ruangan'] = 'Lantai 3 Meja Petugas';
                    $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                } elseif ($row['gol_pustaka'] == 'LP') {
                    $row['ruangan'] = 'Lantai 3 Meja Petugas';
                    $row['label'] = trim($row['jenis_buku'] . ' ' . $row['no_buku'] . ' ' . $row['label3'] . ' ' . $row['label4']);
                } elseif ($row['gol_pustaka'] == 'AL') {
                }
            }

            // Highlight hanya kata penting (abaikan noise) pada judul
            $row['highlight_phrase'] = $row['judul'];
            $row['highlight_pengarang'] = $row['Pengarang'];
            foreach ($words as $word) {
                if (strlen($word) < 1) continue;
                if (!in_array($word, $dictionary['noise']) && strlen($word) > 2) {
                    $row['highlight_phrase'] = preg_replace(
                        '/(' . preg_quote($word, '/') . ')/i',
                        '<span class="bg-yellow-200 text-black font-semibold">$1</span>',
                        $row['highlight_phrase']
                    );
                    $row['highlight_pengarang'] = preg_replace(
                        '/(' . preg_quote($word, '/') . ')/i',
                        '<span class="bg-yellow-200 text-black font-semibold">$1</span>',
                        $row['highlight_pengarang']
                    );
                }
            }
        }
        unset($row);
        // echo "<pre>" . print_r($results, true) . "</pre>";
        // die();
        return $results;
    }

    public function getCornerMap()
    {
        return [
            'CC' => 'Lantai 3 Canadian Corner',
            'BI' => 'Lantai 2 BI Corner',
            'IC' => 'lantai 2 Iranian Corner',
            'IJ' => 'lantai 2 Kalijaga Corner',
            'KC' => 'Lantai',
            'AR' => 'Lantai 2 Sebelah Barat',
            'LP' => 'Lantai',
            'MK' => 'Lantai',
            'MS' => 'Lantai',
            'MU' => 'Lantai 2 sebelah barat',
            'NC' => 'Lantai',
            'PK' => 'Lantai',
            'RD' => 'Lantai',
            'RF' => 'Lantai 2 sebelah barat',
            'RT' => 'Lantai',
            'SC' => 'Lantai',
            'SH' => 'Lantai',
            'ST' => 'Lantai',
            'TB' => 'Lantai',
        ];
    }

    public function log_search($log_data)
    {
        // Tabel: search_log (id INT AUTO_INCREMENT, keyword VARCHAR, ip_address VARCHAR, user_agent VARCHAR, searched_at DATETIME)
        $this->db->insert('search_log', [
            'keyword'     => $log_data['keyword'],
            'ip_address'  => $log_data['ip_address'],
            'user_agent'  => $log_data['user_agent'],
            'searched_at' => $log_data['searched_at']
        ]);
    }

    public function getRakSkripsi($jns_buku)
    {

        // label bisa berupa string, misal: 'Buku', 'Jurnal', 'Skripsi', dll
        $jns_buku = strtolower($jns_buku);

        switch ($jns_buku) {
            case 'ay':
                return 'ADAB';
            case 'ty':
                return 'Tarbiyah';
            case 'st':
                return 'Saintek';
            case 'dy':
                return 'Dakwah';
            case 'fb':
                return 'Febi';
            case 'sy':
                return 'Syariah';
            case 'uy':
                return 'Usuluddin';
            case 'dy':
                return 'Dakwah';
            case 'dt':
                return 'Disertasi';
            case 'hm':
                return 'Fishum';
            case 'ps':
                return 'Tesis';

            default:
                return 'not found';
        }
    }

    public function build_keywords($query_clean, $is_strict = false)
    {
        // Ambil dictionary (noise word, dsb)
        $dictionary = $this->build_dictionary();

        // Jika strict, gunakan frasa penuh (tidak dipecah)
        if ($is_strict) {
            return [trim($query_clean)];
        }

        // Jika tidak strict, pecah query menjadi kata-kata
        $words = preg_split('/\s+/', strtolower($query_clean));
        // Hilangkan noise word
        $keywords = array_diff($words, $dictionary['noise']);

        // Logika untuk menerapkan $similarity
        $expanded_keywords = [];
        $similarity_flipped = array_flip($dictionary['similarity']); // Balik array untuk pencarian dua arah

        foreach ($keywords as $word) {
            $expanded_keywords[] = $word; // Tambahkan kata asli
            if (isset($dictionary['similarity'][$word])) {
                // Jika kata adalah kunci di array similarity (misal: 'skripsi')
                $expanded_keywords[] = $dictionary['similarity'][$word]; // Tambahkan nilainya ('tugas akhir')
            } elseif (isset($similarity_flipped[$word])) {
                // Jika kata adalah nilai di array similarity (misal: 'tugas akhir')
                $expanded_keywords[] = $similarity_flipped[$word]; // Tambahkan kuncinya ('skripsi')
            }
        }

        // Hilangkan duplikat dan kembalikan array kata kunci yang sudah diperluas
        return array_values(array_unique($expanded_keywords));
    }

    public function build_dictionary()
    {

        // Noise words: kata-kata umum yang diabaikan dalam pencarian
        $noise = [
            'dan',
            'atau',
            'yang',
            'di',
            'ke',
            'dari',
            'untuk',
            'dengan',
            'pada',
            'adalah',
            'ini',
            'itu',
            'sebagai',
            'oleh',
            'dalam',
            'juga',
            'bagi',
            'karena',
            'pada',
            'para',
            'maka',
            'akan',
            'dapat',
            'dengan',
            'tanpa',
            'oleh',
            'sehingga',
            'agar',
            'supaya',
            'bukan',
            'sudah',
            'belum',
            'masih',
            'telah',
            'akan',
            'harus',
            'bisa',
            'dapat',
            'saja',
            'hanya',
            'lagi',
            'pun',
            'lah',
            'kah',
            'nya',
            'bajingan'
        ];

        // Abbreviation: singkatan yang sering digunakan
        $abbreviation = [
            'tk' => 'taman kanak-kanak',
            'sd' => 'sekolah dasar',
            'smp' => 'sekolah menengah pertama',
            'sma' => 'sekolah menengah atas',
            'pts' => 'perguruan tinggi swasta',
            'ptn' => 'perguruan tinggi negeri',
            'uin' => 'universitas islam negeri',
            'univ' => 'universitas',
            'itb' => 'institut teknologi bandung',
            'ui' => 'universitas indonesia'
        ];

        // Similarity: kata yang dianggap mirip/ekuivalen
        $similarity = [
            'pesantren' => 'pondok',
            'madrasah' => 'sekolah islam',
            'perpustakaan' => 'library',
            'skripsi' => 'tugas akhir',
            'tesis' => 'tugas akhir',
            'disertasi' => 'tugas akhir'
        ];

        return [
            'noise' => $noise,
            'abbreviation' => $abbreviation,
            'similarity' => $similarity
        ];
    }

    public function getSkripsiCodes()
    {
        // Kode jenis buku yang dianggap sebagai skripsi/tesis/disertasi
        return ['DT', 'SK', 'TS', '13', 'DS'];
    }
}
