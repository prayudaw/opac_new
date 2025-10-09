<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Result extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session'); // Tambahkan baris ini
        $this->load->model('Book_model');
    }

    public function index()
    {
        $benchmarkStarted = microtime(true);
        // Cek dan inisialisasi dictionary di session
        if (!$this->session->userdata('dictionary')) {
            $dictionary = $this->Book_model->build_dictionary();
            $this->session->set_userdata('dictionary', $dictionary);
        }
        $dictionary = $this->session->userdata('dictionary');

        $query = trim($this->input->get('q'));
        $results = [];
        $data['search_error'] = ''; // Inisialisasi variabel error

        // Proses input pencarian
        if ($query) {
            // Log pencarian
            $log_data = [
                'keyword' => $query,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent(),
                'searched_at' => date('Y-m-d H:i:s')
            ];

            // Simpan log ke database (buat model/fungsi sesuai kebutuhan)
            if (method_exists($this->Book_model, 'log_search')) {
                $this->Book_model->log_search($log_data);
            }

            // Bersihkan dan ubah ke ASCII
            $query_ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $query);

            // Hilangkan karakter non-alfabet (kecuali spasi dan kutip)
            $query_clean = trim(preg_replace('/[^a-zA-Z0-9\'" ]/', ' ', $query_ascii));

            // Simpan kata kunci asli dan bersih
            $data['original_query'] = $query;
            $data['clean_query'] = $query_clean;

            // Tentukan jumlah kata
            $word_count = str_word_count($query_clean);
            $data['word_count'] = $word_count;

            // PERBAIKAN: Tetapkan batas maksimal kata
            $max_word_limit = 20;

            // Simpan frasa kutipan jika ada
            preg_match('/^"(.*?)"$/', $query_clean, $quoted);

            $data['quoted_phrase'] = isset($quoted[1]) ? $quoted[1] : '';

            // Tentukan mode strict atau tidak
            $is_strict = (isset($quoted[1]) && $quoted[1]) || $word_count == 1;

            $data['is_strict'] = $is_strict ? 'strict' : 'loose';

            // Panggil fungsi build_keywords di model (implementasi sesuai kebutuhan)
            $keywords = $this->Book_model->build_keywords($query_clean, $is_strict);
            $data['keywords'] = $keywords;

            // PERBAIKAN: Tambahkan validasi untuk jumlah kata maksimal
            if (strlen(preg_replace('/[^a-z]/i', '', $query_clean)) < 3) {
                $results = [];
                $data['search_error'] = 'Kata kunci pencarian terlalu pendek (minimal 3 karakter).';
            } else if ($word_count > $max_word_limit) {
                $results = [];
                $data['search_error'] = 'Pencarian dibatasi hingga ' . $max_word_limit . ' kata untuk hasil yang lebih akurat.';
            } else {
                if ($data['is_strict'] == 'strict') {

                    $results = $this->Book_model->search_books($query_clean);
                } else {
                    $results = $this->Book_model->search_books_loose($keywords);
                }
            }
        }
        $data['query'] = $query;
        $data['results'] = $results;
        $data['benchmarkStarted'] = $benchmarkStarted;
        $this->load->view('result', $data);
    }
}