<?php
/**
 * ��ǰ����Ʈ �����̷���
 * @author kbk35
 * @since 2012-11-02
 */
$sRediret = '/list/';
$sQryStr = $_SERVER['QUERY_STRING'];
if($sQryStr) $sRediret.='?'.$sQryStr;
header('Location: '.$sRediret);