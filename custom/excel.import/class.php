<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/libs/vendor/autoload.php';

use Bitrix\Main\Loader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Bitrix\Iblock\SectionTable;

class CustomExcelImportComponent extends CBitrixComponent
{
    const IBLOCK_ID = 110;
    const BRANDS_IBLOCK_ID = 128;
    const PRICE_TYPE_SPECIALIST = 3;
    const PRICE_TYPE_CLIENT = 2;

    private $headerMap = [
        'BRAND' => 0,
        'TYPE_PRODUCT' => 1,
        'NAME' => 2,
        'SECTION_NAME' => 3,
        'VOLUME' => 4,
        'PRICE_SPECIALIST' => 5,
        'VAT_INCLUDED' => 6,
        'PRICE_CLIENT' => 7,
        'DESCRIPTION' => 8,
        'ARTICLE' => 9,
        'XML_ID' => 10,
        'UUID' => 11,
    ];
    
/*    public function onPrepareComponentParams($arParams)
    {
        $arParams["CACHE_TIME"] = $arParams["CACHE_TIME"] ?? 3600;
        return $arParams;
    }*/

    public function executeComponent()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        $this->arResult['ERRORS'] = [];
        $this->arResult['SUCCESS'] = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
            $this->handleUpload();
        }

        $this->includeComponentTemplate();
    }

    private function handleUpload()
    {
        $file = $_FILES['excel_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->arResult['ERRORS'][] = 'Ошибка при загрузке файла.';
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls', 'xlsx'])) {
            $this->arResult['ERRORS'][] = 'Неподдерживаемый формат файла.';
            return;
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/import_price/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

        $filePath = $uploadDir . 'price_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->arResult['ERRORS'][] = 'Ошибка сохранения файла.';
            return;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $this->processSheet($sheet);
            }
            $this->arResult['SUCCESS'] = true;
        } catch (\Exception $e) {
            $this->arResult['ERRORS'][] = 'Ошибка Excel: ' . $e->getMessage();
        }
    }

    private function processSheet($sheet)
    {
        $rowIndex = 0;
        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;
            if ($rowIndex === 1) continue;

            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim($cell->getFormattedValue());
            }

            if (empty($cells[$this->headerMap['NAME']])
                || mb_strtolower($cells[$this->headerMap['ARTICLE']]) === 'article'
                || mb_strtolower($cells[$this->headerMap['NAME']]) === 'название') {
                continue;
            }

            $name = $cells[$this->headerMap['NAME']];
            $sectionId = $this->getOrCreateSection($cells[$this->headerMap['SECTION_NAME']]);
            $brandId = $this->getOrCreateBrandId($cells[$this->headerMap['BRAND']]);
            $uuid = trim($cells[$this->headerMap['UUID']] ?? '');
            $imageTmp = $this->downloadImageFromYandex($uuid);

            $fields = [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'IBLOCK_SECTION_ID' => $sectionId,
                'NAME' => $name,
                'PREVIEW_TEXT' => TruncateText(strip_tags($cells[$this->headerMap['DESCRIPTION']]), 150),
                'DETAIL_TEXT' => $cells[$this->headerMap['DESCRIPTION']],
                'CODE' => CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '']),
                'ACTIVE' => 'Y',
                'PROPERTY_VALUES' => [
                    'BRAND' => $brandId,
                    'PROP_2026' => $cells[$this->headerMap['VOLUME']],
                    'TYPE_PRODUCT' => $cells[$this->headerMap['TYPE_PRODUCT']],
                    'ITEM_TEMPLATE' => 3885,
                ]
            ];

            if ($imageTmp) {
                $fields['PREVIEW_PICTURE'] = $imageTmp;
                $fields['DETAIL_PICTURE'] = $imageTmp;
            }

            $article = $cells[$this->headerMap['ARTICLE']];
            $fields['PROPERTY_VALUES']['ARTICLE'] = $article;

            $res = CIBlockElement::GetList([], [
                'IBLOCK_ID' => self::IBLOCK_ID,
                '=PROPERTY_ARTICLE' => $article
            ], false, false, ['ID']);

            $el = new CIBlockElement;
            if ($item = $res->Fetch()) {
                $el->Update($item['ID'], $fields);
                $productId = $item['ID'];
            } else {
                $productId = $el->Add($fields);
                if ($productId) {
                    CCatalogProduct::Add(["ID" => $productId, "QUANTITY" => 5, "TYPE" => 1]);
                }
            }

            if ($productId) {
                $this->setPrices($productId, $cells[$this->headerMap['PRICE_CLIENT']], $cells[$this->headerMap['PRICE_SPECIALIST']]);
            }
        }
    }

    /*private function downloadImageFromYandex($uuid)
    {
        if (!$uuid) return null;

        $token = YA_TOKEN_DISK;
        //$extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'];

        //foreach ($extensions as $ext) {
            //$diskPath = "disk:/2/{$uuid}.{$ext}";
            $diskPath = YA_DISK_PUTH.$uuid;я

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://cloud-api.yandex.net/v1/disk/resources/download?path=" . urlencode($diskPath));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: OAuth $token"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            if (!empty($data['href'])) {
                $fileData = file_get_contents($data['href']);
                $tmpFile = tempnam(sys_get_temp_dir(), 'yadisk_');
                file_put_contents($tmpFile, $fileData);
                $imageTmp = CFile::MakeFileArray($tmpFile);
                //$imageTmp['name'] = "{$uuid}.{$ext}";
                $imageTmp['name'] = "{$uuid}";
                $imageTmp['MODULE_ID'] = 'main';
                return $imageTmp;
            }
        //}

        $this->arResult['ERRORS'][] = "Не удалось загрузить изображение для UUID: {$uuid}";
        return null;
    }*/
    private function downloadImageFromYandex($publicUrl)
    {
        if (!$publicUrl) return null;

        $url = "https://cloud-api.yandex.net/v1/disk/public/resources/download?public_key=" . urlencode($publicUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!empty($data['href'])) {
            $fileData = file_get_contents($data['href']);
            $tmpFile = tempnam(sys_get_temp_dir(), 'yadisk_');
            file_put_contents($tmpFile, $fileData);

            $imageTmp = CFile::MakeFileArray($tmpFile);
            $imageTmp['name'] = basename(parse_url($publicUrl, PHP_URL_PATH)) . '.jpg';
            $imageTmp['MODULE_ID'] = 'main';

            return $imageTmp;
        }

        $this->arResult['ERRORS'][] = "Не удалось скачать файл с публичной ссылки: {$publicUrl}";
        return null;
    }

    private function parsePrice($price)
    {
        $price = preg_replace('/[\s\xA0]/u', '', $price);
        if (strpos($price, ',') !== false && strlen($price) - strrpos($price, ',') <= 3) {
            $price = str_replace('.', '', $price);
            $price = str_replace(',', '.', $price);
        } else {
            $price = str_replace(',', '', $price);
        }
        return floatval($price);
    }

    /*private function getOrCreateSection($name)
    {
        if (trim($name) === '') return 0;

        $section = SectionTable::getList([
            'filter' => ['IBLOCK_ID' => self::IBLOCK_ID, 'NAME' => $name],
            'select' => ['ID'],
        ])->fetch();

        if ($section) return $section['ID'];

        $code = CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '', 'change_case' => 'L']);
        $bs = new CIBlockSection;
        return $bs->Add([
            'IBLOCK_ID' => self::IBLOCK_ID,
            'NAME' => $name,
            'CODE' => $code,
            'ACTIVE' => 'Y',
        ]) ?: 0;
    }*/
    private function getOrCreateSection($name)
    {
        if (trim($name) === '') return 0;

        // Генерируем CODE из имени — в нижнем регистре
        $code = CUtil::translit($name, 'ru', [
            'replace_space' => '-',
            'replace_other' => '',
            'change_case' => 'L' // lowercase
        ]);

        // Ищем раздел по IBLOCK_ID и CODE (без учёта регистра имени)
        $section = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'CODE' => $code,
            ],
            'select' => ['ID'],
        ])->fetch();

        if ($section) {
            return $section['ID'];
        }

        // Создаём раздел с оригинальным именем (с заглавной буквы) и CODE
        $bs = new CIBlockSection;
        return $bs->Add([
            'IBLOCK_ID' => self::IBLOCK_ID,
            'NAME' => $name,
            'CODE' => $code,
            'ACTIVE' => 'Y',
        ]) ?: 0;
    }

    private function getOrCreateBrandId($name)
    {
        $name = trim($name);
        if ($name === '') return null;

        $res = CIBlockElement::GetList([], [
            'IBLOCK_ID' => self::BRANDS_IBLOCK_ID,
            'NAME' => $name
        ], false, false, ['ID']);

        if ($item = $res->Fetch()) return (int)$item['ID'];

        $el = new CIBlockElement;
        return $el->Add([
            'IBLOCK_ID' => self::BRANDS_IBLOCK_ID,
            'NAME' => $name,
            'ACTIVE' => 'Y',
        ]) ?: null;
    }

    private function setPrices($productId, $client, $specialist)
    {
        $priceClient = $this->parsePrice($client);
        $priceSpecialist = $this->parsePrice($specialist);

        CPrice::SetBasePrice($productId, $priceClient, 'RUB');

        $res = CPrice::GetList([], [
            "PRODUCT_ID" => $productId,
            "CATALOG_GROUP_ID" => self::PRICE_TYPE_SPECIALIST
        ]);

        if ($price = $res->Fetch()) {
            CPrice::Update($price["ID"], ["PRICE" => $priceSpecialist, "CURRENCY" => "RUB"]);
        } else {
            CPrice::Add([
                "PRODUCT_ID" => $productId,
                "CATALOG_GROUP_ID" => self::PRICE_TYPE_SPECIALIST,
                "PRICE" => $priceSpecialist,
                "CURRENCY" => "RUB"
            ]);
        }
    }
}