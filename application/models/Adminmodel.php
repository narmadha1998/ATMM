<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Adminmodel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->TableList = array("log" => "logs", "rol" => "roles", "usr" => "users", "casehis" => "casehistory", "case" => "cases", "off_mst" => "offender_master", "sts" => "states", "dist" => "district", "city" => "cities", "ca_st" => "case_status_master", "notf" => "notifications", "of_mst" => "offences_master", "pm" => "privatemessages", "fir" => "fir", "comp" => "complaints");
        $this->SeqID = array("logs" => "id", "roles" => "roleid", "users" => "user_id", "casehistory" => "casehistoryid", "cases" => "caseid", "offender_master" => "offenderid", "states" => "stateid", "district" => "dist_id", "cities" => "cityid", "case_status_master" => "case_status_id", "notifications" => "noty_id", "offences_master" => "offid", "privatemessages" => "msgid", "fir" => "fir_id", "complaints" => "complaintsid");
    }

    public function FetchData($Condition, $Select, $TableList, $SelectAll, $GroupBY, $OrderBY) {
        $TableName = $this->TableList[$TableList];
        return $this->CSearch($Condition, $Select, $TableName, $SelectAll, $GroupBY, $OrderBY);
    }

    public function AllInsert($condition, $dataDB, $Select, $Tble) {
        return $this->Crud($condition, $dataDB, $Select, $Tble);
    }

    public function Crud($Condition, $DBdata, $Select, $TableList) {
        $IPAdress = ($this->input->ip_address() === "::1") ? "127.0.0.1" : $this->input->ip_address();
        $TableName = $this->TableList[$TableList];
        $CrudDetails = $this->CSearch($Condition, $Select, $TableName, null, False);
        $this->db->set($DBdata);
        if (!(empty($CrudDetails))) {
            $this->db->where($Condition);
            $this->db->set("updatedBy", $_SESSION["UserFullName"]);
            $this->db->set("updatedAt", "CURRENT_TIMESTAMP", false);
            $this->db->set("updatedIP", ip2long($IPAdress), false);
            $this->db->update($TableName);
            return $CrudDetails[$this->SeqID[$TableName]];
        } else {
            $this->db->set($Condition);
            $this->db->set("createdBy", empty($_SESSION["UserFullName"]) ? NULL : $_SESSION["UserFullName"]);
            $this->db->set("createdAt", "CURRENT_TIMESTAMP", false);
            $this->db->set("createdIP", ip2long($IPAdress), false);
            $this->db->insert($TableName);
            return $this->db->insert_id();
        }
    }

    public function CSearch($Condition, $Select, $TableName, $SelectAll, $JoinRequired, $JoinType, $Distinct, $Omit, $LeftJoin, $GroupBY, $orderBy) {
        $JoinType = NULL;
        $_TableName = $this->TableList[$TableName];
        if (!empty($Select)) {
            $this->db->select($Select, FALSE);
        }
        if (!empty($_TableName)) {
            $TableName = $_TableName;
        }
        if ($JoinRequired) {
            $this->JoinData($TableName, $JoinType, $Omit, $LeftJoin);
        }
        if ($Distinct) {
            $this->db->distinct();
        }
        if ($Condition) {
            $this->db->distinct($Condition);
        }
        if (!empty($Condition)) {
            $this->db->where($Condition);
        }
        if (!empty($GroupBY)) {
            $this->db->group_by($GroupBY);
        }
        if (!empty($orderBy)) {
            $this->db->order_by($orderBy, "desc");
        }

        $Result = $this->db->get($TableName);

        if (empty($SelectAll)):
            return (empty($Result)) ? null : (array) $Result->row();
        else:
            return (empty($Result)) ? null : (array) $Result->result_array();
        endif;
    }

    protected function JoinData($TableName, $JoinType, $Omit, $LeftJoin) {

        switch ($TableName) {
            case "users":
                $JoinTable = array(
                    "roles" => "roles.roleid=users.role",
                    "cities" => "cities.cityid=users.city",
                    "states" => "states.stateid=users.state",
                );
                break;
            case "cases":
                $JoinTable = array(
                    "users" => "users.user_id=cases.userid",
                    "offences_master" => "offences_master.offid=cases.offid",
                    "gender" => "gender.gender_id=cases.victimgender",
                    "case_status_master" => "case_status_id=cases.casestatus",
                    "district" => "district.dist_id=cases.victimdistrict",
                    "cities" => "cities.cityid=cases.victimcity",
                    "states" => "states.stateid=cases.victimstate",
                    "offender_master" => "offender_master.offenderid=cases.offenderid",
                );
                break;
            case "casehistory":
                $JoinTable = array(
                    "cases" => "cases.caseid=casehistory.caseid",
                    "users" => "users.user_id=casehistory.userid",
                    "roles" => "roles.roleid=users.role"
                );
                break;
            case "offender_master":
                $JoinTable = array(
                    "gender" => "gender.gender_id=offender_master.offendergender",
                    "states" => "states.stateid=offender_master.offenderstate",
                    "cities" => "cities.cityid=offender_master.offendercity",
                    "district" => "district.dist_id=offender_master.offenderdistrict",
                );
                break;
            case "privatemessages":
                $JoinTable = array(
                    "users" => "users.user_id=privatemessages.msgfrom",
                    "users" => "users.user_id=privatemessages.msgto",
                );
                break;
            case "complaints":
                $JoinTable = array(
                    "users" => "users.user_id=complaints.comp_usr_ref",
                );
                break;
        }

        if (!empty($JoinTable)) {
            foreach ($JoinTable as $key => $val) {
                if (!in_array($key, $Omit)) {
                    $JoinType = (in_array($key, $LeftJoin)) ? "LEFT" : $JoinType;
                    $this->db->join($key, $val, $JoinType);
                }
            }
        }
    }

    public function Delete($id, $idval, $table) {
        $this->db->where($id, $idval);
        return $this->db->delete($table);
    }

    public function DropData($condition, $table) {
        $TableName = $this->TableList[$table];
        $this->db->where($condition);
        $status = $this->db->delete($TableName);
        return ($this->db->affected_rows() > 0) ? TRUE : FALSE;
    }

    private function _get_datatables_query($tableName, $Condition, $ColumnOrder, $ColumnSearch, $OrderBy, $JoinRequired) {
        if ($JoinRequired) {
            $this->JoinData($tableName, 'LEFT', '', '');
        }
        $this->db->from($tableName);
        $this->db->where($Condition);
        $i = 0;
        foreach ($ColumnSearch as $item) { // loop column
            if ($_POST['search']['value']) { // if datatable send POST for search
                if ($i === 0) { // first loop
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($ColumnSearch) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $i++;
        }
        if (isset($_POST['order'])) { // here order processing
            $this->db->order_by($ColumnOrder[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($OrderBy)) {
            $order = $OrderBy;
            $this->db->order_by(key($order));
        }
    }

    function get_datatables($TableList, $Condition, $ColumnOrder, $ColumnSearch, $OrderBy, $JoinRequired) {
        $TableName = $this->TableList[$TableList];
        $this->_get_datatables_query($TableName, $Condition, $ColumnOrder, $ColumnSearch, $OrderBy, $JoinRequired);
        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
        }
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($TableList, $Condition, $ColumnOrder, $ColumnSearch, $OrderBy, $JoinRequired) {
        $TableName = $this->TableList[$TableList];
        $this->_get_datatables_query($TableName, $Condition, $ColumnOrder, $ColumnSearch, $OrderBy, $JoinRequired);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all($TableList, $Condition) {
        $TableName = $this->TableList[$TableList];
        $this->db->from($TableName);
        $this->db->where($Condition);
        return $this->db->count_all_results();
    }

}
