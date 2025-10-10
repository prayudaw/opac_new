<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Result extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Book_model');
        $this->load->model('Visitor_model');
    }

    public function index()
    {
        // Catat statistik pengunjung
        $this->Visitor_model->log_visitor(
            $this->input->ip_address(),
            $this->input->user_agent()
        );

        //Ambil dan bersihkan query dari URL
        $query = trim($this->input->get('q', TRUE));
        $data['benchmarkStarted'] = microtime(true);;
        $data['query'] = $query;
        $data['results'] = [];
        $data['search_error'] = null;

        if (!empty($query)) {
            // 1.Bersihkan dan ubah ke ASCII
            $query_ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $query);

            // 2. Bersihkan query untuk diproses
            $query_clean = preg_replace('/[^a-zA-Z0-9\s"-]/', '', $query_ascii);

            // 3. Tentukan mode pencarian (Strict atau Loose)
            preg_match('/"(.*?)"/', $query, $quoted);
            $word_count = str_word_count($query_clean);
            $is_strict = (isset($quoted[1]) && $quoted[1]) || $word_count == 1;

            // 4. Validasi panjang query
            if (strlen($query_clean) < 3) {
                $data['search_error'] = 'Kata kunci pencarian terlalu pendek. Masukkan minimal 3 karakter.';
            } else {
                // 5. Eksekusi pencarian berdasarkan mode
                if ($is_strict) {
                    // Mode STRICT: Cari sebagai frasa utuh atau satu kata
                    $results = $this->Book_model->search_books($query_clean);
                } else {

                    // Mode LOOSE: Pecah jadi kata kunci, buang noise words
                    $keywords = $this->Book_model->build_keywords($query_clean, false);

                    if (empty($keywords)) {
                        $data['search_error'] = 'Kata kunci Anda terlalu umum. Coba gunakan kata yang lebih spesifik.';
                    } else {
                        $results = $this->Book_model->search_books_loose($keywords);
                    }
                }
                $data['results'] = $results;

                // (Opsional) Log pencarian
                $log_data = [
                    'keyword'     => $query,
                    'ip_address'  => $this->input->ip_address(),
                    'user_agent'  => $this->input->user_agent(),
                    'searched_at' => date('Y-m-d H:i:s')
                ];
                $this->Book_model->log_search($log_data);
            }
        }

        // 6. Tampilkan view dengan data hasil pencarian
        $this->load->view('result', $data);
    }
}
