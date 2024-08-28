<?php
// Constants for TCP Utility.

if (!defined('TRAVIS_TCP_HEADER'))
	define('TRAVIS_TCP_HEADER', 0xA1);

if (!defined('TRAVIS_FIELD_HEADER'))
	define('TRAVIS_FIELD_HEADER', 0xA2);
if (!defined('TRAVIS_ARRAY_HEADER'))
	define('TRAVIS_ARRAY_HEADER', 0xA3);


// TCP Commands
if (!defined('TRAVIS_TCP_COMMAND_PING'))
	define('TRAVIS_TCP_COMMAND_PING', 0x00);
if (!defined('TRAVIS_TCP_COMMAND_GETIMAGE'))
	define('TRAVIS_TCP_COMMAND_GETIMAGE', 0x01);
if (!defined('TRAVIS_TCP_COMMAND_ACKNOWLEDGE'))
	define('TRAVIS_TCP_COMMAND_ACKNOWLEDGE', 0x02);


if (!defined('TRAVIS_TCP_COMMAND_GETPROPERTY'))
	define('TRAVIS_TCP_COMMAND_GETPROPERTY', 0x10);
if (!defined('TRAVIS_TCP_COMMAND_SETPROPERTY'))
	define('TRAVIS_TCP_COMMAND_SETPROPERTY', 0x20);


// Array Types

if (!defined('TRAVIS_ARRAYTYPE_POINT'))
	define('TRAVIS_ARRAYTYPE_POINT', 0x10);
if (!defined('TRAVIS_ARRAYTYPE_RECT'))
	define('TRAVIS_ARRAYTYPE_RECT', 0x12);

if (!defined('TRAVIS_ARRAYTYPE_POLYGON'))
	define('TRAVIS_ARRAYTYPE_POLYGON', 0x20);

if (!defined('TRAVIS_ARRAYTYPE_DETECTIONZONE'))
	define('TRAVIS_ARRAYTYPE_DETECTIONZONE', 0x60);
if (!defined('TRAVIS_ARRAYTYPE_COUNTZONE'))
	define('TRAVIS_ARRAYTYPE_COUNTZONE', 0x70);


// TCP Image Retrieval Operations
if (!defined('TRAVIS_OP_CAMERA_GET_NORMAL'))
	define('TRAVIS_OP_CAMERA_GET_NORMAL', 0xFF);
if (!defined('TRAVIS_OP_CAMERA_GET_UNPROCESSED'))
	define('TRAVIS_OP_CAMERA_GET_UNPROCESSED', 0xFE);
if (!defined('TRAVIS_OP_CAMERA_GET_EDGE'))
	define('TRAVIS_OP_CAMERA_GET_EDGE', 0x00);
if (!defined('TRAVIS_OP_CAMERA_GET_FGEDGE'))
	define('TRAVIS_OP_CAMERA_GET_FGEDGE', 0x12);
if (!defined('TRAVIS_OP_CAMERA_GET_BGEDGE'))
	define('TRAVIS_OP_CAMERA_GET_BGEDGE', 0x13);


// TCP Acknowledge Operations
if (!defined('TRAVIS_OP_ACKNOWLEDGE_RESEND'))
	define('TRAVIS_OP_ACKNOWLEDGE_RESEND', 0x00);

if (!defined('TRAVIS_OP_ACKNOWLEDGE_CONFIRM'))
	define('TRAVIS_OP_ACKNOWLEDGE_CONFIRM', 0x10);
if (!defined('TRAVIS_OP_ACKNOWLEDGE_COMMIT'))
	define('TRAVIS_OP_ACKNOWLEDGE_COMMIT', 0x20);

// TCP Intersection Properties
if (!defined('TRAVIS_PROP_INTERSECTION_NAME'))
	define('TRAVIS_PROP_INTERSECTION_NAME', 0x01);


// TCP Direction Properties
if (!defined('TRAVIS_PROP_DIRECTION_NAME'))
	define('TRAVIS_PROP_DIRECTION_NAME', 0x01);
    
if (!defined('TRAVIS_PROP_DIRECTION_DIRECTION'))
	define('TRAVIS_PROP_DIRECTION_DIRECTION', 0x02);
    
if (!defined('TRAVIS_PROP_DIRECTION_FRAMERATE'))
	define('TRAVIS_PROP_DIRECTION_FRAMERATE', 0x03);
    
if (!defined('TRAVIS_PROP_DIRECTION_THRESHOLD'))
	define('TRAVIS_PROP_DIRECTION_THRESHOLD', 0x04);

if (!defined('TRAVIS_PROP_DIRECTION_THRESHOLDDIFFERENCE'))
	define('TRAVIS_PROP_DIRECTION_THRESHOLDDIFFERENCE', 0x05);

if (!defined('TRAVIS_PROP_DIRECTION_PROCESSINGFRAMERATE'))
	define('TRAVIS_PROP_DIRECTION_PROCESSINGFRAMERATE', 0x06);
    
if (!defined('TRAVIS_PROP_DIRECTION_MINNUMBRIGHTPIXELSFORTARGETVEH'))
	define('TRAVIS_PROP_DIRECTION_MINNUMBRIGHTPIXELSFORTARGETVEH', 0x07);
    
if (!defined('TRAVIS_PROP_DIRECTION_MINPERCENTAGEFORTMCZONE'))
	define('TRAVIS_PROP_DIRECTION_MINPERCENTAGEFORTMCZONE', 0x08);
    
if (!defined('TRAVIS_PROP_DIRECTION_DETZONEBUFSIZE'))
	define('TRAVIS_PROP_DIRECTION_DETZONEBUFSIZE', 0x09);
    
if (!defined('TRAVIS_PROP_DIRECTION_TMCZONEBUFSIZE'))
	define('TRAVIS_PROP_DIRECTION_TMCZONEBUFSIZE', 0x0A);
    
if (!defined('TRAVIS_PROP_DIRECTION_SENSITIVITY'))
	define('TRAVIS_PROP_DIRECTION_SENSITIVITY', 0x10);
    
if (!defined('TRAVIS_PROP_DIRECTION_DETECTIONZONES'))
	define('TRAVIS_PROP_DIRECTION_DETECTIONZONES', 0x20);

if (!defined('TRAVIS_PROP_DIRECTION_SEGMENTS'))
	define('TRAVIS_PROP_DIRECTION_SEGMENTS', 0x30);

if (!defined('TRAVIS_PROP_DIRECTION_MASKS'))
	define('TRAVIS_PROP_DIRECTION_MASKS', 0x40);
    


// TCP Devices
if (!defined('TRAVIS_DEVICE_INTERSECTION'))
	define('TRAVIS_DEVICE_INTERSECTION', 0x00);
if (!defined('TRAVIS_DEVICE_SERIAL'))
	define('TRAVIS_DEVICE_SERIAL', 0x01);
if (!defined('TRAVIS_DEVICE_CAMERAS'))
	define('TRAVIS_DEVICE_CAMERAS', 0x02);
	
if (!defined('TRAVIS_DEVICE_SYSTEM'))
	define('TRAVIS_DEVICE_SYSTEM', 0x08);

if (!defined('TRAVIS_DEVICE_NORTH'))
	define('TRAVIS_DEVICE_NORTH', 0x10);
if (!defined('TRAVIS_DEVICE_SOUTH'))
	define('TRAVIS_DEVICE_SOUTH', 0x11);
if (!defined('TRAVIS_DEVICE_EAST'))
	define('TRAVIS_DEVICE_EAST', 0x12);
if (!defined('TRAVIS_DEVICE_WEST'))
	define('TRAVIS_DEVICE_WEST', 0x13);


// Direction-Based Devices (Used for GetImage Command)
if (!defined('TRAVIS_DEVICE_CAMERA_1'))
	define('TRAVIS_DEVICE_CAMERA_1', 0x01);
if (!defined('TRAVIS_DEVICE_CAMERA_2'))
	define('TRAVIS_DEVICE_CAMERA_2', 0x02);
if (!defined('TRAVIS_DEVICE_CAMERA_3'))
	define('TRAVIS_DEVICE_CAMERA_3', 0x04);
if (!defined('TRAVIS_DEVICE_CAMERA_4'))
	define('TRAVIS_DEVICE_CAMERA_4', 0x08);


if (!defined('TRAVIS_ERROR_BASE'))
	define('TRAVIS_ERROR_BASE', 1000);

if (!defined('TRAVIS_ERROR_INVALIDPACKET'))
	define('TRAVIS_ERROR_INVALIDPACKET', TRAVIS_ERROR_BASE + 1);
if (!defined('TRAVIS_ERROR_INVALIDDEVICE'))
	define('TRAVIS_ERROR_INVALIDDEVICE', TRAVIS_ERROR_BASE + 2);
if (!defined('TRAVIS_ERROR_BADCHECKSUM'))
	define('TRAVIS_ERROR_BADCHECKSUM', TRAVIS_ERROR_BASE + 3);
if (!defined('TRAVIS_ERROR_BADMD5'))
	define('TRAVIS_ERROR_BADMD5', TRAVIS_ERROR_BASE + 4);

if (!defined('TRAVIS_ERROR_INVALIDCOMMAND'))
	define('TRAVIS_ERROR_INVALIDCOMMAND', TRAVIS_ERROR_BASE + 5);



function calcChecksum($szBuffer, $nLen)
{
	$nCRC = 0;
	for( $n = 0; $n < $nLen; $n++)
	{
		$nCRC += ord($szBuffer[$n]);
	}
	return $nCRC % 255;
}

function parseInterfaceProperties($szBuffer, &$nOffset)
{
    $aReturn = [];
    $szTemp = '';
    $szValue = '';
    $nProperty = 0;
    $nLength = 0;
    $nCRC = 0;
    $szDebug = "";
    //Validate Packet Header
    if (ord($szBuffer[$nOffset]) == TRAVIS_FIELD_HEADER)
    {
        $szDebug .= "Packet Header Valid\n";
        switch (ord($szBuffer[$nOffset+1]))
        {
            case TRAVIS_PROP_DIRECTION_NAME:
            case TRAVIS_PROP_DIRECTION_DIRECTION:
            case TRAVIS_PROP_DIRECTION_FRAMERATE:
            case TRAVIS_PROP_DIRECTION_THRESHOLD:
            case TRAVIS_PROP_DIRECTION_THRESHOLDDIFFERENCE:
            case TRAVIS_PROP_DIRECTION_PROCESSINGFRAMERATE:
            case TRAVIS_PROP_DIRECTION_MINNUMBRIGHTPIXELSFORTARGETVEH:
            case TRAVIS_PROP_DIRECTION_MINPERCENTAGEFORTMCZONE:
            case TRAVIS_PROP_DIRECTION_DETZONEBUFSIZE:
            case TRAVIS_PROP_DIRECTION_TMCZONEBUFSIZE:
                $nProperty = ord($szBuffer[$nOffset+1]);
                $nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
                if ($nLength)
                    $szValue = substr($szBuffer, $nOffset + 4, $nLength);
                    $szTemp = substr($szBuffer, $nOffset, 5 + $nLength);
                    $nCRC = calcChecksum($szTemp, 4 + $nLength);
                    //echo "\n First Byte:" . $nCRC . "\nSecond Byte:" . ord($szBuffer[$nOffset + $nLength + 4]) . "\n\n";
                    if ($nCRC == ord($szBuffer[$nOffset + $nLength + 4]))
                    {
                        $aReturn = ['szRaw' => $szTemp, 'nProperty' => $nProperty, 'nLength' => $nLength, 'szValue' => $szValue, 'nCRC' => $nCRC];
                    }
                break;
            case TRAVIS_PROP_DIRECTION_DETECTIONZONES:
                $nProperty = ord($szBuffer[$nOffset+1]);
				$szDebug .= "Property is a Detection Zone\n";
                $nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
				$szDebug .= "Length in Bytes: $nLength\n";
				
                if ($nLength)
                    $szValue = substr($szBuffer, $nOffset + 4, $nLength);
                    $szTemp = substr($szBuffer, $nOffset, 5 + $nLength);
                    $nCRC = calcChecksum($szTemp, 4 + $nLength);
                    $szDebug .= "Field Checksum:" . $nCRC . "\nChecksum in Buffer:" . ord($szBuffer[$nOffset + $nLength + 4]) . "\n\n";
                    if ($nCRC == ord($szBuffer[$nOffset + $nLength + 4]))
                    {
						$nTempOffset = 0;
						$szValue = parsePropertyArray($szValue, $nTempOffset);
                        $aReturn = ['szRaw' => $szTemp, 'nProperty' => $nProperty, 'nLength' => $nLength, 'szValue' => $szValue, 'nCRC' => $nCRC];
                    }
                break;
            case TRAVIS_PROP_DIRECTION_SEGMENTS:
                $nProperty = ord($szBuffer[$nOffset+1]);
				$szDebug .= "Property is a segment list\n";
                $nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
				$szDebug .= "Length in Bytes: $nLength\n";
				
                if ($nLength)
                    $szValue = substr($szBuffer, $nOffset + 4, $nLength);
                    $szTemp = substr($szBuffer, $nOffset, 5 + $nLength);
                    $nCRC = calcChecksum($szTemp, 4 + $nLength);
                    $szDebug .= "Field Checksum:" . $nCRC . "\nChecksum in Buffer:" . ord($szBuffer[$nOffset + $nLength + 4]) . "\n\n";
                    if ($nCRC == ord($szBuffer[$nOffset + $nLength + 4]))
                    {
						//$nTempOffset = 0;
						$szValue = parsePropertyArray($szValue, $nOffset);
                        $aReturn = ['szRaw' => $szTemp, 'nProperty' => $nProperty, 'nLength' => $nLength, 'szValue' => $szValue, 'nCRC' => $nCRC];
                    }
                break;
        }
    }
    $nOffset+=5 + $nLength;
    $aReturn = ['szRaw' => $szTemp, 'nProperty' => $nProperty, 'nLength' => $nLength, 'szValue' => $szValue, 'nCRC' => $nCRC, 'szDebug'=> $szDebug];
    return $aReturn;
}

function parsePropertyArray(&$szBuffer, &$nOffset)
{
	$aTemp = [];
	$nTempOffset = 0;
    if (ord($szBuffer[$nOffset]) == TRAVIS_ARRAY_HEADER)
    {
        //echo "Packet Header Valid\n";
        switch (ord($szBuffer[$nOffset+1]))
        {
			case TRAVIS_ARRAYTYPE_POINT:
                $nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
				$nOffset += 4;
                for ($nIndex = 0; $nIndex < $nLength; $nIndex++)
				{
					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$x0 = $nTemp;
					
					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$y0 = $nTemp;
					array_push($aTemp, [$x0, $y0]);
				}
				break;
			case TRAVIS_ARRAYTYPE_RECT:
                $nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
				$nOffset += 4;
                for ($nIndex = 0; $nIndex < $nLength; $nIndex++)
				{
					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$x0 = $nTemp;
					
					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$y0 = $nTemp;
					
					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$x1 = $nTemp;

					$nTemp = (ord($szBuffer[$nOffset++]));
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 8);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 16);
					$nTemp = $nTemp | (ord($szBuffer[$nOffset++]) << 24);
					$y1 = $nTemp;
					
					array_push($aTemp, [$x0, $y0, $x1, $y1]);
				}
				break;
			case TRAVIS_ARRAYTYPE_DETECTIONZONE:
                $nArrayLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
				//$nTempOffset = $nOffset + 4;
				$nOffset += 4;
				for ($nIndex = 0; $nIndex < $nArrayLength; $nIndex++)
				{
					//echo "$nOffset\n";
					// Parse Out Zone Type
					$nProperty = ord($szBuffer[$nOffset+1]);
					$nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
					if ($nLength)
					{
						$szValue = substr($szBuffer, $nOffset + 4, $nLength);
						array_push($aTemp, $szValue);
					    $nOffset+=4 + $nLength;
					}
					// Parse Out Zone Number
					$nProperty = ord($szBuffer[$nOffset+1]);
					$nLength = ord($szBuffer[$nOffset+2]) | (ord($szBuffer[$nOffset+3])<<8);
					if ($nLength)
					{
						$szValue = substr($szBuffer, $nOffset + 4, $nLength);
						array_push($aTemp, $szValue);
					    $nOffset+=4 + $nLength;
					}
					
					// Parse Vertices for Detection Zone
					array_push($aTemp, parsePropertyArray($szBuffer, $nOffset));
					// Parse Vertices for Count Zone
					array_push($aTemp, parsePropertyArray($szBuffer, $nOffset));
				}
				break;
		}
		$nOffset++;
	}
	
	return $aTemp;
}

function findPropertyValue($aFields, $nFieldID)
{
    foreach($aFields as $aField)
    {
        if ($aField['nProperty'] == $nFieldID)
            return $aField['szValue'];
    }
    return '';
}

function transmitPacket($szBuffer, $szAddress, $nPort)
{
    if ( $objSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) )
    {
        //Socket Created Successfully
        if ( socket_connect($objSocket, $szAddress, $nPort) )
        {
            // Send Packet to Server
            socket_write($objSocket, $szBuffer, strlen($szBuffer));
            
            // Read Response
            $szReturn = '';
            while ($out = socket_read($objSocket, 2048)) 
            {
               $szReturn .= $out;
            }
        }
		socket_close($objSocket);
    }
    return $szReturn;
}

function transmitRequest($szBuffer, $szAddress, $nPort)
{
    if ( $objSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) )
    {
        //Socket Created Successfully
        if ( socket_connect($objSocket, $szAddress, $nPort) )
        {
            // Send Packet to Server
            socket_write($objSocket, $szBuffer . MD5($szBuffer), strlen($szBuffer) + 32);
            
            // Read Response
            $szReturn = '';
            while ($out = socket_read($objSocket, 2048)) 
            {
               $szReturn .= $out;
            }
            
            $aFields = [];
            
            //Validate Packet
            if (ord($szReturn[0]) == TRAVIS_TCP_HEADER)
            {
                if (ord($szReturn[1]) == TRAVIS_TCP_COMMAND_ACKNOWLEDGE)
                {
                    switch (ord($szReturn[2]))
                    {
                        case TRAVIS_DEVICE_NORTH:
                        case TRAVIS_DEVICE_SOUTH:
                        case TRAVIS_DEVICE_EAST:
                        case TRAVIS_DEVICE_WEST:
                        case TRAVIS_DEVICE_INTERSECTION:
                            $nCRC = calcChecksum($szReturn, strlen($szReturn)-1);
                            if ( $nCRC == ord($szReturn[strlen($szReturn)-1]) )
                                //echo "Packet Received: <br>{$szReturn}, CRC: {$nCRC}\n";
                                
                            $nNumberFields = ord($szReturn[3]);
                            //echo "Number of Fields in Response: {$nNumberFields}\n";
                            $nOffset = 4;
                            for ($nIndex = 0; $nIndex < $nNumberFields; $nIndex++)
                            {
                                $aField = parseInterfaceProperties($szReturn, $nOffset);
                                //var_dump($aField);
                                if (count($aField))
                                {
                                    array_push($aFields, $aField);
                                }
                            }
                            break;
                    }
                }
            }
        }
		socket_close($objSocket);
    }
    return $aFields;
}

function buildTcpField($nPropertyID, $nFieldSize, $szData)
{
	$szPacket = "";
	$nCRC = 0;
	$szPacket = pack ('CCCC', TRAVIS_FIELD_HEADER, $nPropertyID, $nFieldSize & 255, ($nFieldSize>>8) & 255);
	$szPacket .= $szData;
	$nCRC = calcChecksum($szPacket, strlen($szPacket));
	$szPacket .= pack('C', $nCRC);
	return $szPacket;
}

function buildTcpPacket($nCommand, $nDevice, $nNumFields, $szData)
{
	$szPacket = "";
	$nCRC = 0;
	$szPacket = pack ('CCCC', TRAVIS_TCP_HEADER, $nCommand, $nDevice, $nNumFields & 255);
	$szPacket .= $szData;
	$nCRC = calcChecksum($szPacket, strlen($szPacket));
	$szPacket .= pack('C', $nCRC);
	return $szPacket;
}

function buildTcpArray(&$szBuffer, $nType, $nLength, $pObject)
{
	$szPacket = "";
	$nCRC = 0;
	$szPacket = pack ('CCCC', TRAVIS_ARRAY_HEADER, $nType, ($nLength & 255), (($nLength >> 8) & 255));
	//$szPacket .= $szData
	
	
	$nCRC = calcChecksum($szPacket, strlen($szPacket));
	$szPacket .= pack('C', $nCRC);
	return $szPacket;
}

function validatePacket($szPacket)
{
	// Validate Packet Structure
	if (ord($szPacket[0]) != TRAVIS_TCP_HEADER)
	{
		$nErrorCode = TRAVIS_ERROR_INVALIDPACKET;
		//echo "\n\nPacket Error. Bad TCP Header";
		return $nErrorCode;
	}
	
	// Check For Valid Command
	switch (ord($szPacket[1]))
	{
		case TRAVIS_TCP_COMMAND_PING:
		case TRAVIS_TCP_COMMAND_GETIMAGE:
		case TRAVIS_TCP_COMMAND_ACKNOWLEDGE:
		case TRAVIS_TCP_COMMAND_GETPROPERTY:
		case TRAVIS_TCP_COMMAND_SETPROPERTY:
			// Do Nothing Command is recognized
			break;
		default:
			$nErrorCode = TRAVIS_ERROR_INVALIDCOMMAND;
			//echo "\n\nPacket Error. Invalid Command";
			return $nErrorCode;
	}
	
	switch (ord($szPacket[2]))
	{
		case TRAVIS_DEVICE_INTERSECTION:
		case TRAVIS_DEVICE_SERIAL:
		case TRAVIS_DEVICE_CAMERAS:
		case TRAVIS_DEVICE_SYSTEM:
		case TRAVIS_DEVICE_NORTH:
		case TRAVIS_DEVICE_SOUTH:
		case TRAVIS_DEVICE_EAST:
		case TRAVIS_DEVICE_WEST:
		case TRAVIS_DEVICE_CAMERA_1:
		case TRAVIS_DEVICE_CAMERA_2:
		case TRAVIS_DEVICE_CAMERA_3:
		case TRAVIS_DEVICE_CAMERA_4:
			break;
		default:
			$nErrorCode = TRAVIS_ERROR_INVALIDDEVICE;
			//echo "\n\nPacket Error. Invalid Device";
			return $nErrorCode;
	}

	$nCRC = calcChecksum($szPacket, strlen($szPacket) - 33);
	if ($nCRC != ord($szPacket[strlen($szPacket) - 33]))
	{
		$nErrorCode = TRAVIS_ERROR_BADCHECKSUM;
		//echo "\n\nCRC Error. Length: " . strlen($szPacket) . " CRC: $nCRC, File CRC: " . ord($szPacket[strlen($szPacket) - 33]);
		return $nErrorCode;
	}	
	
	$nMD5Offset = strlen($szPacket) - 32;
	$szMD5 = substr($szPacket, $nMD5Offset, 32);
	$szTemp = substr($szPacket, 0, $nMD5Offset);

	if (MD5($szTemp) != $szMD5)
	{
		$nErrorCode = TRAVIS_ERROR_BADMD5;
		//echo "\n\nMD5 Error. Length: " . strlen($szPacket) . " MD5: " . MD5($szTemp) . ", File CRC: $szMD5";
		return $nErrorCode;
	}
	
	return 0;
}

$nPort = 50000;
$szAddress = '127.0.0.1';

?>