<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    "NAME" => GetMessage("CUSTOM_NAME"),
    "DESCRIPTION" => GetMessage("CUSTOM_COMP__DESC"),
    "SORT" => 40,
    "PATH" => [
        "ID" => "custom",
        "NAME" => GetMessage("CUSTOM_COMP_NAME"),
    ],
];