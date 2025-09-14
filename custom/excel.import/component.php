<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require_once __DIR__ . '/class.php';

$component = new CustomExcelImportComponent($this);
$component->executeComponent();
