<?php
define('SITE_DOCUMENT_ROOT',
	str_replace('\\','/',substr(__DIR__, 0, strlen(__DIR__) - strlen('/helpers')))
	.'/');
define('SITE_PREFIX',
	preg_replace('/^.*\\/www(\\/?[^\\/]*)\\/helpers\\/constants.php$/', '${1}', str_replace('\\', '/', __FILE__))
	);
define('SITE_BASE_URL',
	(isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
	.$_SERVER['HTTP_HOST']
	.SITE_PREFIX
	.'/'
	);
?>
