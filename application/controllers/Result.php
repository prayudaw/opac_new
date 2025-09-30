<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Result extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        $query = trim($this->input->get('q'));
        $this->load->model('Book_model');
        $results = [];
        if ($query) {
            $results = $this->Book_model->search_books($query);
        }
        $data['query'] = $query;
        $data['results'] = $results;
        $this->load->view('result', $data);
    }
}
