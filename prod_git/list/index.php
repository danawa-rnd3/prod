<?php
/**
 * 상품리스트 index
 * @author eprkswu@danawa.com
 * @version 1.0.0
 */
header('Content-Type:text/html; charset=UTF-8');

require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/settings/DocRootUrlPathConst.php';
require_once 'com/danawa/settings/prod/ProdPathConst.php';
require_once 'com/danawa/settings/search/adult_chk.class.php';
require_once 'com/danawa/util/template.php';
require_once 'com/danawa/web/util/DNWInput.php';
require_once 'com/danawa/application/pricecompare/category/PriceCompareCategoryManager.php';
require_once 'com/danawa/application/pricecompare/product/PriceCompareProductManager.php';
require_once 'com/danawa/util/ProductImage.php';
require_once 'com/danawa/util/StringUtils.php';
require_once 'com/danawa/util/PDOFactory.php';
require_once 'com/danawa/web/util/CheckAgent.php';
require_once './lib/model/ProductListDefaultData.class.php';
require_once './lib/ProductListDataSetting.class.php';
require_once 'com/danawa/mds/member/service/MemberAuthManagerImpl.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/settings/StandardCategoryConst.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/settings/DpgZoneCategoryConst.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/service/PhysicsMappingUICategory.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/service/UICategoryInfo.php';
include_once GLOBAL_COMMONFILE_PATH . '/include/guest_cookie.inc';

//SSL 접근시 페이지 강제 이동
if($_SERVER['SERVER_PORT'] == 443){
	header('Location: '.PROD_BLOG_URL.'/list/?'.$_SERVER['QUERY_STRING']);
	exit();
}

$oDNWInput = new DNWInput();

//구글 광고로 유입시 PC,모바일 관계없이 PC로 접근 후 리다이렉트 되므로 파라미터 유지
//유지되는 파라미터 src, kw, gclid
$bGoogleSearch = CheckAgent::checkGoogleAdParameter();

$sParamToMobileResult = '';
$sParamToAdResult = '?' . $_SERVER['QUERY_STRING'];
if($bGoogleSearch == true) {
	$sParamToMobileResult = '&' . $_SERVER['QUERY_STRING'];
}

//동적 검색옵션 분기처리 파라미터
$dynamicOption = $oDNWInput->getString('dynamicOption');

//url추출을 통한 접근
$searchOption = $oDNWInput->getString('searchOption');
$aSearchOption = array();

$aSearchOption = explode('/', $searchOption);

$searchUrl = array();

foreach($aSearchOption as $optionValue) {
	$aOptionValue = explode('=', $optionValue);

		if(strstr($aOptionValue[0], 'searchMaker')) {						//제조사 배열처리
			$searchUrl['searchMaker'] = explode(',', $aOptionValue[1]);
		} else if(strstr($aOptionValue[0], 'searchBrand')) {				//브랜드 배열처리
			$searchUrl['searchBrand'] = explode(',', $aOptionValue[1]);
		} else if(strstr($aOptionValue[0], 'searchAttributeValue')) {		//속성 배열처리및 3번째값 추출
			$exOptionValue = explode('|', $aOptionValue[1]);
			$searchUrl['searchAttributeValue'] = explode(',', $aOptionValue[1]);
		} else {															//그외
			$searchUrl[$aOptionValue[0]] = $aOptionValue[1];
		}
}

$composedUrl = '';

if(count($searchUrl) > 0) {
	foreach($searchUrl as $searchKey => $searchItem) {
		if(count($searchItem) == 1) {
			if(is_array($searchItem)) {
				$composedUrl .= '&'. $searchKey . '=' . $searchItem[0];
			} else {
				$composedUrl .= '&'. $searchKey . '=' . $searchItem;
			}
		} else if(count($searchItem) > 1) {
			$composedUrl .= '&'. $searchKey . '=' .implode(',', $searchItem);
		}
	}
}

//통합검색 바로가기 설정시 넘어오는 키워드 파라미터
$sSearchKeyword = $oDNWInput->getString('barosearch');
$sReturnSearchPageUrl = 'http://search.danawa.com/dsearch.php?query=' . urlencode($oDNWInput->getString('barosearch')) . '&baro=N';

$sAffiliateId = $oDNWInput->getString('affiliate');
$sAffiliateIframeResizeUrl = '';
$sLoggerChannel = '';
if($sAffiliateId != ''){
	require_once './affiliate_setting.inc.php';
	if(in_array($sAffiliateId, $aAffiliateList) === FALSE){
		echo '접근 권한이 없습니다.'; exit;
	}
	$sAffiliateIframeResizeUrl = $aAllowIframeResize[$sAffiliateId];
	$sLoggerChannel = $aAllowChannelVal[$sAffiliateId];
	if($sAffiliateId == 'slrclub'){
		$sLoggerChannel .= '&source=http://partner.slrclub.com';
	}
}

$oPhysicsMappingUICategory = new PhysicsMappingUICategory();

//물리 카테고리 코드
$nCateC1    = ($oDNWInput->get('cate1') > 0) ? $oDNWInput->get('cate1') : $oDNWInput->get('cate_c1');
$nCateC2    = ($oDNWInput->get('cate2') > 0) ? $oDNWInput->get('cate2') : $oDNWInput->get('cate_c2');

//UI 카테고리 코드
$nUICategoryCode = $oDNWInput->getFloat('cate');

//물리카테고리코드가 있고, UI카테고리코드가 없으면 물리카테고리로 UI 카테고리 조회
if(
	$nCateC2 > 0
	&& $nUICategoryCode == 0
){
	$oPhysicsMappingUICategory->setUICategoryMappingPhysicsCategoryList();
	$nUICategoryCode = $oPhysicsMappingUICategory->getUICategoryCode($nCateC2);
}

//UI 카테고리가 없으면 다나와 메인 페이지로 이동
if($nUICategoryCode == 0){
    header('Location:http://www.danawa.com');
	exit;
}

//마이크로소프트 기획전 강제이동
$nMicrosoftPlanCategoryCode = array(
		'11220997' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085",		//게이밍노트북/PC (2뎁스)
		'11320998' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=10958#a_2",	//하이엔드 게이밍
		'11320999' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=10958#a_3",	//가성비 게이밍
		'11324502' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button1",	//LG
		'11321720' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button2",	//한성컴퓨터
		'11324503' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button3",	//삼성
		'11321724' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button4",	//HP
		'11323192' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button5",	//LENOVO
		'11321722' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button6",	//ASUS
		'11322598' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button7",	//ACER
		'11321725' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button8",	//MSI
		'11321723' => "http://plan.danawa.com/user/user_plan_view.php?nPlanSeq=11085#ex_button9"	//GIGABYTE
);

$sUICategoryCode = $oDNWInput->getString('cate');
if(array_key_exists($sUICategoryCode, $nMicrosoftPlanCategoryCode)){
    header('Location:' . $nMicrosoftPlanCategoryCode[$sUICategoryCode]);
	exit;
}

//스탬프 이벤트 기간체크
$stampDisplay = false;
$displayCateCode = array(112758, 132412);	//노출될 카테고리: 노트북, 자전거

if(in_array($nUICategoryCode, $displayCateCode)) {
	$nowTime = mktime();
	if($nowTime >= mktime(00,00,0,05,24,2016) && $nowTime <= mktime(23,59,59,06,10,2016)) {  // 스탬프 이벤트 기간체크
		$stampDisplay = true;
	}
}

$sProdListUrl = PROD_BLOG_URL . '/list/?cate=' . $nUICategoryCode;

//AGENT 체크 모바일 브라우저 접근시 모바일 페이지로 강제 이동

$sMobileUrl = '';
if(getenv('DEV_ADOPTED_ENV') == 'DEVELOPER_PC') {
	$sMobileUrl = 'http://m-local.danawa.com';
} else if(getenv('DEV_ADOPTED_ENV') == 'DEVELOPING_SERVER') {
	$sMobileUrl = 'http://m-t.danawa.com';
} else {
	$sMobileUrl = 'http://m.danawa.com';
}

CheckAgent::checkMobileAgentByCookie('agent', $sMobileUrl . '/product/productList.html?cateCode=' . $nUICategoryCode . $composedUrl . $sParamToMobileResult);

$oMemberAuthManager = MemberAuthManagerImpl::getInstance();

$aAuthInfo = $oMemberAuthManager->simpleCheckAuth();

$nGroup = substr($nUICategoryCode, 0, 2); //그룹 코드 (10, 11, 12 .... 19)
$nDepth = substr($nUICategoryCode, 2, 1); //카테고리 Depth
//UI 카테고리 코드 [get parameter로 전달 받은 카테고리]
//네비게이션 및 기타 정보를 보여주기 위해 사용
$nCategoryCode = substr($nUICategoryCode, 3);

//섹션 목록 include
$aSectionList = array();
require_once GLOBAL_CACHE_PATH . '/WEB/PRODUCT/Category/Section_ArrayPHP.inc';

/*
 * 16.06.17 김영춘 과장 요청
 * UI카테고리가 삭제되어 없는데 네이버 광고에 노출되는 경우가 있어 메인으로 리다이렉트 될때 파라미터 유지
 */
if(array_key_exists($nGroup, $aSectionList) === FALSE) {
	header('Location: http://www.danawa.com/' . $sParamToAdResult);
	exit;
}

//UI 카테고리 Cache File 호출
$oPhysicsMappingUICategory->setUICategoryList($nGroup);

//UI 카테고리가 실제 존재하는지 체크
$bCheckedUICategory = $oPhysicsMappingUICategory->checkUICategory($nDepth, $nCategoryCode);
if($bCheckedUICategory === FALSE){
	header('Location: http://www.danawa.com/' . $sParamToAdResult);
	exit;
}

/*
 * 외부 검색(다음,네이버,구글)으로 진입시 레퍼러 체크
 * */
require GLOBAL_COMMONFILE_PATH.'/include/checkExternalReferer.inc.php';
$hasExternalSearch = checkExternalReferer("UTF-8",$_SERVER['HTTP_REFERER'],$_SERVER['QUERY_STRING']);

$a3DepthPlan = array();
if(file_exists(GLOBAL_CACHE_PATH . '/WEB/PRODUCT/Category/plan/Plan_ArrayPHP_'.$nGroup.'_3Depth.inc')){
	require_once GLOBAL_CACHE_PATH . '/WEB/PRODUCT/Category/plan/Plan_ArrayPHP_'.$nGroup.'_3Depth.inc';
}

$sSectionName = $aSectionList[$nGroup]['name'];

/** UI 카테고리 정보 조회 **/
$oUICategoryInfo = new UICategoryInfo($nCategoryCode);
$oUICategoryInfo->setUICategoryInfo();
$aPhysicsCategoryList = $oUICategoryInfo->getPhysicsCategory(); //물리 카테고리
$aPhysicsCategoryNameList = $oUICategoryInfo->getPhysicsCategoryName(); //물리 카테고리 명칭
$aRepresentPhysicsCategoryList = $oUICategoryInfo->getRepresentPhysicsCategory(); //대표 물리 카테고리
$aRepresentPhysicsCategoryNameList = $oUICategoryInfo->getRepresentPhysicsCategoryName(); //대표 물리 카테고리 명칭
$sLastPhysicsCategory = $oUICategoryInfo->getLastPhysicsCategory(); //마지막 물리 카테고리
$aCategoryMappingCode = $oUICategoryInfo->getCategoryMappingCode(); //카테고리 매핑 코드
$bMultiCategory = $oUICategoryInfo->getMultiCategory(); //멀티 카테고리 여부 (TRUE : 멀티 카테고리, FALSE : 단일 카테고리)
$aExpansionContent = $oUICategoryInfo->getExpansionContent(); //UI 카테고리 template 설정 정보

if(count($aRepresentPhysicsCategoryList) > 0){
	$aPhysicsCategoryList = $aRepresentPhysicsCategoryList;
	$aPhysicsCategoryNameList = $aRepresentPhysicsCategoryNameList;
}

$isDpgZoneCategory = 'N';
foreach($aPhysicsCategoryList as $nPhysicsCategory) {
	if($nPhysicsCategory > 0) {
		if(in_array($nPhysicsCategory, $aDpgZonePhysicsCategory)) {
			$isDpgZoneCategory = 'Y';
			break;
		}
	}
}

$isDpgZoneUICategory = 'N';

if(in_array($nCategoryCode, $aDpgZoneUICategory)) {
	$isDpgZoneUICategory = 'Y';
}


$aAddonContent = $oUICategoryInfo->getUiCategoryAddon();
$sSortContents = $aAddonContent->getSortContetns();

//네이버 파워링크 키워드
//관리자에서 설정시 해당 키워드, 미설정시 UI카테고리명 호출 (powerLink.php?keyword=)
$sPowerLinkKeyword = $aAddonContent->getPowerLinkKeyword();

//제조사 브랜드 기본 사용안함
$aMakerBrandContentsDetail = array();
$aMakerBrandContentsDetail['makerYn'] = 'N';
$aMakerBrandContentsDetail['brandYn'] = 'N';
$nMakerBrandAutoWired = 0;

if(!is_null($sSortContents)) {
	$aMakerBrandContents = explode('|', $sSortContents);

	if(count($aMakerBrandContents) > 0) {
		$aMakerBrandContentsDetail['makerYn'] = $aMakerBrandContents[0];
		$aMakerBrandContentsDetail['brandYn'] = $aMakerBrandContents[2];
	}

	if($aMakerBrandContentsDetail['makerYn'] == 'Y' && $aMakerBrandContentsDetail['brandYn'] == 'Y') {
		$nMakerBrandAutoWired = 1;
	}else if($aMakerBrandContentsDetail['makerYn'] == 'Y' && $aMakerBrandContentsDetail['brandYn'] != 'Y') {
		$nMakerBrandAutoWired = 2;
	}else if($aMakerBrandContentsDetail['makerYn'] != 'Y' && $aMakerBrandContentsDetail['brandYn'] == 'Y') {
		$nMakerBrandAutoWired = 3;
	}
}

/*$aCategoryGuideInfo = $oUICategoryInfo->getCategoryGuideInfo(); //카테고리 가이드 정보
 $sCategoryGuideTitle = $aCategoryGuideInfo['sCategoryGuideTitle']; //카테고리 가이드 제목
 $sCategoryGuideImage = $aCategoryGuideInfo['sCategoryGuideImage']; //카테고리 가이드 이미지
 $sCategoryGuideContents = $aCategoryGuideInfo['sCategoryGuideContents']; //카테고리 가이드 설명*/

$aPriceRange = $oUICategoryInfo->getPriceRange(); //가격 범위
$nPriceRangeMinPrice = (integer)$aPriceRange[0]; //가격 범위 최소값
$nPriceRangeMaxPrice = (integer)$aPriceRange[1]; //가격 범위 최대값

//링크 카테고리의 경우 UI카테고리 코드가 변경이 되기 때문에 재셋팅하여 준다.
//실제 상품 목록 및 검색 옵션을 불러오기 위해 사용
$nOriginGroup = $oUICategoryInfo->getGroup(); //UI 카테고리 그룹 코드 [실제 UI 카테고리 그룹 코드]
$nOriginDepth = $oUICategoryInfo->getDepth(); //UI 카테고리 그룹 depth [실제 UI 카테고리 그룹 depth]
$nUICategoryCode = $oUICategoryInfo->getUICategoryCode(); //UI 카테고리 코드 [실제 UI 카테고리 코드]
/** UI 카테고리 정보 조회 끝 **/

//성인 인증으로 설정되었을 경우 성인 인증 페이지로 이동
if($aExpansionContent['sAdultCheck'] == 'Y'){
	$sReturnUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	$oAdultChk = new AdultChk('');

	$oAdultChk->chkSocialAdultForProductList($sReturnUrl);
}

$sPriceCompareTabSelected = '';
$sSearchProductTabSelected = '';
$sTopFiveAreaExplosure = ' style="display: none;"';
if($aExpansionContent['sDefaultListType'] == DEFAULT_LIST_TYPE_PRICE_COMPARE){
	$sPriceCompareTabSelected = 'selected';
}else if($aExpansionContent['sDefaultListType'] == DEFAULT_LIST_TYPE_SEARCH_PRODUCT){
	$sTopFiveAreaExplosure = '';
	$sSearchProductTabSelected = 'selected';
}

//Top5영역 데이터 셋팅
$aTopFiveProductList = array();
$oPriceCompareProductManager = PriceCompareProductManager::getInstance();
$oPriceCompareProductListSearchCommand = new PriceCompareProductListSearchCommand();
$oPriceCompareProductListSearchCommand->UICategoryCode = $nUICategoryCode;
$oPriceCompareProductListSearchCommand->page = 1;
$oPriceCompareProductListSearchCommand->limit = 5;
$oPriceCompareProductListSearchCommand->sortType = 'BEST';
$oPriceCompareProductListSearchCommand->listType = 2;			//묶음 상품 사용안함
$oPriceCompareProductListSearchCommand->useTermDictionary = 'N';
$oPriceCompareProductListSearchCommand->useTotalCount = 'N';
$oPriceCompareProductListSearchCommand->useUnit = 'N';
$oPriceCompareProductListSearchCommand->useCache = 'true';

if($nGroup == SITE_CODE_PC_NOTEBOOK){
	$oPriceCompareProductListSearchCommand->siteCode = 1;
}else{
	$oPriceCompareProductListSearchCommand->siteCode = 0;
}


$aApiResultList = $oPriceCompareProductManager->getUICategoryProductList($oPriceCompareProductListSearchCommand);

if($aApiResultList['status'] == 200){
	$aResultProductList = $aApiResultList['result']['dataList'];
}

if(count($aResultProductList) > 0){
	$oProductListDefaultData = new ProductListDefaultData();
	$oProductListDefaultData->setDepth($nDepth);
	$oProductListDefaultData->setGroup($nGroup);
	$oProductListDefaultData->setUICategoryCode($nUICategoryCode);
	$oProductListDefaultData->setListCategoryCode($nCategoryCode);
	$oProductListDefaultData->setViewMethod('LIST');
	$oProductListDefaultData->setSortMethod('BEST');
	$oProductListDefaultData->setPhysicsCate1($aPhysicsCategoryList[1]);
	$oProductListDefaultData->setPhysicsCate2($aPhysicsCategoryList[2]);
	$oProductListDefaultData->setPhysicsCate3($aPhysicsCategoryList[3]);
	$oProductListDefaultData->setPhysicsCate4($aPhysicsCategoryList[4]);
	$oProductListDefaultData->setLoggerChannel($sLoggerChannel);

	$oProductListDataSetting = new ProductListDataSetting();

	$nProductSeq = 0;
	foreach($aResultProductList as $aResultProduct){
		$nProductCode = $aResultProduct['productCode'];
		$aProductCodeListForContent[$nProductCode] = $nProductCode;
		//공통 데이터 setting
		$oProductListDataSetting->setProductListData($oProductListDefaultData, $aResultProduct, FALSE);
		$aTopFiveProductList[$nProductSeq] = $oProductListDataSetting->getProductListData();

		$nProductSeq++;
	}
}
//Top5영역 데이터 셋팅 끝

/*
 * 표준시리즈로 설정된 UI카테고리가 있으면 Y, 없으면 N
 * @see common_files/lib/mds/product/settings/StandardCategoryConst.php
 */
$bStandardCategory = 'N';
$bStandardParentCategory = 'N';
if(array_key_exists($nUICategoryCode, $aStandardCatgoryList)) {
	$bStandardCategory = 'Y';
	if($aStandardCatgoryList[$nUICategoryCode]['parentYN'] == 'Y') {
		$bStandardParentCategory = 'Y';
	}
}

// 17.05.24 PAS와 검색API 호출 분리를 위한 파라미터 추가
// 17.08.22 내부아이피에서 PAS<->검색API 전환할수있게 추가
// 17.08.24 tAdmin PAS<->검색API 전환 관리자 추가
$aProductApiSelect = array();
$sProductListApi = '';
$sCurrentApiName = '';

// $sProductListApi = $oDNWInput->getString('productListApi');
if($_SERVER['REMOTE_ADDR'] == '61.37.160.130' || $_SERVER['REMOTE_ADDR'] == '61.37.160.131' || SERVER_ADOPTED_ENV != 'REAL') {
	if(file_exists(GLOBAL_CACHE_PATH.'/WEB/PRODUCT/Category/productApiSelect.json')) {
		$aProductApiSelect = json_decode(file_get_contents(GLOBAL_CACHE_PATH.'/WEB/PRODUCT/Category/productApiSelect.json'), true);
		$sProductListApi = $aProductApiSelect['api'];
	}
	$aSearchApiCategory = array(1822, 1824, 1985, 22596, 57905);
	if(in_array($aPhysicsCategoryList[1], $aSearchApiCategory) || $sProductListApi == 'search') {
		$sCurrentApiName = '검색엔진';
	}
}

$oTpl = new Template('./tpl/');

//브랜드 로그 컨트롤러
require_once './controllers/brandLog.php';
//카테고리 네비게이션 프로세스 컨트롤러
require_once './controllers/categoryNavigation.php';
//프로모션 어워드 컨트롤러
require_once './controllers/promotionAward.php';
//E형 광고 영역 컨트롤러
require_once './controllers/adForTypeE.php';
//이베이 영역 컨트롤러
require_once './controllers/ebayPowerClick.php';

$threeDepthType = 'N';

if(isset($aCategoryNavigation['aCategoryCurrentNavigationList'][2]['imageCategoryYN'])) {
	$threeDepthType = $aCategoryNavigation['aCategoryCurrentNavigationList'][2]['imageCategoryYN'];
}

if($threeDepthType == 'Y') {
	$sThreeDepthNavigationTpl = './tpl/threeDepthImageNavigation.tpl.php';
} else if($threeDepthType == 'N') {
	$sThreeDepthNavigationTpl = './tpl/threeDepthTextNavigation.tpl.php';
}

//헤더 영역
if($sAffiliateId == ''){
	require_once './controllers/common/header.php';
}else{
	require_once './tpl/common/header.php';
}

$data['categoryExplodeLayer'] = './tpl/categoryExplodeLayer.tpl.php';
$data['categoryLNB'] = './tpl/categoryLNB.tpl.php';

$data['categoryNavigation'] = './tpl/categoryNavigation.tpl.php';
$data['brandLog'] = './tpl/brandLog.tpl.php';
$data['categoryCuration'] = './tpl/categoryCuration.tpl.php';
$data['promotionAward'] = './tpl/promotionAward.tpl.php';
$data['adForTypeE'] = './tpl/adForTypeE.tpl.php';
$data['sThreeDepthNavigation'] = $sThreeDepthNavigationTpl;
$data['jsList'] = './tpl/common/jsList.tpl.php';

$oTpl->set('nGroup',$nGroup);
$oTpl->set('nOriginGroup',$nOriginGroup);
$oTpl->set('nDepth',$nDepth);
$oTpl->set('nOriginDepth',$nOriginDepth);
$oTpl->set('nCategoryCode',$nCategoryCode);
$oTpl->set('nUICategoryCode',$nUICategoryCode);
$oTpl->set('aSectionList',$aSectionList);
$oTpl->set('sSectionName',$sSectionName);
$oTpl->set('aExpansionContent',$aExpansionContent);
$oTpl->set('isDpgZonePhysicsCategory',$isDpgZoneCategory);
$oTpl->set('isDpgZoneUICategory',$isDpgZoneUICategory);
$oTpl->set('nPriceRangeMinPrice',$nPriceRangeMinPrice);
$oTpl->set('nPriceRangeMaxPrice',$nPriceRangeMaxPrice);
$oTpl->set('sPriceRangeMinPrice',number_format($nPriceRangeMinPrice));
$oTpl->set('sPriceRangeMaxPrice',number_format($nPriceRangeMaxPrice));
$oTpl->set('bMultiCategory',$bMultiCategory);
$oTpl->set('sMultiCategory',($bMultiCategory === TRUE) ? 'true' : 'false');
$oTpl->set('sPhysicsCate1',$aPhysicsCategoryList[1]);
$oTpl->set('sPhysicsCate2',$aPhysicsCategoryList[2]);
$oTpl->set('sPhysicsCate3',$aPhysicsCategoryList[3]);
$oTpl->set('sPhysicsCate4',$aPhysicsCategoryList[4]);
$oTpl->set('aPhysicsCategoryNameList',$aPhysicsCategoryNameList);
$oTpl->set('sLastPhysicsCategory',$sLastPhysicsCategory);
$oTpl->set('sCategoryMappingCode',implode(',',$aCategoryMappingCode));
$oTpl->set('a3DepthPlan',$a3DepthPlan);
$oTpl->set('aAdForTypeE',$aAdForTypeE);
$oTpl->set('aADProductListForTypeE',$aADProductListForTypeE);
$oTpl->set('sPriceCompareTabSelected',$sPriceCompareTabSelected);
$oTpl->set('sSearchProductTabSelected',$sSearchProductTabSelected);
$oTpl->set('sTopFiveAreaExplosure',$sTopFiveAreaExplosure);
$oTpl->set('aTopFiveProductList',$aTopFiveProductList);

$oTpl->set('sProductListApi',$sProductListApi);
$oTpl->set('dynamicOption',$dynamicOption);

$oTpl->set('sAffiliateId',$sAffiliateId);
$oTpl->set('sLoggerChannel',$sLoggerChannel);
$oTpl->set('sAffiliateIframeResizeUrl',$sAffiliateIframeResizeUrl);
$oTpl->set('sSearchKeyword',$sSearchKeyword);
$oTpl->set('sReturnSearchPageUrl',$sReturnSearchPageUrl);
$oTpl->set('sComposedUrl',$composedUrl);
$oTpl->set('sProdListUrl',$sProdListUrl);
$oTpl->set('bStandardCategory', $bStandardCategory);
$oTpl->set('bStandardParentCategory', $bStandardParentCategory);
$oTpl->set('aAuthInfo', $aAuthInfo);
$oTpl->set('stampDisplay', $stampDisplay);
$oTpl->set('nMakerBrandAutoWired', $nMakerBrandAutoWired);		//제조사 브랜드 자동정렬 유무
$oTpl->set('sPowerClickParams', $sPowerClickParams);				//이베이 파워클릭 호출 파라미터
$oTpl->set('isPowerClickTopPosition', $isPowerClickTopPosition);	//이베이 파워클릭 상단노출 여부
$oTpl->set('sPowerLinkKeyword', $sPowerLinkKeyword);	//이베이 파워링크 키워드
$oTpl->set('sCurrentApiName', $sCurrentApiName);

$oTpl->set($data);

echo $oTpl->fetch('index.tpl.php');

//푸터 영역
if($sAffiliateId == ''){
	require_once GNB_GLOBAL_PATH.'/footer_utf8.php';
}else{
	require_once './tpl/common/footer.php';
}

//외부 광고검색으로 진입시 안내레이어
if($hasExternalSearch === TRUE){
	require_once './tpl/common/externalSearchLayer.tpl.php';
}
?>