<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Component\ParameterSigner;

CJSCore::Init(['ajax']);

$signer = new ParameterSigner();
$signedParameters = $signer->signParameters($component->getName(), $arParams);
$componentName = $component->getName();
?>
<?
$this->addExternalJS("https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js");
$this->addExternalJS("https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js");
$this->addExternalJS("https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js");
$this->addExternalCss("https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css");
?>
<div class="gallery-grid gallery_masonry_wrapper" id="gallery-container">
    <?foreach($arResult["COLUMNS"] as $key => $column){?>
        <div id="column_<?=$key?>" class="column_gallery">
            <?foreach($column as $arItem){?>
                <div class="gallery-item" data-id="<?=htmlspecialcharsbx($arItem['ID'])?>">
                    <a href="<?=htmlspecialcharsbx($arItem["SRC"])?>" data-fancybox="gallery" >
                        <img
                                data-src="<?=htmlspecialcharsbx($arItem["RESIZE_PREVIEW"]["src"])?>"
                                class="element-item persent-size lazyload fade-img"
                                width="<?=intval($arItem['RESIZE_PREVIEW']['WIDTH'])?>"
                                height="<?=intval($arItem['RESIZE_PREVIEW']['HEIGHT'])?>"
                                alt="<?=htmlspecialcharsbx($arItem['RESIZE_PREVIEW']['ALT'] ?? $arItem['NAME'])?>"
                                title="<?=htmlspecialcharsbx($arItem['RESIZE_PREVIEW']['TITLE'] ?? $arItem['NAME'])?>"
                        />
                    </a>
                </div>
            <?}?>
        </div>
    <?}?>
</div>
<div id="observer-trigger" style="height: 40px;"></div>

<script>
    document.addEventListener("DOMContentLoaded", function(){
        let currentPage = <?= isset($arResult['CURRENT_PAGE']) ? (int)$arResult['CURRENT_PAGE'] : 1 ?>;
        let loading = false;
        const container = document.getElementById("gallery-container");
        const trigger = document.getElementById("observer-trigger");
        const columns = Array.from(document.querySelectorAll('.column_gallery'));
        let colIndex = 0;

        function loadNextPage() {
            if (loading) return;
            loading = true;
            currentPage++;

            BX.ajax.runComponentAction("custom:gallery_ajax", "load", {
                mode: "class",
                data: {
                    page: currentPage,
                    signedParameters: '<?= CUtil::JSEscape($signedParameters) ?>'
                }
            }).then(function(response){
                const html = response.data.html || "";
                if (html.trim() === "") {
                    if (!response.data.hasMore) {
                        observer.disconnect();
                    }
                    loading = false;
                    return;
                }

                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItems = Array.from(temp.children);

                newItems.forEach(item => {
                    const targetColumn = columns[colIndex % columns.length];
                    targetColumn.appendChild(item);
                    colIndex++;
                });

                if (!response.data.hasMore) {
                    observer.disconnect();
                }

                loading = false;
            }).catch(function(err){
                console.error('AJAX error', err);
                loading = false;
            });
        }

        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && !loading) {
                loadNextPage();
            }
        }, { root: null, rootMargin: '400px', threshold: 0.1 });

        if ('IntersectionObserver' in window) {
            observer.observe(trigger);
        } else {
            // fallback для старых браузеров
            window.addEventListener('scroll', function(){
                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 800 && !loading) {
                    loadNextPage();
                }
            });
        }
    });
</script>

