<?php
/**
 * 상품리스트 미리보기
 * @author eprkswu@danawa.com
 * @version 1.0.0
 */
header('Content-Type:text/html; charset=UTF-8');
require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/settings/DocRootUrlPathConst.php';
require_once 'com/danawa/settings/prod/ProdPathConst.php';
require_once 'com/danawa/util/template.php';
require_once 'com/danawa/web/util/DNWInput.php';
require_once 'com/danawa/application/pricecompare/product/PriceCompareProductManager.php';
require_once 'com/danawa/application/pricecompare/searchOption/PriceCompareSearchOptionManager.php';
require_once 'com/danawa/util/ProductImage.php';
require_once 'com/danawa/util/StringUtils.php';
require_once 'com/danawa/mds/member/service/MemberAuthManagerImpl.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/service/PhysicsMappingUICategory.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/service/UICategoryInfo.php';
include_once GLOBAL_COMMONFILE_PATH . '/include/guest_cookie.inc';

$oMemberAuthManager = MemberAuthManagerImpl::getInstance();
$aAuthInfo = $oMemberAuthManager->simpleCheckAuth();

if(!$aAuthInfo || stripos($aAuthInfo['id'], 'admin-') === FALSE){
	echo '잘못된 접근 입니다.';
	exit;
}

//섹션 목록 include
$aSectionList = array();
require_once GLOBAL_CACHE_PATH . '/WEB/PRODUCT/Category/Section_ArrayPHP.inc';

$oDNWInput = new DNWInput();

$oPhysicsMappingUICategory = new PhysicsMappingUICategory();

$nDepth = $oDNWInput->post('categoryDepth');
$nOriginDepth = $nDepth;
$nGroup = $oDNWInput->post('categoryGroup');
$nOriginGroup = $nGroup;
$nCategoryCode = $oDNWInput->post('categoryCode');
$nUICategoryCode = $nCategoryCode;

$sSectionName = $aSectionList[$nGroup]['name'];

//UI 카테고리 Cache File 호출
$oPhysicsMappingUICategory->setUICategoryList($nGroup);

$oUICategoryInfo = new UICategoryInfo(0);

$nRepresentCategoryCode = $oDNWInput->post('representCategoryCode');
$sSearchOptionCategoryInfo = $oDNWInput->postString('searchOptionCategoryInfo');
$aSearchOptionCategoryInfo = explode('|',$sSearchOptionCategoryInfo);

$sSearchOptionCategoryDepthInfo = $oDNWInput->postString('searchOptionCategoryDepthInfo');
$aSearchOptionCategoryDepthInfo = explode('|',$sSearchOptionCategoryDepthInfo);

$sSearchOptionMakerInfo = $oDNWInput->postString('searchOptionMakerInfo');
$sSearchOptionBrandInfo = $oDNWInput->postString('searchOptionBrandInfo');
$sSearchOptionAttributeInfo = $oDNWInput->postString('searchOptionAttributeInfo');

$sSearchOptionRepresentMakerInfo = $oDNWInput->postString('searchOptionRepresentMakerInfo');
$sSearchOptionRepresentBrandInfo = $oDNWInput->postString('searchOptionRepresentBrandInfo');
$sSearchOptionRepresentAttributeInfo = $oDNWInput->postString('searchOptionRepresentAttributeInfo');
$sSearchOptionSettingInfo = $oDNWInput->postString('searchOptionSettingInfo');

$sDefaultListType = $oDNWInput->postString('defaultListType');
$sPriceCompareListType = $oDNWInput->postString('priceCompareListType');
$nPriceCompareListPackageType = $oDNWInput->post('priceCompareListPackageType');
$nPriceCompareListCount = $oDNWInput->post('priceCompareListCount');
$nPriceCompareListPackageLimit = $oDNWInput->post('priceCompareListPackageLimit');
// $nPriceCompareImagePackageType = $oDNWInput->post('priceCompareImagePackageType');
// $nPriceCompareImageCount = $oDNWInput->post('priceCompareImageCount');
$sSearchProductListType = $oDNWInput->postString('searchProductListType');
$nSearchProductListCount = $oDNWInput->post('searchProductListCount');
$nSearchProductImageCount = $oDNWInput->post('searchProductImageCount');
$sDiscontinueIndicate = $oDNWInput->postString('discontinueIndicate');
$sMakerIndicate = $oDNWInput->postString('makerIndicate');
if($sMakerIndicate == ''){
	$sMakerIndicate = 'Y';
}
$sBrandIndicate = $oDNWInput->postString('brandIndicate');
if($sBrandIndicate == ''){
	$sBrandIndicate = 'Y';
}
$sMakerStandardDisplayStatus = $oDNWInput->postString('makerStandardDisplayStatus');
if($sMakerStandardDisplayStatus == ''){
	$sMakerStandardDisplayStatus = 'Y';
}
$sBrandStandardDisplayStatus = $oDNWInput->postString('brandStandardDisplayStatus');
if($sBrandStandardDisplayStatus == ''){
	$sBrandStandardDisplayStatus = 'Y';
}
$nPriceUnit = $oDNWInput->post('priceUnit');
$nPriceUnitValue = $oDNWInput->post('priceUnitValue');
$sPriceUnitClass = $oDNWInput->postString('priceUnitClass');
$sCmRecommendSort = $oDNWInput->postString('cmRecommendSort');
$sCmRecommendSortDefault = $oDNWInput->postString('cmRecommendSortDefault');
if($sCmRecommendSortDefault == ''){
	$sCmRecommendSortDefault = 'N';
}
$sBundleImagePreview = $oDNWInput->postString('bundleImagePreview');
if($sBundleImagePreview == ''){
	$sBundleImagePreview = 'N';
}

$nPriceRangeMinPrice = $oDNWInput->post('priceRangeMinPrice');
$nPriceRangeMaxPrice = $oDNWInput->post('priceRangeMaxPrice');
$makerBrandSortContents = $oDNWInput->post('makerBrandSortContents');

$oPriceCompareProductManager = PriceCompareProductManager::getInstance();

$sCategoryUserOptions = '';
$aCategoryAttributeCodeList = array();

$aPhysicsCategoryList = array();
$aPhysicsCategoryNameList = array();
$aRepresentPhysicsCategoryList = array();
$sRepresentPhysicsCategoryList = '';

$aMakerList = array();
$aBrandList = array();
$aAttributeList = array();
if(count($aSearchOptionCategoryInfo) > 0){
	foreach($aSearchOptionCategoryInfo as $nSeq => $nPhysicsCategory){
		$aPhysicsCategory = $oUICategoryInfo->setPhysicsCategory($nPhysicsCategory, $aSearchOptionCategoryDepthInfo[$nSeq]);

		if($nRepresentCategoryCode == $nPhysicsCategory){
			$nRepresentCategoryDepth = $aSearchOptionCategoryDepthInfo[$nSeq];
			$aRepresentPhysicsCategoryList = $oUICategoryInfo->setPhysicsCategory($nRepresentCategoryCode, $nRepresentCategoryDepth);
		}

		$aPhysicsCategoryList[1][$aPhysicsCategory[1]] = $aPhysicsCategory[1];
		$aPhysicsCategoryList[2][$aPhysicsCategory[2]] = $aPhysicsCategory[2];
		$aPhysicsCategoryList[3][$aPhysicsCategory[3]] = $aPhysicsCategory[3];
		$aPhysicsCategoryList[4][$aPhysicsCategory[4]] = $aPhysicsCategory[4];

		//PAS API 전송에 사용될 카테고리 목록
		$aPhysicsCategoryForPasList[1][$nSeq] = $aPhysicsCategory[1];
		$aPhysicsCategoryForPasList[2][$nSeq] = $aPhysicsCategory[2];
		$aPhysicsCategoryForPasList[3][$nSeq] = $aPhysicsCategory[3];
		$aPhysicsCategoryForPasList[4][$nSeq] = $aPhysicsCategory[4];

		$aLastPhysicsCategoryList[] = $nPhysicsCategory.'|'.$aSearchOptionCategoryDepthInfo[$nSeq];

		$aCategoryMappingCode[] = $nSeq;
	}

	$sLastPhysicsCategory = implode(',',$aLastPhysicsCategoryList); //마지막 물리 카테고리

	if(count($aRepresentPhysicsCategoryList) > 0){
		$aPhysicsCategoryList = $aRepresentPhysicsCategoryList;
		$sRepresentPhysicsCategoryList = implode(',', $aRepresentPhysicsCategoryList);
	}else{
		$aPhysicsCategoryList[1] = implode(',',$aPhysicsCategoryList[1]);
		$aPhysicsCategoryList[2] = implode(',',$aPhysicsCategoryList[2]);
		$aPhysicsCategoryList[3] = implode(',',$aPhysicsCategoryList[3]);
		$aPhysicsCategoryList[4] = implode(',',$aPhysicsCategoryList[4]);
	}

	//단일 카테고리의 경우 속성, 제조사, 브랜드 검색 옵션 전체 조회
	//멀티 카테고리의 경우 제조사, 브랜드 검색 옵션 조회
	if(count($aSearchOptionCategoryInfo) == 1){
		$bMultiCategory = false;

		$nPhysicsCate1 = (integer)$aPhysicsCategoryList[1];
		$nPhysicsCate2 = (integer)$aPhysicsCategoryList[2];
		$nPhysicsCate3 = (integer)$aPhysicsCategoryList[3];
		$nPhysicsCate4 = (integer)$aPhysicsCategoryList[4];

		$sCategoryUserOptions .= $nPhysicsCate1.':'.$nPhysicsCate2.':'.$nPhysicsCate3.':'.$nPhysicsCate4;

		/** 검색 옵션 정보 PAS 호출 시작 **/
		$oPriceCompareSearchOptionManager = PriceCompareSearchOptionManager::getInstance();

		$oPriceCompareSearchOptionSearchCommand = new PriceCompareSearchOptionSearchCommand();
		$oPriceCompareSearchOptionSearchCommand->cate1 = $nPhysicsCate1;
		$oPriceCompareSearchOptionSearchCommand->cate2 = $nPhysicsCate2;
		$oPriceCompareSearchOptionSearchCommand->cate3 = $nPhysicsCate3;
		$oPriceCompareSearchOptionSearchCommand->cate4 = $nPhysicsCate4;
		$oPriceCompareSearchOptionSearchCommand->mode = 2;
		$oPriceCompareSearchOptionSearchCommand->displayAll = 'N';
		$oPriceCompareSearchOptionSearchCommand->useCache = 'true';

		$aSearchOptionResultList = $oPriceCompareSearchOptionManager->getSearchOptionCategorySearchOption($oPriceCompareSearchOptionSearchCommand);
		/** 검색 옵션 정보 PAS 호출 끝 **/

		if($aSearchOptionResultList['status'] == 200){
			$aSearchOptionResultList = $aSearchOptionResultList['result']['data'];

			$aSearchOptionMakerList = $aSearchOptionResultList['makerList']['infoList'];
			$nSearchOptionMakerCount = count($aSearchOptionMakerList);
			if($nSearchOptionMakerCount > 0){
				foreach($aSearchOptionMakerList as $aSearchOptionMaker){
					$aMakerList[$aSearchOptionMaker['code']] = $aSearchOptionMaker['name'];
				}
			}

			$aSearchOptionBrandList = $aSearchOptionResultList['brandList']['infoList'];
			$nSearchOptionBrandCount = count($aSearchOptionBrandList);
			if($nSearchOptionBrandCount > 0){
				foreach($aSearchOptionBrandList as $aSearchOptionBrand){
					$aBrandList[$aSearchOptionBrand['code']] = $aSearchOptionBrand['name'];
				}
			}

			$aSearchOptionAttributeSubList = $aSearchOptionResultList['attributeList']['searchOptionSubList'];
			$aSearchOptionAttributeTitleList = $aSearchOptionResultList['attributeList']['searchOptionTitleList'];

			/** 검색 옵션을 속성을 기준으로 데이터 처리 시작 **/
			$aAttributeTitleList = array();
			if(count($aSearchOptionAttributeTitleList) > 0){
				foreach($aSearchOptionAttributeTitleList as $aSearchOptionAttributeTitle){
					$nAttributeCode = $aSearchOptionAttributeTitle['attributeSeq'];
					$aAttributeTitleList[$nAttributeCode]['attributeName'] = $aSearchOptionAttributeTitle['attributeName'];
				}
			}
			if(count($aSearchOptionAttributeSubList) > 0){
				foreach($aSearchOptionAttributeSubList as $nSeq => $aSearchOptionAttribute){
					$nAttributeCode = $aSearchOptionAttribute['attributeSeq'];
					$nAttributeValueCode = $aSearchOptionAttribute['attributeValueSeq'];

					$aAttributeList[$nAttributeValueCode]['attributeCode'] = $nAttributeCode;
					$aAttributeList[$nAttributeValueCode]['attributeName'] = $aAttributeTitleList[$nAttributeCode]['attributeName'];

					$sAttributeValueName = '';
					if($aSearchOptionAttribute['attributeUnitPosition'] == 'F'){
						$sAttributeValueName = $aSearchOptionAttribute['attributeUnit'] . $aSearchOptionAttribute['attributeValueName'];
					}else{
						$sAttributeValueName = $aSearchOptionAttribute['attributeValueName'] . $aSearchOptionAttribute['attributeUnit'];
					}

					$aAttributeList[$nAttributeValueCode]['attributeValueName'] = $sAttributeValueName;

					$sMultiSelectType = '';
					if($aSearchOptionAttribute['multiSelectType'] == 'S'){
						$sMultiSelectType = 'OR';
					}else{
						$sMultiSelectType = 'AND';
					}

					$aAttributeList[$nAttributeValueCode]['multiSelectType'] = $sMultiSelectType;
				}
			}
			/** 검색 옵션을 속성을 기준으로 데이터 처리 끝 **/
		}

		//CategoryOptions에 검색 옵션 셋팅
		$sCategoryUserOptions .= '^';
		if($sSearchOptionAttributeInfo != ''){
			$aSearchOptionAttributeInfoList = explode(',',$sSearchOptionAttributeInfo);
			if(count($aSearchOptionAttributeInfoList) > 0){
				foreach($aSearchOptionAttributeInfoList as $nSearchOptionAttributeValueCode){
					$aOptionData[] = '0|'.$aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode'].'|'.$nSearchOptionAttributeValueCode.'|'.$aAttributeList[$nSearchOptionAttributeValueCode]['multiSelectType'];
					$aCategoryAttributeCodeList[$aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode']] = $aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode'];
				}
			}
		}

		$sSearchOptionValue = $oPriceCompareProductManager->getSearchOptionValue($aOptionData);
		$sSearchOptionValue = str_replace(',',':',$sSearchOptionValue);
		$sCategoryUserOptions .= $sSearchOptionValue;

		//CategoryOptions에 제조사 셋팅
		$sCategoryUserOptions .= '^';
		if($sSearchOptionMakerInfo != ''){
			$sCategoryUserOptions .= str_replace(',',':',$sSearchOptionMakerInfo);
		}

		//CategoryOptions에 브랜드 셋팅
		$sCategoryUserOptions .= '^';
		if($sSearchOptionBrandInfo != ''){
			$sCategoryUserOptions .= str_replace(',',':',$sSearchOptionBrandInfo);
		}
	}else{
		$bMultiCategory = true;

		$oPriceCompareSearchOptionManager = PriceCompareSearchOptionManager::getInstance();

		$aSearchOptionMakerInfo = explode('|',$sSearchOptionMakerInfo);
		$aSearchOptionBrandInfo = explode('|',$sSearchOptionBrandInfo);
		$aSearchOptionAttributeInfo = explode('|',$sSearchOptionAttributeInfo);

		foreach($aSearchOptionCategoryInfo as $nSeq => $nPhysicsCategory){

			$sCategoryUserOptions .= $aPhysicsCategoryForPasList[1][$nSeq] . ':';
			$sCategoryUserOptions .= $aPhysicsCategoryForPasList[2][$nSeq] . ':';
			$sCategoryUserOptions .= $aPhysicsCategoryForPasList[3][$nSeq] . ':';
			$sCategoryUserOptions .= $aPhysicsCategoryForPasList[4][$nSeq] . ':';

			/** 검색 옵션 정보 PAS 호출 시작 **/
			$oPriceCompareSearchOptionManager = PriceCompareSearchOptionManager::getInstance();

			$oPriceCompareSearchOptionSearchCommand = new PriceCompareSearchOptionSearchCommand();
			$oPriceCompareSearchOptionSearchCommand->cate1 = $aPhysicsCategoryForPasList[1][$nSeq];
			$oPriceCompareSearchOptionSearchCommand->cate2 = $aPhysicsCategoryForPasList[2][$nSeq];
			$oPriceCompareSearchOptionSearchCommand->cate3 = $aPhysicsCategoryForPasList[3][$nSeq];
			$oPriceCompareSearchOptionSearchCommand->cate4 = $aPhysicsCategoryForPasList[4][$nSeq];
			$oPriceCompareSearchOptionSearchCommand->mode = 2;
			$oPriceCompareSearchOptionSearchCommand->displayAll = 'N';
			$oPriceCompareSearchOptionSearchCommand->useCache = 'true';

			$aSearchOptionResultList = $oPriceCompareSearchOptionManager->getSearchOptionCategorySearchOption($oPriceCompareSearchOptionSearchCommand);
			/** 검색 옵션 정보 PAS 호출 끝 **/

			if($aSearchOptionResultList['status'] == 200){
				$aSearchOptionResultList = $aSearchOptionResultList['result']['data'];

				$aSearchOptionMakerList = $aSearchOptionResultList['makerList']['infoList'];
				$nSearchOptionMakerCount = count($aSearchOptionMakerList);
				if($nSearchOptionMakerCount > 0){
					foreach($aSearchOptionMakerList as $aSearchOptionMaker){
						$aMakerList[$aSearchOptionMaker['code']] = $aSearchOptionMaker['name'];
					}
				}

				$aSearchOptionBrandList = $aSearchOptionResultList['brandList']['infoList'];
				$nSearchOptionBrandCount = count($aSearchOptionBrandList);
				if($nSearchOptionBrandCount > 0){
					foreach($aSearchOptionBrandList as $aSearchOptionBrand){
						$aBrandList[$aSearchOptionBrand['code']] = $aSearchOptionBrand['name'];
					}
				}

				$aSearchOptionAttributeSubList = $aSearchOptionResultList['attributeList']['searchOptionSubList'];
				$aSearchOptionAttributeTitleList = $aSearchOptionResultList['attributeList']['searchOptionTitleList'];

				/** 검색 옵션을 속성을 기준으로 데이터 처리 시작 **/
				$aAttributeTitleList = array();
				if(count($aSearchOptionAttributeTitleList) > 0){
					foreach($aSearchOptionAttributeTitleList as $aSearchOptionAttributeTitle){
						$nAttributeCode = $aSearchOptionAttributeTitle['attributeSeq'];
						$aAttributeTitleList[$nAttributeCode]['attributeName'] = $aSearchOptionAttributeTitle['attributeName'];
					}
				}
				if(count($aSearchOptionAttributeSubList) > 0){
					foreach($aSearchOptionAttributeSubList as $aSearchOptionAttribute){
						$nAttributeCode = $aSearchOptionAttribute['attributeSeq'];
						$nAttributeValueCode = $aSearchOptionAttribute['attributeValueSeq'];

						$aAttributeList[$nAttributeValueCode]['attributeCode'] = $nAttributeCode;
						$aAttributeList[$nAttributeValueCode]['attributeName'] = $aAttributeTitleList[$nAttributeCode]['attributeName'];

						$sAttributeValueName = '';
						if($aSearchOptionAttribute['attributeUnitPosition'] == 'F'){
							$sAttributeValueName = $aSearchOptionAttribute['attributeUnit'] . $aSearchOptionAttribute['attributeValueName'];
						}else{
							$sAttributeValueName = $aSearchOptionAttribute['attributeValueName'] . $aSearchOptionAttribute['attributeUnit'];
						}

						$aAttributeList[$nAttributeValueCode]['attributeValueName'] = $sAttributeValueName;

						$sMultiSelectType = '';
						if($aSearchOptionAttribute['multiSelectType'] == 'S'){
							$sMultiSelectType = 'OR';
						}else{
							$sMultiSelectType = 'AND';
						}

						$aAttributeList[$nAttributeValueCode]['multiSelectType'] = $sMultiSelectType;
					}
				}
				/** 검색 옵션을 속성을 기준으로 데이터 처리 끝 **/
			}

			//CategoryOptions에 검색 옵션 셋팅
			$sCategoryUserOptions .= '^';
			$aOptionData = array();
			if($aSearchOptionAttributeInfo[$nSeq] != ''){
				$aSearchOptionAttributeInfoList = explode(',',$aSearchOptionAttributeInfo[$nSeq]);
				if(count($aSearchOptionAttributeInfoList) > 0){
					foreach($aSearchOptionAttributeInfoList as $nSearchOptionAttributeValueCode){
						$aOptionData[] = '0|'.$aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode'].'|'.$nSearchOptionAttributeValueCode.'|'.$aAttributeList[$nSearchOptionAttributeValueCode]['multiSelectType'];
						$aCategoryAttributeCodeList[$aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode']] = $aAttributeList[$nSearchOptionAttributeValueCode]['attributeCode'];
					}
				}
			}

			$sSearchOptionValue = $oPriceCompareProductManager->getSearchOptionValue($aOptionData);
			$sSearchOptionValue = str_replace(',',':',$sSearchOptionValue);
			$sCategoryUserOptions .= $sSearchOptionValue;

			//CategoryOptions에 제조사 셋팅
			$sCategoryUserOptions .= '^';
			if($aSearchOptionMakerInfo[$nSeq] != ''){
				$sCategoryUserOptions .= str_replace(',',':',$aSearchOptionMakerInfo[$nSeq]);
			}

			//CategoryOptions에 브랜드 셋팅
			$sCategoryUserOptions .= '^';
			if($aSearchOptionBrandInfo[$nSeq] != ''){
				$sCategoryUserOptions .= str_replace(',',':',$aSearchOptionBrandInfo[$nSeq]);
			}

			if($nSeq < count($aSearchOptionCategoryInfo) - 1){
				$sCategoryUserOptions .= ',';
			}
		}
	}
}

$aExpansionContent = array();
$aExpansionContent['sAttributeIndicate'] = $sAttributeIndicate;
$aExpansionContent['sDefaultListType'] = $sDefaultListType; 				//목록 기본탭
$aExpansionContent['sPriceCompareListType'] = $sPriceCompareListType;  			//가격비교설정 > 리스트형/이미지형		유지
$aExpansionContent['nPriceCompareListPackageType'] = $nPriceCompareListPackageType; 	//가격비교설정 > 리스트형 > 묶음조건	통합
$aExpansionContent['nPriceCompareListCount'] = $nPriceCompareListCount; 			//가격비교설정 > 리스트형 > 노출개수	통합
$aExpansionContent['nPriceCompareListPackageLimit'] = $nPriceCompareListPackageLimit; 	//가격비교설정 > 리스트형 > 묶음기본개수	유지
$aExpansionContent['sSearchProductListType'] = $sSearchProductListType; 			//검색상품설정 > 리스트형/이미지형		유지
$aExpansionContent['nSearchProductListCount'] = $nSearchProductListCount;			//검색상품설정 > 리스트형 > 노출개수	통합
$aExpansionContent['sDiscontinueIndicate'] = $sDiscontinueIndicate;
$aExpansionContent['sMakerIndicate'] = $sMakerIndicate;
$aExpansionContent['sBrandIndicate'] = $sBrandIndicate;
$aExpansionContent['nPriceUnit'] = $nPriceUnit;
$aExpansionContent['nPriceUnitValue'] = $nPriceUnitValue;
$aExpansionContent['sPriceUnitClass'] = $sPriceUnitClass;
$aExpansionContent['sCmRecommendSort'] = $sCmRecommendSort;
$aExpansionContent['sCmRecommendSortDefault'] = $sCmRecommendSortDefault;
$aExpansionContent['sBundleImagePreview'] = $sBundleImagePreview;
// $aExpansionContent['sAdultCheck'] = $sBundleImagePreview;
$aExpansionContent['sMakerStandardDisplayStatus'] = $sMakerStandardDisplayStatus;
$aExpansionContent['sBrandStandardDisplayStatus'] = $sBrandStandardDisplayStatus;


//제조사 브랜드 기본 사용안함
$aMakerBrandContentsDetail = array();
$aMakerBrandContentsDetail['makerYn'] = 'N';
$aMakerBrandContentsDetail['brandYn'] = 'N';
$nMakerBrandAutoWired = 0;

if(!is_null($sSortContents)) {
	$aMakerBrandContents = explode('|', $makerBrandSortContents);

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




$sPriceCompareTabSelected = '';
$sSearchProductTabSelected = '';
if($aExpansionContent['sDefaultListType'] == DEFAULT_LIST_TYPE_PRICE_COMPARE){
	$sPriceCompareTabSelected = 'selected';
}else if($aExpansionContent['sDefaultListType'] == DEFAULT_LIST_TYPE_SEARCH_PRODUCT){
	$sSearchProductTabSelected = 'selected';
}

$sCategoryAttributeCodeList = implode(',',$aCategoryAttributeCodeList);

$a3DepthPlan = array();

$oTpl = new Template('./tpl/');

//브랜드 로그 컨트롤러
require_once './controllers/brandLog.php';

//카테고리 네비게이션 프로세스 컨트롤러
require_once './controllers/categoryNavigation.php';

//프로모션 어워드 컨트롤러
require_once './controllers/promotionAward.php';

//E형 광고 영역 컨트롤러
require_once './controllers/adForTypeE.php';

$data['categoryNavigation'] = './tpl/categoryNavigation.tpl.php';
$data['brandLog'] = './tpl/brandLog.tpl.php';
$data['categoryCuration'] = './tpl/categoryCuration.tpl.php';
$data['promotionAward'] = './tpl/promotionAward.tpl.php';
$data['adForTypeE'] = './tpl/adForTypeE.tpl.php';
$data['jsList'] = './tpl/common/jsList.tpl.php';

//----템플릿 셋팅 값은 index.php와 동일 하다. 동기화가 되어야 한다.-----//
$oTpl->set('nGroup',$nGroup);
$oTpl->set('nOriginGroup',$nOriginGroup);
$oTpl->set('nDepth',$nDepth);
$oTpl->set('nOriginDepth',$nOriginDepth);
$oTpl->set('nCategoryCode',$nCategoryCode);
$oTpl->set('nUICategoryCode',$nUICategoryCode);
$oTpl->set('aSectionList',$aSectionList);
$oTpl->set('sSectionName',$sSectionName);
$oTpl->set('aExpansionContent',$aExpansionContent);
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
$oTpl->set('sPriceCompareTabSelected',$sPriceCompareTabSelected);
$oTpl->set('sSearchProductTabSelected',$sSearchProductTabSelected);
$oTpl->set('sTopFiveAreaExplosure',$sTopFiveAreaExplosure);
$oTpl->set('aTopFiveProductList',$aTopFiveProductList);
$oTpl->set('nMakerBrandAutoWired',$nMakerBrandAutoWired);
//----템플릿 셋팅 값은 index.php와 동일 하다. 동기화가 되어야 한다.-----//

//미리보기 전용 템플릿 셋팅 값
$oTpl->set('sCategoryUserOptions',$sCategoryUserOptions);
$oTpl->set('sRepresentPhysicsCategoryList',$sRepresentPhysicsCategoryList);
$oTpl->set('sCategoryAttributeCodeList',$sCategoryAttributeCodeList);
$oTpl->set('sSearchOptionRepresentMakerInfo',$sSearchOptionRepresentMakerInfo);
$oTpl->set('sSearchOptionRepresentBrandInfo',$sSearchOptionRepresentBrandInfo);
$oTpl->set('sSearchOptionRepresentAttributeInfo',$sSearchOptionRepresentAttributeInfo);
$oTpl->set('sSearchOptionSettingInfo',$sSearchOptionSettingInfo);

$oTpl->set($data);

echo $oTpl->fetch('preview.tpl.php');