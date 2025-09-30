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
        $this->db->select('buku.judul, buku.th_terbit as tahun, buku.penulis1 as pengarang,buku.penulis1 as pengarang,buku.penulis2 as pengarang1, jenis_buku.jns_buku as kategori');
        $this->db->from('buku');
        $this->db->join('jenis_buku', 'buku.kd_jns_buku = jenis_buku.kd_jns_buku', 'left');
        $this->db->group_start();
        $this->db->like('buku.judul', $query);
        $this->db->or_like('buku.penulis1', $query);
        $this->db->or_like('buku.penulis2', $query);
        $this->db->or_like('buku.penulis3', $query);
        $this->db->or_like('buku.th_terbit', $query);
        $this->db->group_end();
        $this->db->limit(30);
        $results = $this->db->get()->result_array();

        // Ganti kategori 'Sirkulasi' menjadi 'Buku'
        foreach ($results as &$row) {
            if (isset($row['kategori']) && strtolower($row['kategori']) == 'sirkulasi') {
                $row['kategori'] = 'Buku';
            }
        }
        unset($row);
        return $results;
    }
}
