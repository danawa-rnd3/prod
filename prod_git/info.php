<?php
/**
 * ��ǰ���� �����̷���
 * @author kbk35
 * @since 2012-11-02
 */
$sRediret = '/info/';
$sQryStr = $_SERVER['QUERY_STRING'];
if($sQryStr) $sRediret.='?'.$sQryStr;
header('Location: '.$sRediret);