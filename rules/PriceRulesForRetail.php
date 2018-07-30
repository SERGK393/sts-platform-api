<?php
/**
 * Created by PhpStorm.
 * User: kan
 * Date: 28.09.15
 * Time: 17:31
 */
include __DIR__.'/DB.php';

class PriceRulesForRetail{
    const IBLOCK_ID = 10;

    private $DB;

    function __construct(){
        $this->DB=new DB('localhost','product_rules','rules_user','rules');
    }

    public function getBitrixPrice($product_sku){
        $price = 0;
        $res_prod=CIBlockElement::getList(array(),array('IBLOCK_ID'=>self::IBLOCK_ID,'XML_ID'=>$product_sku),false,false,array('ID'));
        if($res_prod){
            if($res_prod->getNext(false,false)){
                $db_res = CPrice::GetList(array(),
                    array(
                        "PRODUCT_ID" => $res_prod['ID'],
                        "CATALOG_GROUP_ID" => 1
                    )
                );
                if ($ar_res = $db_res->Fetch()) {
                    $price=$ar_res["PRICE"];
                }
            }
        }
        return $price;
    }

    public function getBitrixInfo($product_sku){
        $arResult = array();
        $result=CIBlockElement::GetList(array(),array('IBLOCK_ID'=>self::IBLOCK_ID,'XML_ID'=>$product_sku),false,false,array('ID','NAME','IBLOCK_SECTION_ID'));
        if($get=$result->getNext(false,false)){
            //$arResult['NAME']=$get['NAME'];

            $prop_res=CIBlockElement::GetProperty(self::IBLOCK_ID,$get['ID'],array(),array('CODE'=>'vendor'));
            if($prop_res){
                if($prop=$prop_res->getNext(false,false)){
                    $arResult['brand']=$prop['VALUE'];
                }
            }

            $section_id=$get['IBLOCK_SECTION_ID'];
            //$arResult['SID']=$section_id;
            while($section_id){
                $res = CIBlockSection::GetByID($section_id);
                if($ar_res = $res->GetNext(false,false)){
                    $arResult['SECTIONS'][]=$ar_res['NAME'];
                    $section_id=$ar_res['IBLOCK_SECTION_ID'];
                }else $section_id=0;
            }
            $sections=$arResult['SECTIONS'];
            $arResult['cat']=implode('/',array_reverse($arResult['SECTIONS']));
            unset($arResult['SECTIONS']);
            //должно быть обязательно только два параметра - brand и cat.
        }
        return $arResult;
    }

    public function getProductInfo($product_sku){
        $prod_res=$this->DB->getItemsList(array('sku'=>$product_sku),null,'product_info');
        return $prod_res;
    }

    public function setProductInfo($product_sku){
        if(!$this->getProductInfo($product_sku)) {
            $info = $this->getBitrixInfo($product_sku);
            if (isset($info['brand']) && isset($info['cat']) && count($info) == 2) {
                $info['sku']=$product_sku;
                $this->DB->insertItem('product_info',$info);
            }
        }
    }

    public function updateProductInfo($product_sku){
        $info=$this->getBitrixInfo($product_sku);
        if(isset($info['brand'])&&isset($info['cat'])&&count($info)==2){
            if($this->getProductInfo($product_sku)) $this->DB->editItem('product_info',array('sku'=>$product_sku),$info);
            else {
                $info['sku']=$product_sku;
                $this->DB->insertItem('product_info',$info);
            }
        }
    }

    public function setRule($arRule){
        if(isset($arRule['cat'])&&isset($arRule['brand'])&&isset($arRule['sku'])
            &&isset($arRule['class'])&&isset($arRule['type'])&&isset($arRule['direction'])&&isset($arRule['value'])){
            $this->DB->insertItem('product_rules',$arRule);
        }elseif(isset($arRule['id'])&&count($arRule)>1){
            $rule_id=$arRule['id'];
            unset($arRule['id']);
            $this->DB->editItem('product_rules',array('id'=>$rule_id),$arRule);
        }else throw new Exception("not enough parameters for setRule");
    }

    public function getRule($arRule){
        if(!empty($arRule)){
            $arRule=$this->DB->getItemsList($arRule,null,'product_rules');
            if(!empty($arRule)) return $arRule[0];
        }
        throw new Exception("error on getRule");
    }

    public function deleteRule($arRule){
        if(isset($arRule['id'])){
            $this->DB->deleteItem(array('id'=>$arRule['id']),'product_rules');
        }
    }

    public function getRules($arRule=null){
        $sortBy[]='class';
        $sortBy[]='ASC';
        return $this->DB->getItemsList($arRule,null,'product_rules',$sortBy);
    }

    public function getAlphaRules(){
        return $this->DB->getItemsList(array('class'=>'A'),null,'product_rules');
    }

    public function getRulesBySku($product_sku,$class='',$type=''){
        $info=$this->DB->getItemsList(array('sku'=>$product_sku),null,'product_info');
        $brand=$info[0]['brand'];
        $cat=$info[0]['cat'];
        $query="SELECT * FROM `product_rules` WHERE ((`sku`='$product_sku') OR (`brand`='$brand' AND '$cat' LIKE CONCAT(`cat`,'%')) OR (`brand`='$brand' AND `cat`='null') OR (`brand`='null' AND '$cat' LIKE CONCAT(`cat`,'%')))";
        if(!empty($class))$query.=" AND `class`='$class'";
        if(!empty($type))$query.=" AND `type`='$type'";
        $res=$this->DB->query($query);
        $ItemsList = array();
        if($res){
            while ($ar = $res->fetch(PDO::FETCH_ASSOC)) {
                $ItemsList[] = $ar;
            }
        }
        return $ItemsList;
    }

    public function getPriceByRule($price,$rule){
        if(is_numeric($rule))$arRule=$this->getRule(array('id'=>$rule));
        else $arRule=$rule;
        if(isset($arRule['type'])&&isset($arRule['direction'])&&isset($arRule['value'])){
            if($arRule['type']=='%'){
                if($arRule['direction']=='+')return round(0.0+$price+($price*($arRule['value']/100)));
                elseif($arRule['direction']=='-')return round(0.0+$price-($price*($arRule['value']/100)));
            }elseif($arRule['type']=='d'){
                if($arRule['direction']=='+')return $price+$arRule['value'];
                elseif($arRule['direction']=='-')return $price-$arRule['value'];
                elseif($arRule['direction']=='=')return $arRule['value'];
            }
        }
        throw new Exception("not enough parameters for setRule");
    }

    public function getPriceBySku($price,$product_sku,$class='B',$type='%d'){
        foreach($this->getRulesBySku($product_sku) as $rule){
            if($rule['class']==$class&&stristr($type,$rule['type'])) {
                $price = $this->getPriceByRule($price, $rule);
            }
        }
        return $price;
    }

    public function getAlphaPrice($price){
        foreach($this->getAlphaRules() as $rule){
            $price = $this->getPriceByRule($price, $rule);
        }
        return $price;
    }

    public function getCategories(){
        $arResult = array();

        $list_db = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), Array("IBLOCK_ID"=>self::IBLOCK_ID), false, Array("ID","NAME","DEPTH_LEVEL"));
        while($get=$list_db->fetch()){
            $path[$get['DEPTH_LEVEL']]=$get['NAME'];
            $name=array();
            for($i=1;$i<=$get['DEPTH_LEVEL'];$i++){
                $name[]=$path[$i];
            }
            $name=implode('/',$name);
            $arResult[$get['ID']]=$name;
        }

        return $arResult;
    }

    public function getBrands(){
        $arResult = array();

        $hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(2)->fetch();
        $hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
        $hlDataClass = $hldata['NAME'].'Table';

        $result = $hlDataClass::getList(array(
            'order' => array('UF_NAME' =>'ASC'),
            'select' => array('UF_XML_ID','UF_NAME')
        ));

        while($res = $result->fetch()){
            $arResult[$res['UF_XML_ID']]=$res['UF_NAME'];
        }

        return $arResult;
    }

    public function getVendorIcon($vendor){
        $arResult = 'no vendor';
        $hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(2)->fetch();
        $hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
        $hlDataClass = $hldata['NAME'] . 'Table';

        $result = $hlDataClass::getList(array(
            'order' => array('UF_NAME' => 'ASC'),
            'filter' => array('UF_XML_ID' => $vendor),
            'select' => array('UF_XML_ID', 'UF_FILE')
        ));

        if ($res = $result->fetch()) {
            $img=CFile::GetFileArray($res['UF_FILE']);
            if($img)$img=$img['SRC'];
            else $img='';
            $arResult = $img;
        }

        return $arResult;
    }
}