<?php
/**
 * Created by PhpStorm.
 * User: kan
 * Date: 28.09.15
 * Time: 19:36
 */
include_once 'prolog.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rules</title>
    <link rel="stylesheet" href="style.css">
    <script src="/js/jquery.js" type="text/javascript"></script>
    <script src="srcipt.js" type="text/javascript"></script>
</head>
<body><?=$rul->getPriceBySku(1000,111003,'F', 'd')?><?//print_r($rul->getRulesBySku(69864,'B','%'))?>
<div class="add">
    <form method="post">
        <select class="cat" name="rule[cat]">
            <option value="null">Категория</option>
            <?foreach($rul->getCategories() as $section){?>
                <option value="<?=$section?>"><?=$section?></option>
            <?}?>
        </select>
        <select class="brand" name="rule[brand]">
            <option value="null">Бренд</option>
            <?foreach($rul->getBrands() as $brand_code=>$brand){?>
                <option value="<?=$brand_code?>"><?=$brand?></option>
            <?}?>
        </select>
        <label>Код товара: <input class="sku" type="text" name="rule[sku]" value="0"></label><br>
        <select class="class" name="rule[class]">
            <option value="B">Цена 1С</option>
            <option value="S">Спеццена</option>
            <option value="A">Общая цена</option>
            <option value="F">Окончательная цена</option>
        </select>
        <select class="direction" name="rule[direction]">
            <option value="+">+</option>
            <option value="-">-</option>
            <option value="=">=</option>
        </select>
        <input class="value" type="text" name="rule[value]">
        <select class="type" name="rule[type]">
            <option value="%">%</option>
            <option value="d">р</option>
        </select>
        <input type="submit" value="Создать правило">
    </form>
</div>
<button class="showhide">Доп. записи</button>
<div class="edit">
    <form method="post">
    <?foreach ($rul->getRules() as $rule){?>
        <div class="rule <?=(!empty($rule['sku'])&&$rule['direction']=='=')?'hided':''?>">
            <?if($rule['cat']!='null'){?>
                <span class="cat"><?=$rule['cat']?></span>
            <?}?>
            <?if($rule['brand']!='null'){?>
                <img class="brand" src="//www.santehsmart.ru<?print_r($rul->getVendorIcon($rule['brand']))?>">
            <?}?>
            <?if($rule['sku']!=0){?>
                <span class="sku"><?=$rule['sku']?></span>
            <?}?>
            <select class="direction" name="change[<?=$rule['id']?>][direction]">
                <option value="+" <?=($rule['direction']=='+')?'selected':''?>>+</option>
                <option value="-" <?=($rule['direction']=='-')?'selected':''?>>-</option>
                <option value="=" <?=($rule['direction']=='=')?'selected':''?>>=</option>
            </select>
            <input class="value" type="text" name="change[<?=$rule['id']?>][value]" value="<?=$rule['value']?>">
            <select class="type" name="change[<?=$rule['id']?>][type]">
                <option value="%" <?=($rule['type']=='%')?'selected':''?>>%</option>
                <option value="d" <?=($rule['type']=='d')?'selected':''?>>р</option>
            </select>
            <?if($rule['class']!='A'){?>
                <input class="delete" type="checkbox" name="change[<?=$rule['id']?>][delete]" title="Удалить правило">
            <?}?>
            <?=$rule['class']=='S'?'(спеццена)':''?>
            <?=$rule['class']=='F'?'(FINAL)':''?>
        </div>
    <?}?>
        <input type="submit" value="Сохранить изменения">
    </form>
</div>
</body>
