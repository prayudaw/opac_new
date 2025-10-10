<?php
class Visitor_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function log_visitor($ip, $user_agent)
    {
        $today = date('Y-m-d');
        $query = $this->db->get_where('pengunjung_opac_yuda', [
            'tanggal' => $today,
            'ip_address' => $ip
        ]);
        if ($query->num_rows() > 0) {
            $this->db->where([
                'tanggal' => $today,
                'ip_address' => $ip
            ])->set('jumlah', 'jumlah+1', false)->update('pengunjung_opac_yuda');
        } else {
            $this->db->insert('pengunjung_opac_yuda', [
                'tanggal' => $today,
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'jumlah' => 1
            ]);
        }
    }

    public function get_statistik_harian($limit = 30)
    {
        return $this->db->select('tanggal, SUM(jumlah) as total')
            ->group_by('tanggal')
            ->order_by('tanggal', 'DESC')
            ->limit($limit)
            ->get('pengunjung_opac_yuda')
            ->result_array();
    }

    public function get_statistik_total()
    {
        return $this->db->select('COUNT(DISTINCT ip_address) as total_pengunjung, SUM(jumlah) as total_pencarian')
            ->get('pengunjung_opac_yuda')
            ->row_array();
    }
}
