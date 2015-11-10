<?php
return array(
	//主帐号,对应开官网发者主账号下的 ACCOUNT SID
	'smsAccountSid' 	=> 'aaf98f894a188342014a370d5c2c1077',

	//主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
	'smsAccountToken'	=> 'ecda672fd13f4edb89fb52044722fdf6',

	//应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
	//在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
	'smsAppId'			=> 'aaf98f8950ccb14f0150d059268e04d2',

	//请求地址
	//沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
	//生产环境（用户应用上线使用）：app.cloopen.com
	'smsServerIP'		=> 'app.cloopen.com',


	//请求端口，生产环境和沙盒环境一致
	'smsServerPort'		=> '8883',

	//REST版本号，在官网文档REST介绍中获得。
	'smsSoftVersion'	=> '2013-12-26',

	'smsTime'			=> '30',

	//开启语言包功能
	'LANG_SWITCH_ON'	=> true,
	'LANG_AUTO_DETECT'	=> true,
	'LANG_LIST'			=> 'zh-cn',
	'VAR_LANGUAGE'		=> '1',
);