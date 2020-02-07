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
?>
<? if (isset($arResult['PROCESS_STATE'])&&($arResult['PROCESS_STATE'] != "")) { ?>
<p><?=$arResult['PROCESS_STATE']?></p>
<? } ?>


<form action="" method="POST">
    <input type="hidden" name="process" value="Y">
    <input type="submit" value="<?=GetMessage('VU_SUBMIT_TEXT')?>">
</form>
