<?php
/**
 * 통합검색에서 넘어온 페이지에 대한 로거 측정을 위한 페이지
 * IFRAME 사용
 */
require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/web/util/DNWInput.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/global_header_predefine.inc';

$oDNWInput = new DNWInput();

$nCate1 = $oDNWInput->getInt('cate1');
$nCate2 = $oDNWInput->getInt('cate2');
$nCate3 = $oDNWInput->getInt('cate3');
$nCate4 = $oDNWInput->getInt('cate4');
$sPageType = $oDNWInput->getString('pageType');

if($sPageType == 'searchToBlog'){
	$sPageType = '통합검색_상품블로그';
}else if($sPageType == 'searchToList'){
	$sPageType = '통합검색_상품리스트';
}else if($sPageType == 'searchToBridge'){
	$sPageType = '통합검색_사러가기';
}

if($nCate1 == 0 || $nCate2 == 0){
	exit;
}

$nSiteCode		= $oSiteCate->intGetSiteCodeFromCategory($nCate1, $nCate2);
$sSiteTextName  = $oSiteInfo->getSiteString($nSiteCode);
$sSiteName      = $oSiteInfo->getSiteName($nSiteCode);

if(file_exists(GLOBAL_GENFILES_PATH.'/PHP/Category_ArrayPHP_'.$sSiteTextName.'.inc')){
	require_once GLOBAL_GENFILES_PATH.'/PHP/Category_ArrayPHP_'.$sSiteTextName.'.inc';

	$aTempCateCodeList1 = ${'aPreSetCateCode1_'.$sSiteTextName};
	$aTempCateCodeList2 = ${'aPreSetCateCode2_'.$sSiteTextName};
	$aTempCateCodeList3 = ${'aPreSetCateCode3_'.$sSiteTextName};
	$aTempCateCodeList4 = ${'aPreSetCateCode4_'.$sSiteTextName};

	$aTempCateNameList1 = ${'aPreSetCateName1_'.$sSiteTextName};
	$aTempCateNameList2 = ${'aPreSetCateName2_'.$sSiteTextName};
	$aTempCateNameList3 = ${'aPreSetCateName3_'.$sSiteTextName};
	$aTempCateNameList4 = ${'aPreSetCateName4_'.$sSiteTextName};

	if(count($aTempCateCodeList1) > 0){
		$nCateKeyIdx = array_search($nCate1,$aTempCateCodeList1);
		$sCateName1 = $aTempCateNameList1[$nCateKeyIdx];
		$sLoggerStr = $sPageType;
		$sLoggerStr .= '/' . str_replace('/',',',$sCateName1);
	}

	if($nCate2 > 0 && count($aTempCateCodeList2[$nCate1]) > 0){
		$nCateKeyIdx = array_search($nCate2, $aTempCateCodeList2[$nCate1]);
		$sCateName2 = $aTempCateNameList2[$nCate1][$nCateKeyIdx];
		$sLoggerStr .= '/' . str_replace('/',',',$sCateName2);
	}

	if($nCate3 > 0 && count($aTempCateCodeList3[$nCate2]) > 0){
		$nCateKeyIdx = array_search($nCate3, $aTempCateCodeList3[$nCate2]);
		$sCateName3 = $aTempCateNameList3[$nCate2][$nCateKeyIdx];
		$sLoggerStr .= '/' . str_replace('/',',',$sCateName3);
	}

	if($nCate4 > 0 && count($aTempCateCodeList4[$nCate3]) > 0){
		$nCateKeyIdx = array_search($nCate4, $aTempCateCodeList4[$nCate3]);
		$sCateName4 = $aTempCateNameList4[$nCate3][$nCateKeyIdx];
		$sLoggerStr .= '/' . str_replace('/',',',$sCateName4);
	}
}

if($sLoggerStr != ''){
?>
<script type="text/javascript">
_TRK_CP = "<?=$sLoggerStr?>";
</script>
<script src="//static.danawa.com/js/logger_Insight_WebAnalytics.js"></script>
<?php
}
?>