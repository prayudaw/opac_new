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

        // Proses input pencarian
        if ($query) {
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

            // Simpan frasa kutipan jika ada
            preg_match('/^"(.*?)"$/', $query_clean, $quoted);

            $data['quoted_phrase'] = isset($quoted[1]) ? $quoted[1] : '';

            // Tentukan mode strict atau tidak
            $is_strict = (isset($quoted[1]) && $quoted[1]) || $word_count == 1;

            $data['is_strict'] = $is_strict ? 'strict' : 'loose';

            // Panggil fungsi build_keywords di model (implementasi sesuai kebutuhan)
            $keywords = $this->Book_model->build_keywords($query_clean, $is_strict);
            $data['keywords'] = $keywords;

            // Jika terlalu pendek (<3 huruf), batal pencarian
            if (strlen(preg_replace('/[^a-z]/i', '', $query_clean)) < 3) {
                $results = [];
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