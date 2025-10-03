<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

class GalleryAjaxComponent extends CBitrixComponent implements Controllerable
{
    private $cacheTime = 3600;

    public function configureActions()
    {
        return [
            'load' => ['prefilters' => []],
        ];
    }

    public function executeComponent()
    {
        if (isset($this->arParams['CACHE_TIME'])) {
            $this->cacheTime = (int)$this->arParams['CACHE_TIME'];
        }

        $this->arResult['PAGE_SIZE'] = (int)($this->arParams['PAGE_SIZE'] ?? 12);
        $this->arResult['CURRENT_PAGE'] = 1;
        $items = $this->getItems(1, $this->arResult['PAGE_SIZE']);
        $items = $this->resizeImg($items);
        //$columns = $this->addColumns($items);
        //$this->arResult['COLUMNS'] = $columns['COLUMNS'];
        $this->arResult['COLUMNS'] = $items;

        $this->includeComponentTemplate();
    }

    public function resizeImg($items){
        foreach ($items as $key=>&$arr ){
            if(!empty($arr["DETAIL_PICTURE"] )){
                $newWidth = 297;
                $newHeight = 900;
                $file = CFile::ResizeImageGet($arr["DETAIL_PICTURE"], Array("width"=>$newWidth, "height"=>$newHeight), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                $arr['RESIZE_PREVIEW'] = $file;
                if(function_exists('makeWebp')){
                    $arr['RESIZE_PREVIEW']['src'] = makeWebp($file['src']);
                    $arr['SRC'] = makeWebp($arr['SRC']);
                } else {
                    $arr['RESIZE_PREVIEW']['src'] = $file['src'];
                    $arr['SRC'] = $arr['SRC'];
                }
            }
        }
        return $items;
    }

    public function addColumns($items){
        $columns = (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT'])) ? 2 : 4;

        $columnsCount = isset($this->arParams['COLUMNS_COUNT']) ? (int)$this->arParams['COLUMNS_COUNT'] : $columns;
        if($columnsCount <= 0) $columnsCount = 4;
        $columns = array_fill(0, $columnsCount, []);
        $i = 0;
        foreach ($items as $elem) {
            $columns[$i % $columnsCount][] = $elem;
            $i++;
        }
        return ['COLUMNS' => $columns];
    }

    public function loadAction($page)
    {
        $pageSize = $this->arParams['PAGE_SIZE'];
        $items = $this->getItems((int)$page, $pageSize);
        $items = $this->resizeImg($items);

        return [
            'html' => $items,
            'hasMore' => count($items) == $pageSize
        ];
    }

    /**
     * Получение элементов с кэшированием
     */
    private function getItems($page, $pageSize)
    {
        Loader::includeModule('iblock');

        $cache = Cache::createInstance();
        $iblockId = intval($this->arParams['IBLOCK_ID']);
        $cacheId = 'gallery_items_vue_bx' . $iblockId . "_{$page}_{$pageSize}";
        $cacheDir = '/gallery_ajax_vue_bx/';
        if ($cache->initCache($this->cacheTime, $cacheId, $cacheDir)) {
            $items = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $arFilter = [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y'
            ];

            $arSelect = ['ID', 'NAME', 'DETAIL_PICTURE'];
            $arNavParams = [
                "iNumPage" => $page,
                "nPageSize" => $pageSize,
                'bShowAll' => false
            ];

            $res = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], $arFilter, false, $arNavParams, $arSelect);

            $items = [];
            while ($row = $res->GetNext()) {
                if (empty($row['DETAIL_PICTURE'])) continue;
                $items[] = [
                    'ID' => $row['ID'],
                    'SRC' => CFile::GetPath($row['DETAIL_PICTURE']),
                    'DETAIL_PICTURE' => $row['DETAIL_PICTURE'],
                    'NAME' => $row['NAME'],
                ];
            }
            $cache->endDataCache($items);
        } else {
            $items = [];
        }

        return $items;
    }
}
