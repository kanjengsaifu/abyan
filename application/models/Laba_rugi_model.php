<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Laba_rugi_model extends MY_Model
{
    public $table = 'laba_rugi';
    public $primary_key = 'id';
    public $column_order = array(null, 'id','tanggal', 'periode','total_pendapatan','total_biaya',null);
    public $column_search = array('tanggal', 'periode',);
    public $order = array('tanggal' => 'desc'); // default order

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query()
    {
        //$this->db->group_by('kode');
        $this->db->order_by('tanggal','desc');
        $this->db->from('laba_rugi');
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

    public function get_by_periode($periode)
    {
        $this->db->from($this->table);
        $this->db->where('periode',$periode);
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
        $this->db->where('kode_piutang',$kode);
        $this->db->update($this->table, $data);
        return $this->db->affected_rows();
        //$this->db->update($this->table,array("upload_rate"=>0,"download_rate"=>0));
    }


    public function total_piutang_perbulan_tahun($bulan,$tahun){
        $total_piutang = array();
        $filter = "$bulan"."/".$tahun;
        $this->db->select("
            ifnull(max(abs(substring_index(kode_piutang, '/', 1))),0) as total_piutang
        ");
        $this->db->where(" kode_piutang like '%$filter' ");
        $query = $this->db->get($this->table);

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total_piutang = $atributy->total_piutang ;
                
            }

        }
        return $total_piutang;

    }

    public function get_piutang_by_kode_kirim($kode_kirim){

         $this->db->select("
            kode_piutang, nominal, kode_bantu
        ");

        $this->db->from($this->table);
        $this->db->where("kode_referensi",$kode_kirim);
      //  $this->db->where("tanggal",$tanggal);
        $query = $this->db->get();

        return $query->row();
    }
    
    //untuk laba rugi
    public function total_pendapatan_perbulan_tahun($bulan,$tahun){
        $this->db->select("
            ifnull(sum(pp.nominal),0) as total 
            from piutang p
            join pembayaran_piutang pp using (kode_piutang)
        ");
        $this->db->where(" month(pp.tanggal)='$bulan' and year(pp.tanggal) = '$tahun' ");
        $query = $this->db->get();

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total = $atributy->total ;
                
            }

        }
        return $total;
    }
    
    public function total_biaya_perbulan_tahun($bulan,$tahun){
        $this->db->select("
            ifnull(sum(c.nominal),0) as total 
            from pembayaran_piutang a
            join piutang b on a.kode_piutang = b.kode_piutang
            join transaksi_biaya c on c.kode_referensi = b.kode_referensi
        ");

        $this->db->where(" month(c.tanggal)='$bulan' and year(c.tanggal) = '$tahun'  ");
        $query = $this->db->get();

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total = $atributy->total ;
                
            }

        }
        return $total;
    }
    
    
    public function total_pembelian_perbulan_tahun($bulan,$tahun){
        $this->db->select("
            ifnull(sum(dso.harga_beli),0) as total 
        ");
        $this->db->from('pembayaran_piutang pp');
        $this->db->join('piutang p','p.kode_piutang=pp.kode_piutang');
        $this->db->join('sales_order so','so.kode_so=p.kode_referensi');
        $this->db->join('detail_so dso','dso.kode_so=so.kode_so');
        $this->db->where(" month(pp.tanggal)='$bulan' and year(pp.tanggal) = '$tahun' ");
        $query = $this->db->get();

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total = $atributy->total ;
                
            }
        }
        return $total;
    }
    
    public function total_hutang_perbulan_tahun($bulan,$tahun){
        $this->db->select("
            ifnull(sum(pp.nominal),0) as total 
            from hutang p
            join pembayaran_hutang pp using (kode_hutang)
        ");
        $this->db->where(" month(pp.tanggal)='$bulan' and year(pp.tanggal) = '$tahun' ");
        $query = $this->db->get();

        $totaly2 = $query->num_rows();
        if ($totaly2 > 0) {
            foreach ($query->result() as $atributy) {

                $total = $atributy->total ;
                
            }
        }
        return $total;
    }

}