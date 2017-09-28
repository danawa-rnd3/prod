<?php
{	#회원 아이디
	$aAffiliateList = Array (
		0 => 'GUEST',
		1 => 'slrclub',
	);
}

{	#로거채널서비스 채널값 정의
	$aAllowChannelVal = Array (
		$aAffiliateList[0]	=>	7,
		$aAffiliateList[1]	=>	15
	);
}

{	# 외부 아이프레임 제공이면 값에 사이즈 값을 가져오는 주소를 입력하고
	# 외부 아이프레임 제공이 아니면 ''을 입력하면 된다.
	# 가로값 : nWidth, 세로값 nHeight
	$aAllowIframeResize = Array (
		$aAffiliateList[0]	=>	'',
		$aAffiliateList[1]	=>	'http://price.slrclub.com/xss.php'
	);
}