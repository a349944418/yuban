<?php
// Ӧ������ļ�
// ���PHP����
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

$_GET['m'] = 'Admin';
// ��������ģʽ ���鿪���׶ο��� ����׶�ע�ͻ�����Ϊfalse
define('APP_DEBUG',true);

// ����Ӧ��Ŀ¼
define('APP_PATH','./Apps/');

// ����ThinkPHP����ļ�
require '../Core/ThinkPHP.php';