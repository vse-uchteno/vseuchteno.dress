<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/*
  #####################################################
  # Bitrix: Modules and Components tests              #
  # Copyright (c) 2020 D.Starovoytov (VseUchteno)     #
  # mailto:denis@starovoytov.online                   #
  #####################################################
 */
use Bitrix\Iblock\PropertyTable as Property;
use Bitrix\Iblock\ElementPropertyTable as ElementProperties;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Diag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

/**
 * Component class for test by dress update
 */
class CVseuchtenoDress extends CBitrixComponent
{
    
        private $propertyCode;    
	
	public function executeComponent()
	{
            
                $this->propertyCode = $this->getPropertyCodeById($this->arParams['DRESS_IBLOCK_PROPERTY_COLLECTION_ID']);
            
                $this->arResult['PROCESS_STATE'] = "";

                $request = Application::getInstance()->getContext()->getRequest();

                /*Начали обработку*/
                if ($request->getPost("process") == "Y") {

                    /*
                     * Если по условиям задачи коллекций не более 100, а товаров не менее 20.000, очевидно что обходить все товары мы не будем.
                     * Мы сформируем список всех существующих коллекций из БД и запросим по ним описания через API.
                     */
                    $arCollectionsMatrix=$this->getCollections();
                    if (!empty($arCollectionsMatrix)) {
                        $allCount=0;
                        foreach ($arCollectionsMatrix as $collectionName => $collectionDescription) {
                            $allCount+=$this->setElementsDescriptionByCollection($collectionName,$collectionDescription);
                        }
                        $this->arResult['PROCESS_STATE'] = sprintf(Loc::getMessage("VU_SUCCESS"),$allCount);
                        
                    } else {
                        $this->arResult['PROCESS_STATE'] = Loc::getMessage("VU_NO_COLLECTIONS");
                    }
                    

                }
                
                $this->includeComponentTemplate();
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
         * 
         * Вообще правильнее было бы повесить этот кэш на стандартный кэш компонента, чтобы лече было его сбросить, но поздно осенило.
	 */
	private function getCollections() 
	{

		$result=[];
		
		$cache = Cache::createInstance();
		if ($cache->initCache($this->arParams['API_COLLECTION_CACHE_TTL'], $this->getName())) { 
			$vars = $cache->getVars(); 
			$result=$vars;
		}
		elseif ($cache->startDataCache()) {
			/*
			 * В реальных условиях, я бы конечно вынес формирование этого кэша в Агент
			 * Все таки 100 коллекций и не менее 1 секунды запрос в API - это уже минимум 100 сек. Не каждый конфиг веб-сервера дождется.
			 * В данном случае я и не буду делать всяких костылей типа ini_set('max_execution_time', 0) не зная ничего о среде.
			 */
			$collections=$this->getAllCollectionFromProductsDB();
                        foreach ($collections as $collectionName => $collectionValue) {
                            $result[$collectionName] = $this->getCollectionDescriptionByNameAPI($collectionName);
                        }
			
			$cache->endDataCache($result); 
		}
		
		return $result;
	}

	
	/*
	 * Устанавливает  в описание элементам, описание коллекции 
	 */
	private function setElementsDescriptionByCollection($collectionName, $collectionDescription) 
	{
		$processCount=0;
                /*Получаем Коллекцию (в смысле ORM) элементов принадлежащих данной коллеции*/
                $elemetsCollection=$this->getElementsByCollectionName($collectionName);
                /*
                 * По нормальному конечно нужно сделать пошаговый скрипт, обрабатывающий ограниченное число элементов.
                 * Но при всех итак сделаных допусках и напусках.... лень 
                 */
                foreach($elemetsCollection as $element) {
                    $processCount++;
                    $element->setDetailText($collectionDescription);
                }
                /* Сохраню всю коллекцию целиком */
                $elemetsCollection->save(true);
		
                return $processCount;
	}

        /*
         * Метод возвращает коллекцию элементов
         */
	private function getElementsByCollectionName($collectionName) 
	{
		
                $iblock = \Bitrix\Iblock\Iblock::wakeUp($this->arParams['DRESS_IBLOCK_ID']);
                $arCollectionsResult = $iblock->getEntityDataClass()::getList([
                        'select' => ['ID','NAME'],
                        'filter' => [$this->propertyCode.'.VALUE' => $collectionName],
                ])->fetchCollection();

		return $arCollectionsResult;
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
            if ($this->arParams['API_COLLECTION_DEBUG'] == "Y") {
                return Loc::getMessage("VU_DEBUG_DESCRIPTION")." ".$name;
            }
            
            return $this->httpGet('collection', ['collection' => $name]);
	}

        
        /*
         * Обертка для запроса на API
         */
	private function httpGet($method, array $params = array())
	{
		$httpClient = new HttpClient();
                $response="";
                try {
                    $response = $httpClient->get($this->arParams['API_COLLECTION_URL'] . $method."/?". http_build_query($params));
                } catch (Exception $exc) {
                    Diag\Debug::writeToFile($this->arParams['API_COLLECTION_URL'] . $method."/?". http_build_query($params), "httpGetRequest", $this->getName()."_".date("Ymd").".txt");
                    Diag\Debug::writeToFile($exc->getTraceAsString(), "httpGetResponce", $this->getName()."_".date("Ymd").".txt");
                }
		return $response;
	}

                
	/*
	 * Получает код свойства по его ID
	 */
	private function getPropertyCodeById($property_id) 
	{
		$arProperty = Property::getRowById($property_id);
		return $arProperty['CODE'];
	}

	
}
