<?php
/**
 * Created by PhpStorm.
 * User: kan
 * Date: 28.09.15
 * Time: 19:01
 */
class DB{
    private $db;

    function __construct($host,$dbname,$user,$pass=''){
        $options = array( PDO:: MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
        return $this->db=new PDO("mysql:host=$host;dbname=$dbname",$user,$pass,$options);
    }

    function getItemsList(array $filter=null, array $selectFields=null,$table,array $sortBy =null ){
        $sql="SELECT ";
        if($selectFields){
            $i=count($selectFields);
            $ic=0;
            foreach($selectFields as $field){
                $ic++;
                $sql.=" `$field`";
                $sql.=($ic<$i)?", ":"";
            }
        }else{
            $sql.=" * ";
        }
        $sql.=' FROM `'.$table.'`';

        if($filter){
            $sql.=" WHERE";
            $i=count($filter);
            $ic=0;
            foreach($filter as $key=>$value){
                $ic++;
                $sql.=" `$key`='$value' ";
                $sql.=($ic<$i)?" AND ":"";
            }
        }
        if($sortBy){
            $sql.=" ORDER BY ".$sortBy[0]." ".$sortBy[1];
        }
        // $sql.="LIMIT 20";
        if($res=$this->db->query($sql)) {
            $ItemsList = array();
            while ($ar = $res->fetch(PDO::FETCH_ASSOC)) {
                $ItemsList[] = $ar;
            }
        }
        if(isset($ItemsList)){
            return $ItemsList;
        }else return false;
    }
    function insertItem($table,$arFields){
        $sql='INSERT INTO '.$table.' (';
        $i=count($arFields);
        $ic=0;
        $fields="";
        $values="";
        foreach($arFields as $field=>$value){
            $ic++;
            $fields.=" $field";
            if($ic<$i) $fields.=",";
            $values.=" '".$value."'";
            if($ic<$i) $values.=",";
        }
        $sql.=$fields.") VALUES (".$values.")";
        $this->db->exec($sql);
        return $this->db->lastInsertId();
    }
    function editItem($table,$arFilter,$arFields){
        $sql='UPDATE '.$table.' SET';
        $i=count($arFields);
        $ic=0;
        $fields="";
        $where="";
        foreach($arFields as $field=>$value){
            $ic++;
            $fields.=" `$field`='$value'";
            if($ic<$i) $fields.=",";
        }
        if($arFilter){
            $where.=" WHERE";
            $i=count($arFilter);
            $ic=0;
            foreach($arFilter as $key=>$value){
                $ic++;
                $where.=" `$key`='$value' ";
                $where.=($ic<$i)?" AND ":"";
            }
        }
        $sql.=$fields.$where;
        return $this->db->exec($sql);
    }
    public function deleteItem(array $filter,$table){
        $sql="DELETE ";

        $sql.=' FROM `'.$table.'`';

        if($filter){
            $sql.=" WHERE";
            $i=count($filter);
            $ic=0;
            foreach($filter as $key=>$value){
                $ic++;
                $sql.=" `$key`='$value' ";
                $sql.=($ic<$i)?" AND ":"";
            }
        }
        //echo $sql;
        $this->db->exec($sql);

    }

    function query($q){
        $result=$this->db->query($q);
        if (!$result) throw new Exception($this->db->errorInfo());
        return $result;
    }
}