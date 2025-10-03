<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Component\ParameterSigner;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Page\Asset;
\Bitrix\Main\UI\Extension::load("ui.vue3");
$this->setFrameMode(true);
CJSCore::Init(['ajax']);
$signer = new ParameterSigner();
$signedParameters = $signer->signParameters($component->getName(), $arParams);
$componentName = $component->getName();

$this->addExternalJS("https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js");
$this->addExternalJS("https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js");
$this->addExternalJS("https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js");
$this->addExternalCss("https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css");
?>
<div id="app" v-clock></div>

<script>
    const arResult = <?=Bitrix\Main\Web\Json::encode($arResult)?>
</script>

<script>
    // Передаем данные из PHP в JavaScript
    const vueImgData = <?= \Bitrix\Main\Web\Json::encode([
        'items' => $arResult['COLUMNS'],
        'siteId' => $arResult['SITE_ID'],
        'ajaxPath' => $arResult['AJAX_PATH'],
        'params' => [
                    'ajaxComponent' => $this->getComponent()->getName(),
                    'signedParams' => $signedParameters,
                    'startPage' => (int)($arResult['CURRENT_PAGE'] ?? 1) ,
                    'currentPage'=> $arResult['CURRENT_PAGE']
        ]
    ]) ?>;
</script>

<script src="<?= $this->GetFolder() ?>/js/components/imgItem.js"></script>
<script src="<?= $this->GetFolder() ?>/js/components/galleryImg.js"></script>
<script src="<?= $this->GetFolder() ?>/js/app.js"></script>
