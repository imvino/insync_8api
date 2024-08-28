<?php

require_once("networkHelper.php");

header("Content-type: application/rdp");
header("Content-disposition: attachment; filename=connect.rdp");

?>
compression:i:1
desktopwidth:i:1024
desktopheight:i:768
keyboardhook:i:2
audiomode:i:2
redirectclipboard:i:1
redirectdrives:i:1
redirectprinters:i:0
redirectcomports:i:0
redirectsmartcards:i:0
displayconnectionbar:i:1
autoreconnection enabled:i:1
alternate shell:s:
shell working directory:s:
disable wallpaper:i:1
disable full window drag:i:1
disable menu anims:i:1
disable themes:i:1
gatewaycredentialssource:i:0
disable cursor setting:i:0
bitmapcachepersistenable:i:1
session bpp:i:16
screen mode id:i:1
smart sizing:i:1
full address:s:<?php echo getInSyncIP()."\r\n"; ?>
username:s:Administrator
domain:s:\
authentication level:i:0