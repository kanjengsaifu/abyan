<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pembayaran_hutang_model extends MY_Model
{
    public $table = 'pembayaran_hutang';
    public $primary_key = 'id';
    public $column_order = array(null, 'id','kode_pembayaran_hutang', 'kode_hutang','kode_relasi','nama_relasi','nominal','tanggal','status',null);
    public $column_search = array('kode_pembayaran_hutang', 'kode_hutang','kode_relasi','nama_relasi','nominal','tanggal','status');
    public $order = array('tanggal' => 'desc'); // default order

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query()
    {
        //$this->db->group_by('kode');
        $this->db->order_by('tanggal','desc');
        $this->db->from('pembayaran_hutang');
          $i = 0;
        foreach ($this->column_search as $item) // loop column
        {
            if($_POST['search']['value']) // if datatable send POST for search
            {
                if($i===0) // first loop
                {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                }
                else
                {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if(count($this->column_search) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $i++;
        }

        /*
        if(isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        }
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
        */
    }

    function get_datatables()
    {
        $this->_get_datatables_query();
        if($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->select($this->primary_key);
        $this->db->from($this->table);
        return $this->db->count_all_results();
        
        

    }

    public function get_by_id($id)
    {
        $this->db->from($this->table);
        $this->db->where($this->primary_key,$id);
        $query = $this->db->get();

        return $query->row();
    }

    public function save($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_by_id($where, $data)
    {
        $this->db->update($this->table, $data, $where);
        return $this->db->affected_rows();
        //$this->db->update($this->table,array("upload_rate"=>0,"download_rate"=>0));
    }

    public function delete_by_id($id)
    {
        $this->db->where($this->primary_key, $id);
        $this->db->delete($this->table);
    }

    public function update_by_kode($kode, $data)
    {
        $this->db->where('kode',$kode);
        $this->db->update($this->table, $data);
        return $this->db->affected_rows();
        //$this->db->update($this->table,array("upload_rate"=>0,"download_rate"=>0));
    }


    public function total_pembayaran_hutang_perbulan_tahun($bulan,$tahun){
        $total_hutang = array();
        $filter = "$bulan"."/".$tahun;
        $this->db->select("
            ifnull(max(abs(substring_index(kode_pembayaran_hutang, '/', 1))),0) as total_hutang
        ");
        $this->db->where(" kode_pembayaran_hutang like '%$filter' ");
        $query = $this->db->get($this->table);

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total_hutang = $atributy->total_hutang ;
                
            }

        }
        return $total_hutang;

    }

    public function get_total_bayar_by_kode($kode){
        $nominal = array();
        $this->db->select("
            sum(nominal) as nominal
        ");
        $this->db->where("kode_hutang",$kode);
        //$this->db->where("tanggal",$tanggal);
        $query = $this->db->get($this->table);

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $nominal = $atributy->nominal ;
                
            }

        }
        return $nominal;
    }

    
    //untuk detail laba rugi
//    public function select_insert_hutang_perbulan_tahun($bulan,$tahun,$idlabarugi){
//        
//        $this->db->select("p.kode_relasi as kode_so, pp.nominal, pp.tanggal as tgl_trans");
//        $this->db->where("month(pp.tanggal)='$bulan' and year(pp.tanggal) = '$tahun'");
//        $this->db->from($this->table.' pp');
//        $this->db->join('hutang p','p.kode_hutang=pp.kode_hutang');
//        $query = $this->db->get();
//        $hasil = $query->result_array();
//        $i=1;
//        foreach($hasil as $row){
//            $row['id_detail_laba_rugi']=$id=md5($bulan.'/'.$tahun.'/hutang/'.$i);
//            $row['created_at']=date('Y-m-d H:i:s');
//            $row['id_laba_rugi']=$idlabarugi;
//            $row['jenis_trans']='hutang';
//            $insert = $this->db->insert('detail_laba_rugi', $row);
//            $i++;
//        }
//        return $insert;
//    }
}