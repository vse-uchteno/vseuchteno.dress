<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\TypeLanguageTable;

$arIblockTypes[]='';
$arIblocks[]='';
$arIblockProperties[]='';


$arIblockTypesFilter=array(
	'LANGUAGE_ID' => SITE_ID,
);
$arIblockTypesResult=TypeLanguageTable::getList(array('filter' => $arIblockTypesFilter))->fetchAll();
foreach($arIblockTypesResult as $arIblockTypeItem) {
	$arIblockTypes[$arIblockTypeItem['IBLOCK_TYPE_ID']] = $arIblockTypeItem['NAME'];
}

if (isset($arCurrentValues['DRESS_IBLOCK_TYPE_ID']) && ($arCurrentValues['DRESS_IBLOCK_TYPE_ID'] != "")) {
	
	$arIblocksFilter=array(
		'IBLOCK_TYPE_ID' => $arCurrentValues['DRESS_IBLOCK_TYPE_ID'],
	);
	$arIblocksResult=IblockTable::getList(array('filter' => $arIblocksFilter))->fetchAll();
	foreach($arIblocksResult as $arIblockItem) {
		$arIblocks[$arIblockItem['ID']] = $arIblockItem['NAME'];
	}
	
}

if (isset($arCurrentValues['DRESS_IBLOCK_ID']) && ($arCurrentValues['DRESS_IBLOCK_ID'] != "")) {
	
	$arIblockPropertiesFilter=array(
		'IBLOCK_ID' => $arCurrentValues['DRESS_IBLOCK_ID'],
		'PROPERTY_TYPE' => array('S'),
	);
	$arIblockPropertiesResult=PropertyTable::getList(array('filter' => $arIblockPropertiesFilter))->fetchAll();
	foreach($arIblockPropertiesResult as $arIblockPropertiesItem) {
		$arIblockProperties[$arIblockPropertiesItem['ID']] = $arIblockPropertiesItem['NAME'];
	}
	
}



$arComponentParameters = array(
	'PARAMETERS'=>array(
		"API_COLLECTION_URL"=>array(
			"PARENT"=>"BASE",
			"NAME"=>GetMessage("VU_API_COLLECTION_URL"),
			"TYPE"=>"STRING",
			"MULTIPLE"=>"N",
			"DEFAULT"=>"https://sebekon.ru/api/v2/collection/",
			"COLS"=>25
		),
		"API_COLLECTION_CACHE_TTL"=>array(
			"PARENT"=>"BASE",
			"NAME"=>GetMessage("VU_API_COLLECTION_CACHE_TTL"),
			"TYPE"=>"STRING",
			"MULTIPLE"=>"N",
			"DEFAULT"=>"21600",
			"COLS"=>25
		),
		"DRESS_IBLOCK_TYPE_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_DRESS_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arIblockTypes,
			"REFRESH" => "Y"
		),
		"DRESS_IBLOCK_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_DRESS_IBLOCK"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arIblocks,
			"REFRESH" => "Y"
		),
		"DRESS_IBLOCK_PROPERTY_COLLECTION_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_DRESS_IBLOCK_PROPERTY_COLLECTION"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arIblockProperties,
		),
	),
);
?>