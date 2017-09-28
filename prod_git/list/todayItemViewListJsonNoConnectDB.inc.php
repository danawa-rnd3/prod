<?PHP
require_once 'com/danawa/settings/GlobalPathConst.php';
require_once 'com/danawa/util/ProductImage.php';
require_once 'com/danawa/web/util/DNWInput.php';

$oDNWInput = new DNWInput();

if(isset($_COOKIE['cookSaveProdInfo'])) {
    $aGetProdInfo =	explode('|',$_COOKIE['cookSaveProdInfo']);

    $nIdx =	0;
    foreach($aGetProdInfo AS $nProdCode)
    {
        $aProdInfo = explode(':', $nProdCode);

        if( $aProdInfo[0] == '' || is_null($aProdInfo[0]) ) $aProdInfo[0] = 1;

        $aGetSiteCode[$nIdx] = $aProdInfo[0];
        $aGetProdCode[$nIdx] = $aProdInfo[1];
        $nIdx++;
    }

    $nTotalCount = $nIdx;	// รัฐนผ๖

    $aTodayProdList = array();

    asort($aGetProdCode);

    if( $nTotalCount > 0 )
    {
        $nIdx = 0;
        foreach ($aGetProdCode AS $nKey => $nPCode)
        {
            $sProdImg = ProductImage::getProductImageUrl($nPCode,'80');
            $aTodayProdList[$nIdx]['linkUrl'] = "http://prod.danawa.com/info/?pcode=".$nPCode;
            $aTodayProdList[$nIdx]['imageUrl'] = str_replace('http:','',$sProdImg);
            $nIdx++;
        }
        unset($sProdImg);
    } else {
        $aTodayProdList = null;
    }

} else {
    $aTodayProdList = null;
}

echo $oDNWInput->getString('jsoncallback')."(".json_encode($aTodayProdList).");";
?>