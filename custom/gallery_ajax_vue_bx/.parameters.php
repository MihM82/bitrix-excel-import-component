<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Iblock;

// подключаем языковые фразы (если нужно)
Loc::loadMessages(__FILE__);

if (!Loader::includeModule("iblock")) {
    return;
}

// Получаем список инфоблоков для выпадающего списка
$arIBlocks = [];
$res = CIBlock::GetList(
    ["SORT" => "ASC"],
    ["ACTIVE" => "Y"]
);
while ($ar = $res->Fetch()) {
    $arIBlocks[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

$arComponentParameters = [
    "PARAMETERS" => [
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("MY_GALLERY_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => "",
            "ADDITIONAL_VALUES" => "Y",
        ],
        "PAGE_SIZE" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("MY_GALLERY_PAGE_SIZE"),
            "TYPE" => "STRING",
            "DEFAULT" => "8",
        ],
        "CACHE_TIME" => ["DEFAULT" => 3600],
    ],
];
