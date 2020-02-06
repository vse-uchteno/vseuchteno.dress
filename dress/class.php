<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/*
  #####################################################
  # Bitrix: Modules and Components tests              #
  # Copyright (c) 2020 D.Starovoytov (VseUchteno)     #
  # mailto:denis@starovoytov.online                   #
  #####################################################
 */
use Bitrix\Iblock\ElementTable as Elements;
use Bitrix\Iblock\ElementPropertyTable as ElementProperties;
use \Bitrix\Main\Data\Cache;

/**
 * Component class for test by dress update
 */
class CVseuchtenoDress extends CBitrixComponent
{
	
	private static $collections_cache_ttl=60*60*24;

	public function executeComponent()
	{

		/*
		 * Если по условиям задачи коллекций не более 100, а товаров не менее 20.000, очевидно что обходить все товары мы не будем.
		 * Мы сформируем список всех существующих коллекций из БД и запросим по ним описания через API.
		 */
		$arCollectionsMatrix=$this->getAllCollectionFromProductsDB();
		echo("<pre>");print_r($arCollectionsMatrix);echo("<pre>");
	}
	

	/*
	 * Получает готовый список коллекций с описаниями.
	 * Если он уже есть в кэше то берет из кэша
	 * Если кэша нет или у него истек TTL, то сформируем эти данные заново
	 * 
	 * Примечание:
	 * Наверное не очень правильно сохранять в кэше, и после формировать из них массив в памяти данные, 
	 * объем которых я не могу оценить, чтобы на выходе не получить в ОЗУ гигантский массив (например из поля LONGTEXT на стороне API).
	 * 
	 * В обычных условиях я бы предварительно ознакомился со спецификацией API и возможно принял решение хранить описания Коллекций например в HLBlock
	 * Но сделаю допущение, что я получаю обычный текст.
	 */
	private function getCollections() 
	{

		$result=[];
		
		$cache = Cache::createInstance();
		if ($cache->initCache(self::$collections_cache_ttl, $this->getName())) { 
			$vars = $cache->getVars(); 
			$result=$vars;
		}
		elseif ($cache->startDataCache()) {
			/*
			 * В реальных условиях, я бы конечно вынес формирование этого кэша в Агент
			 * Все таки 100 колекций и не менее 1 секунды запрос в API - это уже минимум 100 сек. Не каждый конфиг веб-сервера дождется.
			 * В данном случае я и не буду делать всяких костылей типа ini_set('max_execution_time', 0) не зная ничего о среде.
			 * 
			 * В данном случае, я просто буду проверять наличие в кэше каждой коллекции перед тем как делать запрос, если он уже есть то запрос и не делать.
			 */
			
			
			$cache->endDataCache(array("key" => "value")); // записываем в кеш
		}
		
		return $result;
	}
	
	/*
	 * Получает список коллекций из БД
	 */
	private function getAllCollectionFromProductsDB() 
	{
		$result=[];
		
		$select=[
			'VALUE',
		];

		$filter=[
			'IBLOCK_PROPERTY_ID' => $this->arParams['DRESS_IBLOCK_PROPERTY_COLLECTION_ID'],
		];
		$arCollectionsResult = ElementProperties::getList([
			'select'  => $select,
			'filter'  => $filter,
			'group'   => ['VALUE'],
		])->fetchAll();;
		
		foreach ($arCollectionsResult as $collectionsItem) {
			$collectionName=$collectionsItem['VALUE'];
			$result[$collectionName]=$collectionName;
		}
		
		return $result;
	}

	
	/*
	 * Получает описание коллекции по её наименованию по API
	 */
	private function getCollectionDescriptionByNameAPI($name) 
	{
	}
	
	
}
