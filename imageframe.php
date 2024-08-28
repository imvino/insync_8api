<?php
// Read operation code from the o= parameter
//  o=255 (0xFF)  normal image (default)
//  o=254 (0xFE)  normal image (raw, no markup)
//  o=253 (0xFD)  direct from camera (bypasses InSync)
//  o=50  (0x32)  Detector view (no optimization info or detection zone display, just detector results)
//  o=25  (0x19)  direct from camera (via InSync) with markup
//  o=24  (0x18)  panomorph unwrapped 4
//  o=23  (0x17)  panomorph unwrapped 3
//  o=22  (0x16)  panomorph unwrapped 2
//  o=21  (0x15)  panomorph unwrapped 1
//  o=20  (0x14)  panomorph raw image
//  o=19  (0x13)  background image
//  o=18  (0x12)  foreground image
//  o=17  (0x11)  reference image
//  o=1   (0x01)  edge image (raw, no markup)
//  o=0   (0x00)  edge image
$nOperation = 0xFF;
if (isset($_REQUEST['o']))
{
	if (($_REQUEST['o'] > -1) && ($_REQUEST['o'] < 256))
	{
		$nOperation = IntVal($_REQUEST['o']);
	}
}

if ($nOperation != 0xFD)
{
	require_once("helpers/imageframe_insync.php");
}
else 
{
	require_once("helpers/imageframe_direct.php");
}
?>