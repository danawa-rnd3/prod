<?php
/**
 * 상품정보 리다이렉션
 * @author kbk35
 * @since 2012-11-02
 */
$sRediret = '/info/';
$sQryStr = $_SERVER['QUERY_STRING'];
if($sQryStr) $sRediret.='?'.$sQryStr;
header('Location: '.$sRediret);