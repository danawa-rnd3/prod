<?PHP
/************************************************************
 * ��  ġ : DOCUMENT_ROOT/include/prod_today_list.php       *
 * ��  �� : ���� �� ��ǰ ���̾� �ڽ� ���                   *
 * ��  �� :                                                 *
 * �ۼ��� : kbk35											*
 * �ۼ��� : 2007-01-25                                      *
 ***********************************************************/
require_once 'com/danawa/web/util/DNWInput.php';
require_once 'com/danawa/web/util/PriceCompareStatusType.php';
require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/util/PDOFactory.php';
require_once 'com/danawa/mds/member/service/MemberAuthManagerImpl.php';

$memberAuthManager = MemberAuthManagerImpl::getInstance();
$aAuthInfo = $memberAuthManager->simpleCheckAuth();

$oDNWInput = new DNWInput();

if(isset($_COOKIE['cookSaveProdInfo'])) {
    $aGetProdInfo =	explode('|',$_COOKIE['cookSaveProdInfo']);

    switch ( SERVER_ADOPTED_ENV ) {
    	// gnb_ui.js���� �ֱ� �� ��ǰ DB ���� ȣ��Ʈ�� ����ȯ������ �����ؾ� ���� ����
    	case 'LOCAL' :
    	case 'DEV' :
    		$sDocRoot	= $_SERVER['DOCUMENT_ROOT'];
    		$sCommonLib	= $sDocRoot . '/../common_files/lib';
    		$sCommonInc	= $sDocRoot . '/../common_files/include';
    		break;
    	default :
    		if(!$sCommonInc)
    			$sCommonInc = '/home/danawa/common_files/include';
    		if(!$sCommonLib)
    			$sCommonLib = '/home/danawa/common_files/lib';
    		break;
    }

    include_once $sCommonInc.'/funcImage.php';

    $oPDOFactory	= PDOFactory::create('eldanawa.slave');

    $nIdx =	0;
    foreach($aGetProdInfo AS $nProdCode)
    {
        $aProdInfo = explode(':', $nProdCode);

        if( $aProdInfo[0] == '' || is_null($aProdInfo[0]) ) $aProdInfo[0] = 1;

        $aGetSiteCode[$nIdx] = intval($aProdInfo[0]);
        $aGetProdCode[$nIdx] = intval($aProdInfo[1]);
        $aGetminPrice[$nIdx] = intval($aProdInfo[2]);

        $nIdx++;
    }

    $aAdultCategoryCode = array(9274, 54649, 54650); //���� ���� �ʿ� ī�װ� seq
    $nTotalCount = $nIdx;	// �Ѱ���

    $aTodayProdList = array();

//     asort($aGetProdCode);

    if( $nTotalCount > 0 )
    {
        $nIdx = 0;
        foreach ($aGetProdCode AS $nKey => $nPCode)
        {

            $sQuery = "
            SELECT
            	tPC.nCategorySeq1 categorySeq1,
            	tPC.nCategorySeq2 categorySeq2,
            	tPC.nCategorySeq3 categorySeq3,
            	tPC.nCategorySeq4 categorySeq4,
                tP.prod_n prod_n,
                tP.minprice_q minprice_q,
            	tP.shot_yn shotYN,
            	tP.input_d inputDate,
            	tP.make_d makeDate,
            	tP.dtPriceModifyDate priceModifyDate,
                tPB.brand_n brand_n,
                tPM.maker_n maker_n,
                tPBD.bundle_n bundle_n
            FROM
                tProductCategory tPC
                LEFT JOIN tprod tP ON (tPC.nProductSeq = tP.prod_c)
                LEFT JOIN tprod_brand tPB ON (tP.brand_c = tPB.brand_c)
                INNER JOIN tprod_maker tPM ON (tP.maker_c = tPM.maker_c)
                LEFT JOIN tprod_bundle tPBD ON (tP.prod_c = tPBD.prod_c)
            WHERE
                tP.prod_c = :pCode";

            $oStmt = $oPDOFactory->prepare($sQuery);
            $oStmt->bindParam(':pCode', $nPCode, PDO::PARAM_INT);
            $oStmt->execute();

            $row = $oStmt->fetch(PDO::FETCH_ASSOC);

            //���� ���� �˻�
            $oPriceCompareStatusType = new PriceCompareStatusType();
            $aParameter = array();
            $aParameter['type'] = 'json';
            $aParameter['inputDate'] = $row['inputDate'];
            $aParameter['makeDate'] = $row['makeDate'];
            $aParameter['priceModifyDate'] = $row['priceModifyDate'];
            $aParameter['price'] = $row['minprice_q'];
            $aParameter['shotYN'] = $row['shotYN'];

            $oStatusResult = $oPriceCompareStatusType->getPriceCompareStatusType($aParameter);
            $aStatusResult = json_decode($oStatusResult, true);

            $aCate = array($row['categorySeq1'], $row['categorySeq2'] , $row['categorySeq3'], $row['categorySeq4']);
            $bAdultCategory = false;
            foreach($aCate as $nCategorySeq){
            	if(in_array($nCategorySeq, $aAdultCategoryCode) === true){
            		$bAdultCategory = true;
            	}
            }

            $sProdImg = getProdImageURL($nPCode,'80');
            //�ش� ��ǰ�� ����ī�װ��϶�
            if((boolean)preg_match('/^admin-/',$aAuthInfo['id']) === FALSE) {
            	if($bAdultCategory == true) {
            		//���������� �޾Ҵٸ� ���
            		if($aAuthInfo['adult_chk'] != 'Y') {
            			$sProdImg = 'http://img.danawa.com/new/19limited/19_limited.jpg';
            		}
            	}
            }

            //$sProdImg	= 'http://img.danawa.com/prod_img/small/group_' . ( floor($nPCode / 500) ). '/' . $nPCode . '_1.jpg';
            $aTodayProdList[$nIdx]['linkUrl'] = "http://prod.danawa.com/info/?pcode=".$nPCode."&cate1=".$row['categorySeq1']."&cate2=".$row['categorySeq2']."&cate3=".$row['categorySeq3']."&cate4=".$row['categorySeq4'];

            $aTodayProdList[$nIdx]['imageUrl'] = str_replace('http:','',$sProdImg);
            $sMakerName = $row['maker_n'];
            $sBrandName = $row['brand_n'];
            $sProdFullName = "";

	        if($sMakerName == '��Ÿ' || $sMakerName == 'Ȯ����'){
				$sMakerName = '';
			}
			
			if($sBrandName == '��Ÿ' || $sBrandName == 'Ȯ����'){
				$sBrandName = '';
			}

			if(trim($sMakerName) == trim($sBrandName)){
				$sProdFullName = ($sMakerName != '') ? $sMakerName . ' ' : '';
			} else {
				$sProdFullName = ($sMakerName != '') ? $sMakerName . ' ' : '';
				$sProdFullName .= ($sBrandName != '') ? $sBrandName . ' ' : '';
			}

            if($row['prod_n']){
                $sProdFullName .= $row['prod_n'] . " ";
            }
            if($row['bundle_n']){
                $sProdFullName .= "(" . $row['bundle_n'] . ") ";
            }
            $aTodayProdList[$nIdx]['productName'] = iconv("EUC-KR","UTF-8",$sProdFullName);

            $aTodayProdList[$nIdx]['minPrice'] = $row['minprice_q'];
            $aTodayProdList[$nIdx]['productSeq'] = $nPCode;
            $aTodayProdList[$nIdx]['cookMinPrice'] = "$aGetminPrice[$nIdx]";
            $aTodayProdList[$nIdx]['statusCode'] = $aStatusResult['result']['code'];
            $aTodayProdList[$nIdx]['status'] = $aStatusResult['result']['message'];

            $nIdx++;
        }
        unset($sProdImg);
    } else {
        $aTodayProdList = null;
    }

} else {
    $aTodayProdList = null;
}

//print_r($aTodayProdList);
//print_r($aTodayProdList);
//�̹���, ��ǰurl, ��ǰ��, ������
echo $oDNWInput->getString('jsoncallback')."(".json_encode($aTodayProdList).");";
?>