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
        buku.th_terbit as tahun, 
    (CASE 
        WHEN buku.penulis2 IS NULL OR buku.penulis2 = '' THEN buku.penulis1
        WHEN buku.penulis3 IS NULL OR buku.penulis3 = '' THEN CONCAT(buku.penulis1, ', ', buku.penulis2)
        ELSE CONCAT(buku.penulis1, ', ', buku.penulis2, ', ', buku.penulis3)
    END) AS Pengarang, 
        jenis_buku.jns_buku as kategori,
        ($relevance_expr) AS relevance
    ");
        $this->db->from('buku');
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
    public function build_keywords($query_clean, $is_strict = false)
    {
        // Hilangkan noise word
        $dictionary = $this->build_dictionary();
        $words = preg_split('/\s+/', strtolower($query_clean));
        $keywords = array_diff($words, $dictionary['noise']);

        // Jika strict, gunakan frasa penuh
        if ($is_strict) {
            return [trim($query_clean)];
        }

        // Jika tidak strict, gunakan kata-kata yang sudah difilter
        return array_values($keywords);
    }

    public function build_dictionary()
    {
        // Contoh dictionary sederhana, bisa dikembangkan sesuai kebutuhan
        return [
            'noise' => ['anjing'],
            'abbreviation' => ['tk' => 'taman kanak-kanak', 'sd' => 'sekolah dasar'],
            'similarity' => ['pesantren' => 'pondok', 'madrasah' => 'sekolah islam']
        ];
    }

    public function search_books_loose($query)
    {
        $dictionary = $this->build_dictionary();
        if (is_array($query)) {
            $words = array_diff($query, $dictionary['noise']);
        } else {
            $words = array_diff(preg_split('/\s+/', strtolower($query)), $dictionary['noise']);
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
        buku.th_terbit as tahun,
          (CASE 
        WHEN buku.penulis2 IS NULL OR buku.penulis2 = '' THEN buku.penulis1
        WHEN buku.penulis3 IS NULL OR buku.penulis3 = '' THEN CONCAT(buku.penulis1, ', ', buku.penulis2)
        ELSE CONCAT(buku.penulis1, ', ', buku.penulis2, ', ', buku.penulis3)
    END) AS Pengarang,  
        jenis_buku.jns_buku as kategori,
        ($relevance_expr) AS relevance
    ");
        $this->db->from('buku');
        $this->db->join('jenis_buku', 'buku.kd_jns_buku = jenis_buku.kd_jns_buku', 'left');

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

        // Pada bagian foreach hasil query (search_books dan search_books_loose)
        foreach ($results as &$row) {
            if (isset($row['kategori']) && strtolower($row['kategori']) == 'sirkulasi') {
                $row['kategori'] = 'Buku';
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
}
