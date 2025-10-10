<?php
// filepath: c:\xampp\htdocs\opac_new\application\controllers\Statistik.php

defined('BASEPATH') or exit('No direct script access allowed');

class Statistik extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Visitor_model');
    }

    public function index()
    {
        // Ambil statistik total dan harian
        $data['stat_total'] = $this->Visitor_model->get_statistik_total();
        $data['stat_harian'] = $this->Visitor_model->get_statistik_harian(30); // 30 hari terakhir

        // Tampilkan ke view statistik.php
        $this->load->view('statistik', $data);
    }
}
