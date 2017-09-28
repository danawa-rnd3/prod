<?php

header('Content-Type:text/html; charset=UTF-8');
require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/settings/prod/ProdPathConst.php';
require_once 'com/danawa/web/util/DNWInput.php';
require_once 'com/danawa/util/StringUtils.php';
require_once 'com/danawa/util/template.php';
require_once 'com/danawa/application/pricecompare/product/PriceCompareProductManager.php';
require_once GLOBAL_COMMONFILE_PATH.'/lib/mds/product/settings/UICategoryConst.php';
require_once GLOBAL_COMMONFILE_PATH . '/lib/mds/product/settings/StandardCategoryConst.php';
/* $aContentsMenuList */
require_once GLOBAL_COMMONFILE_PATH.'/lib/mds/product/settings/RightContentsConst.php';

$oDNWInput = new DNWInput();
$sViewType = $oDNWInput->postString("viewtype");
$nPhysicsCategoryCode1 = $oDNWInput->post('physicsCategoryCode1') ? $oDNWInput->post('physicsCategoryCode1') : 861;
$nPhysicsCategoryCode2 = $oDNWInput->post('physicsCategoryCode2') ? $oDNWInput->post('physicsCategoryCode2') : 873;
$sVideoUrl = $oDNWInput->postString('videoUrl') ? $oDNWInput->postString('videoUrl') : '';
$sToday = date('Y-m-d');
$oRestClient = new RestClient('cnas');
$sNewsApiUrl = "/news/getNewsApiList.json";
$aApiParam = array();
$aApiParam['limit'] = 5;
$aApiParam['boardSeq'] = '60,61,64,65,68';
$aApiParam['categorySeq1'] = 861; //auto 설정 미리보기는 임의로 [PC 주요 부품 > CPU] 카테고리로 지정
$aApiParam['categorySeq2'] = 873;

if($sViewType == 'dpgzone'){

	$aContentsType[] = $oDNWInput->postString('contentsNewsType') ? $oDNWInput->postString('contentsNewsType') : array();
	$aContentsTitle[] = $oDNWInput->postString('contentsNewsTitle') ? $oDNWInput->postString('contentsNewsTitle') : array();
	$aContentsImage[] = $oDNWInput->postString('contentsNewsImage') ? $oDNWInput->postString('contentsNewsImage') : array();
	$aContentsLink[] = $oDNWInput->postString('contentsNewsLink') ? $oDNWInput->postString('contentsNewsLink') : array();
	$aContentsDesc[] = $oDNWInput->postString('contentsNewsDesc') ? $oDNWInput->postString('contentsNewsDesc') : array();
	$aContentsEndDate[] = $oDNWInput->postString('contentsNewsEndDate') ? $oDNWInput->postString('contentsNewsEndDate') : array();

	if(count($aContentsTitle[0]) > 0){
		for($i = 0; $i < 5; $i++) {

			if($aContentsType[0][$i] == ''){
				$sContentsType = 'auto';
			} else {
				$sContentsType = $aContentsType[0][$i];
			}

			$aNewsContents['contents'][$i] = array();
			$aNewsContents['contents'][$i]['type'] = $sContentsType;

			if($sContentsType == 'manual') {
				$aNewsContents['contents'][$i]['title'] = $aContentsTitle[0][$i];
				$aNewsContents['contents'][$i]['imageUrl'] = $aContentsImage[0][$i];
				$aNewsContents['contents'][$i]['linkUrl'] = $aContentsLink[0][$i];
				$aNewsContents['contents'][$i]['desc'] = $aContentsDesc[0][$i];
				$aNewsContents['contents'][$i]['endDate'] = $aContentsEndDate[0][$i];
			}
		}
		$aNewsContents['group'] = 'NEWS';
		$aNewsContents['video']['videoUrl'] = $sVideoUrl.'?wmode=transparent';
	}

	$aShowContents = $aNewsContents['contents'];;
	$nContentsCount = count($aNewsContents['contents']);
	$sContentsGroup = $aNewsContents['group'];

} else { //표준시리즈 미리보기

	$sSiteName = $oDNWInput->postString('sitesName');

	switch ($sSiteName) {
		case "모니터":		$sCategoryC = "1"; break;
		case "노트북":		$sCategoryC = "2"; break;
		case "CPU":			$sCategoryC = "3"; break;
		case "RAM":			$sCategoryC = "4"; break;
		case "메인보드":	$sCategoryC = "5"; break;
		case "VGA":			$sCategoryC = "6"; break;
		case "SSD":			$sCategoryC = "7"; break;
		case "HDD":			$sCategoryC = "8"; break;
		case "케이스":		$sCategoryC = "9"; break;
		case "파워":			$sCategoryC = "10"; break;
		case "키보드":		$sCategoryC = "11"; break;
		case "마우스":		$sCategoryC = "12"; break;
		case "ODD":			$sCategoryC = "13"; break;
	}


	$aNewsContents['rightContents'][$nPhysicsCategoryCode2] = array();

	$aContents = array();
	$aContents['contents'] = array();
	for($i = 1; $i <= 5; $i++) {

		$sContentsType = $oDNWInput->postString('contentsNewsType_'.$i);

		if($sContentsType == '') {
			$sContentsType = 'auto';
		}

		$sContentsTitle = $oDNWInput->postString('contentsNewsTitle_'.$i);
		$sContentsImage = $oDNWInput->postString('contentsNewsImage_'.$i);
		$sContentsLink = $oDNWInput->postString('contentsNewsLink_'.$i);
		$sContentsDesc = $oDNWInput->postString('contentsNewsDesc_'.$i);
		$sContentsEndDate = $oDNWInput->postString('contentsNewsEndDate_'.$i);

		$aContents['contents'][$i] = array();
		$aContents['contents'][$i]['type'] = $sContentsType;

		if($sContentsType == 'manual') {
			$aContents['contents'][$i]['title'] = iconv('EUC-KR','UTF-8',$sContentsTitle);
			$aContents['contents'][$i]['imageUrl'] = $sContentsImage;
			$aContents['contents'][$i]['linkUrl'] = $sContentsLink;
			$aContents['contents'][$i]['desc'] = iconv('EUC-KR','UTF-8',$sContentsDesc);
			$aContents['contents'][$i]['endDate'] = $sContentsEndDate;
		}
	}

	$aNewsContents['newsContents'][$nPhysicsCategoryCode2]['showContents'][1] = $aContents;
	$aNewsContents['newsContents'][$nPhysicsCategoryCode2]['video']['videoUrl'] = $sVideoUrl;

	$aVideoUrl = array();
	$aVideoUrl = $aNewsContents['newsContents'][$nPhysicsCategoryCode2]['video'];

	if(count($aVideoUrl) > 0) {
		$sVideoUrl = $aVideoUrl['videoUrl'];
	}

	$aShowContents = array();
	$aShowContents = $aNewsContents['newsContents'][$nPhysicsCategoryCode2]['showContents'][1]['contents'];
	$sContentsGroup = 'NEWS';
	$nContentsCount = count($aShowContents);
}


//데이터 셋팅
if($nContentsCount > 0){
	$nManualContentsCount = 0;
	$aAutoContentsBoardSeqList = array();
	foreach ($aShowContents AS $nBoardSeq => $aContentsData){
		if($aContentsData['type'] == 'manual' && $aContentsData['endDate'] >= $sToday){
			$sContentTitle = stripslashes(urldecode($aContentsData['title']));
			$sContentTitle = StringUtils::cutString($sContentTitle, 100, '', 'UTF-8');
			$sContentDesc = stripslashes(urldecode($aContentsData['desc']));
			$sContentDesc = StringUtils::cutString($sContentDesc, 300, '', 'UTF-8');

			$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['title'] = $sContentTitle;
			$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['desc'] = $sContentDesc;
			$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['link'] = stripslashes(urldecode($aContentsData['linkUrl']));
			$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['image'] = stripslashes(urldecode($aContentsData['imageUrl']));
			$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['imageAlt'] = str_replace(array('\'','"'), '', $sContentTitle);
			$nManualContentsCount++;
		} else {
			$aAutoContentsBoardSeqList[] = $nBoardSeq;
		}
	}

	//auto
	if($nManualContentsCount < 4) {
		$sGetNewsApiListJson = $oRestClient->doGet($sNewsApiUrl,$aApiParam);
		$aGetNewsApiListJson = json_decode($sGetNewsApiListJson, true);
		if($aGetNewsApiListJson['resultMessage']['status'] == 200) {
			$aMediaData = $aGetNewsApiListJson['resultMessage']['data'];
			if(count($aMediaData) > 0) {
				foreach($aAutoContentsBoardSeqList as $nAutoContentsSeq => $nBoardSeq) {
					if($aMediaData[$nAutoContentsSeq]) {
						$aBoardList = $aMediaData[$nAutoContentsSeq];
						$sContentTitle = stripslashes(urldecode($aBoardList['title']));
						$sContentTitle = StringUtils::cutString($sContentTitle, 100, '', 'UTF-8');
						$sContentDesc = stripslashes(urldecode($aBoardList['summaryContent']));
						$sContentDesc = StringUtils::cutString($sContentDesc, 300, '', 'UTF-8');
						$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['title'] = $sContentTitle;
						$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['desc'] = $sContentDesc;
						$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['link'] = stripslashes(urldecode($aBoardList['link']));
						$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['image'] = stripslashes(urldecode($aBoardList['mainimage']));
						$aContentsList[$sContentsGroup]['boardList'][$nBoardSeq]['imageAlt'] = str_replace(array('\'','"'), '', $sContentTitle);
					}
				}
			}
		}
	}
	if(count($aContentsList[$sContentsGroup]['boardList']) > 0) {
		ksort($aContentsList[$sContentsGroup]['boardList']);
	}

}

$oTpl = new Template('tpl/');
$oTpl->set('aContentsList',$aContentsList);
$oTpl->set('sVideoUrl',$sVideoUrl);
echo $oTpl->fetch('newsContentsPreview.tpl.php');