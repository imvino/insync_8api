<?php
use LuraCast\Restler\Resources;
require_once __DIR__ . '/../../helpers/constants.php';
require_once SITE_DOCUMENT_ROOT . 'helpers/restler.php';

use Luracast\Restler\Restler;

$rest = new Restler();
$rest->setSupportedFormats('XmlFormat', 'JsonFormat', 'JsFormat', 'JpgFormat', 'UploadFormat');
$rest->addAPIClass(Resources::class);
$rest->addAPIClass('Core','');
$rest->handle();
?>
