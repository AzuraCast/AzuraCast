<?
// 15/07/2006 (2.6)
// - Changed the algorithm to parse the ZIP file.. Now, the script will try to mount the compressed
//   list, searching on the 'Central Dir' records. If it fails, the script will try to search by
//   checking every signature. Thanks to Jayson Cruz for pointing it.
// 25/01/2006 (2.51)
// - Fixed bug when calling 'unzip' without calling 'getList' first. Thanks to Bala Murthu for pointing it.
// 01/12/2006 (2.5)
// - Added optional parameter "applyChmod" for the "unzip()" method. It auto applies the given chmod for
//   extracted files.
// - Permission 777 (all read-write-exec) is default. If you want to change it, you'll need to make it
//   explicit. (If you want the OS to determine, set "false" as "applyChmod" parameter)
// 28/11/2005 (2.4)
// - dUnzip2 is now compliant with old-style "Data Description", made by some compressors,
//   like the classes ZipLib and ZipLib2 by 'Hasin Hayder'. Thanks to Ricardo Parreno for pointing it.
// 09/11/2005 (2.3)
// - Added optional parameter '$stopOnFile' on method 'getList()'.
//   If given, file listing will stop when find given filename. (Useful to open and unzip an exact file)
// 06/11/2005 (2.21)
// - Added support to PK00 file format (Packed to Removable Disk) (thanks to Lito [PHPfileNavigator])
// - Method 'getExtraInfo': If requested file doesn't exist, return FALSE instead of Array()
// 31/10/2005 (2.2)
// - Removed redundant 'file_name' on centralDirs declaration (thanks to Lito [PHPfileNavigator])
// - Fixed redeclaration of file_put_contents when in PHP4 (not returning true)

##############################################################
# Class dUnzip2 v2.6
#
#  Author: Alexandre Tedeschi (d)
#  E-Mail: alexandrebr at gmail dot com
#  Londrina - PR / Brazil
#
#  Objective:
#    This class allows programmer to easily unzip files on the fly.
#
#  Requirements:
#    This class requires extension ZLib Enabled. It is default
#    for most site hosts around the world, and for the PHP Win32 dist.
#
#  To do:
#   * Error handling
#   * Write a PHP-Side gzinflate, to completely avoid any external extensions
#   * Write other decompress algorithms
#
#  If you modify this class, or have any ideas to improve it, please contact me!
#  You are allowed to redistribute this class, if you keep my name and contact e-mail on it.
#
#  PLEASE! IF YOU USE THIS CLASS IN ANY OF YOUR PROJECTS, PLEASE LET ME KNOW!
#  If you have problems using it, don't think twice before contacting me!
#
##############################################################

if(!function_exists('file_put_contents')){
	// If not PHP5, creates a compatible function
	Function file_put_contents($file, $data){
		if($tmp = fopen($file, "w")){
			fwrite($tmp, $data);
			fclose($tmp);
			return true;
		}
		echo "<b>file_put_contents:</b> Cannot create file $file<br>";
		return false;
	}
}

class dUnzip2
{
	// Public
	var $fileName;
	var $compressedList; // You will problably use only this one!
	var $centralDirList; // Central dir list... It's a kind of 'extra attributes' for a set of files
	var $endOfCentral;   // End of central dir, contains ZIP Comments
	var $debug;
	
	// Private
	var $fh;
	var $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
	var $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
	var $dirSignatureE= "\x50\x4b\x05\x06"; // end of central dir signature
	var $overwrite = false;

	function getVersion(){
		return "2.6";
	}
	
	function setOverwrite($new_overwrite_value = false)
	{
		$this->overwrite = $new_overwrite_value;
	}

	// Public
	function dUnzip2($fileName){
		$this->fileName       = $fileName;
		$this->compressedList = 
		$this->centralDirList = 
		$this->endOfCentral   = Array();
	}
	
	function getList($stopOnFile=false){
		if(sizeof($this->compressedList)){
			$this->debugMsg(1, "Returning already loaded file list.");
			return $this->compressedList;
		}
		
		// Open file, and set file handler
		$fh = fopen($this->fileName, "r");
		$this->fh = &$fh;
		if(!$fh){
			$this->debugMsg(2, "Failed to load file.");
			return false;
		}
		
		$this->debugMsg(1, "Loading list from 'End of Central Dir' index list...");
		if(!$this->_loadFileListByEOF($fh, $stopOnFile)){
			$this->debugMsg(1, "Failed! Trying to load list looking for signatures...");
			if(!$this->_loadFileListBySignatures($fh, $stopOnFile)){
				$this->debugMsg(1, "Failed! Could not find any valid header.");
				$this->debugMsg(2, "ZIP File is corrupted or empty");
				return false;
			}
		}
		
		if($this->debug){
			#------- Debug compressedList
			$kkk = 0;
			echo "<table border='0' style='font: 11px Verdana; border: 1px solid #000'>";
			foreach($this->compressedList as $fileName=>$item){
				if(!$kkk && $kkk=1){
					echo "<tr style='background: #ADA'>";
					foreach($item as $fieldName=>$value)
						echo "<td>$fieldName</td>";
					echo '</tr>';
				}
				echo "<tr style='background: #CFC'>";
				foreach($item as $fieldName=>$value){
					if($fieldName == 'lastmod_datetime')
						echo "<td title='$fieldName' nowrap='nowrap'>".date("d/m/Y H:i:s", $value)."</td>";
					else
						echo "<td title='$fieldName' nowrap='nowrap'>$value</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
			
			#------- Debug centralDirList
			$kkk = 0;
			if(sizeof($this->centralDirList)){
				echo "<table border='0' style='font: 11px Verdana; border: 1px solid #000'>";
				foreach($this->centralDirList as $fileName=>$item){
					if(!$kkk && $kkk=1){
						echo "<tr style='background: #AAD'>";
						foreach($item as $fieldName=>$value)
							echo "<td>$fieldName</td>";
						echo '</tr>';
					}
					echo "<tr style='background: #CCF'>";
					foreach($item as $fieldName=>$value){
						if($fieldName == 'lastmod_datetime')
							echo "<td title='$fieldName' nowrap='nowrap'>".date("d/m/Y H:i:s", $value)."</td>";
						else
							echo "<td title='$fieldName' nowrap='nowrap'>$value</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
			}
		
			#------- Debug endOfCentral
			$kkk = 0;
			if(sizeof($this->endOfCentral)){
				echo "<table border='0' style='font: 11px Verdana' style='border: 1px solid #000'>";
				echo "<tr style='background: #DAA'><td colspan='2'>dUnzip - End of file</td></tr>";
				foreach($this->endOfCentral as $field=>$value){
					echo "<tr>";
					echo "<td style='background: #FCC'>$field</td>";
					echo "<td style='background: #FDD'>$value</td>";
					echo "</tr>";
				}
				echo "</table>";
			}
		}
		
		return $this->compressedList;
	}
	
	function getExtraInfo($compressedFileName){
		return
			isset($this->centralDirList[$compressedFileName])?
			$this->centralDirList[$compressedFileName]:
			false;
	}
	
	function getZipInfo($detail=false){
		return $detail?
			$this->endOfCentral[$detail]:
			$this->endOfCentral;
	}
	
	function unzip($compressedFileName, $targetFileName=false, $applyChmod=0777){
		if(!sizeof($this->compressedList)){
			$this->debugMsg(1, "Trying to unzip before loading file list... Loading it!");
			$this->getList(false, $compressedFileName);
		}
		
		$fdetails = &$this->compressedList[$compressedFileName];
		if(!isset($this->compressedList[$compressedFileName])){
			$this->debugMsg(2, "File '<b>$compressedFileName</b>' is not compressed in the zip.");
			return false;
		}
		if(substr($compressedFileName, -1) == "/"){
			$this->debugMsg(2, "Trying to unzip a folder name '<b>$compressedFileName</b>'.");
			return false;
		}
		
		if(!$fdetails['uncompressed_size']){
			$this->debugMsg(1, "File '<b>$compressedFileName</b>' is empty.");
			return $targetFileName?
				file_put_contents($targetFileName, ""):
				"";
		}
		
		fseek($this->fh, $fdetails['contents-startOffset']);
		$ret = $this->uncompress(
				fread($this->fh, $fdetails['compressed_size']),
				$fdetails['compression_method'],
				$fdetails['uncompressed_size'],
				$targetFileName
			);
		if($applyChmod && $targetFileName)
			chmod($targetFileName, 0777);
		
		return $ret;
	}
	
	function unzipAll($targetDir=false, $baseDir="", $maintainStructure=true, $applyChmod=0777){
		if($targetDir === false)
			$targetDir = dirname(__FILE__)."/";
		
		$lista = $this->getList();
		if(sizeof($lista)) foreach($lista as $fileName=>$trash){
			$dirname  = dirname($fileName);
			$outDN    = "$targetDir/$dirname";
			
			if(substr($dirname, 0, strlen($baseDir)) != $baseDir)
				continue;
			
			if(!is_dir($outDN) && $maintainStructure){
				$str = "";
				$folders = explode("/", $dirname);
				foreach($folders as $folder){
					$str = $str?"$str/$folder":$folder;
					if(!is_dir("$targetDir/$str")){
						$this->debugMsg(1, "Creating folder: $targetDir/$str");
						mkdir("$targetDir/$str");
						if($applyChmod)
							chmod("$targetDir/$str", $applyChmod);
					}
				}
			}
			if(substr($fileName, -1, 1) == "/")
				continue;
				
			if ($maintainStructure)
			{
				if ($this->overwrite)
				{
					unlink("$targetDir/$fileName");
				}
				$this->unzip($fileName, "$targetDir/$fileName", $applyChmod);
			}
			else
			{
				if ($this->overwrite)
				{
					unlink("$targetDir/$fileName");
				}
				$this->unzip($fileName, "$targetDir/".basename($fileName), $applyChmod);
			}
		}
	}
	
	function close(){     // Free the file resource
		if($this->fh)
			fclose($this->fh);
	}
	
	function __destroy(){ 
		$this->close();
	}
	
	// Private (you should NOT call these methods):
	function uncompress($content, $mode, $uncompressedSize, $targetFileName=false){
		switch($mode){
			case 0:
				// Not compressed
				return $targetFileName?
					file_put_contents($targetFileName, $content):
					$content;
			case 1:
				$this->debugMsg(2, "Shrunk mode is not supported... yet?");
				return false;
			case 2:
			case 3:
			case 4:
			case 5:
				$this->debugMsg(2, "Compression factor ".($mode-1)." is not supported... yet?");
				return false;
			case 6:
				$this->debugMsg(2, "Implode is not supported... yet?");
				return false;
			case 7:
				$this->debugMsg(2, "Tokenizing compression algorithm is not supported... yet?");
				return false;
			case 8:
				// Deflate
				return $targetFileName?
					file_put_contents($targetFileName, gzinflate($content, $uncompressedSize)):
					gzinflate($content, $uncompressedSize);
			case 9:
				$this->debugMsg(2, "Enhanced Deflating is not supported... yet?");
				return false;
			case 10:
				$this->debugMsg(2, "PKWARE Date Compression Library Impoloding is not supported... yet?");
				return false;
           case 12:
               // Bzip2
               return $targetFileName?
                   file_put_contents($targetFileName, bzdecompress($content)):
                   bzdecompress($content);
			case 18:
				$this->debugMsg(2, "IBM TERSE is not supported... yet?");
				return false;
			default:
				$this->debugMsg(2, "Unknown uncompress method: $mode");
				return false;
		}
	}
	
	function debugMsg($level, $string){
		if($this->debug)
			if($level == 1)
				echo "<b style='color: #777'>dUnzip2:</b> $string<br>";
			if($level == 2)
				echo "<b style='color: #F00'>dUnzip2:</b> $string<br>";
	}

	function _loadFileListByEOF(&$fh, $stopOnFile=false){
		// Check if there's a valid Central Dir signature.
		// Let's consider a file comment smaller than 1024 characters...
		// Actually, it length can be 65536.. But we're not going to support it.
		
		for($x = 0; $x < 1024; $x++){
			fseek($fh, -22-$x, SEEK_END);
			
			$signature = fread($fh, 4);
			if($signature == $this->dirSignatureE){
				// If found EOF Central Dir
				$eodir['disk_number_this']   = unpack("v", fread($fh, 2)); // number of this disk
				$eodir['disk_number']        = unpack("v", fread($fh, 2)); // number of the disk with the start of the central directory
				$eodir['total_entries_this'] = unpack("v", fread($fh, 2)); // total number of entries in the central dir on this disk
				$eodir['total_entries']      = unpack("v", fread($fh, 2)); // total number of entries in
				$eodir['size_of_cd']         = unpack("V", fread($fh, 4)); // size of the central directory
				$eodir['offset_start_cd']    = unpack("V", fread($fh, 4)); // offset of start of central directory with respect to the starting disk number
				$zipFileCommentLenght        = unpack("v", fread($fh, 2)); // zipfile comment length
				$eodir['zipfile_comment']    = $zipFileCommentLenght[1]?fread($fh, $zipFileCommentLenght[1]):''; // zipfile comment
				$this->endOfCentral = Array(
					'disk_number_this'=>$eodir['disk_number_this'][1],
					'disk_number'=>$eodir['disk_number'][1],
					'total_entries_this'=>$eodir['total_entries_this'][1],
					'total_entries'=>$eodir['total_entries'][1],
					'size_of_cd'=>$eodir['size_of_cd'][1],
					'offset_start_cd'=>$eodir['offset_start_cd'][1],
					'zipfile_comment'=>$eodir['zipfile_comment'],
				);
				
				// Then, load file list
				fseek($fh, $this->endOfCentral['offset_start_cd']);
				$signature = fread($fh, 4);
				
				while($signature == $this->dirSignature){
					$dir['version_madeby']      = unpack("v", fread($fh, 2)); // version made by
					$dir['version_needed']      = unpack("v", fread($fh, 2)); // version needed to extract
					$dir['general_bit_flag']    = unpack("v", fread($fh, 2)); // general purpose bit flag
					$dir['compression_method']  = unpack("v", fread($fh, 2)); // compression method
					$dir['lastmod_time']        = unpack("v", fread($fh, 2)); // last mod file time
					$dir['lastmod_date']        = unpack("v", fread($fh, 2)); // last mod file date
					$dir['crc-32']              = fread($fh, 4);              // crc-32
					$dir['compressed_size']     = unpack("V", fread($fh, 4)); // compressed size
					$dir['uncompressed_size']   = unpack("V", fread($fh, 4)); // uncompressed size
					$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
					$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
					$fileCommentLength          = unpack("v", fread($fh, 2)); // file comment length
					$dir['disk_number_start']   = unpack("v", fread($fh, 2)); // disk number start
					$dir['internal_attributes'] = unpack("v", fread($fh, 2)); // internal file attributes-byte1
					$dir['external_attributes1']= unpack("v", fread($fh, 2)); // external file attributes-byte2
					$dir['external_attributes2']= unpack("v", fread($fh, 2)); // external file attributes
					$dir['relative_offset']     = unpack("V", fread($fh, 4)); // relative offset of local header
					$dir['file_name']           = fread($fh, $fileNameLength[1]);                             // filename
					$dir['extra_field']         = $extraFieldLength[1] ?fread($fh, $extraFieldLength[1]) :''; // extra field
					$dir['file_comment']        = $fileCommentLength[1]?fread($fh, $fileCommentLength[1]):''; // file comment			
					
					// Convert the date and time, from MS-DOS format to UNIX Timestamp
					$BINlastmod_date = str_pad(decbin($dir['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
					$BINlastmod_time = str_pad(decbin($dir['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
					$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
					$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
					$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
					$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
					$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
					$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));	
					
					$this->centralDirList[$dir['file_name']] = Array(
						'version_madeby'=>$dir['version_madeby'][1],
						'version_needed'=>$dir['version_needed'][1],
						'general_bit_flag'=>str_pad(decbin($dir['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
						'compression_method'=>$dir['compression_method'][1],
						'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
						'crc-32'            =>str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][0])), 2, '0', STR_PAD_LEFT),
						'compressed_size'=>$dir['compressed_size'][1],
						'uncompressed_size'=>$dir['uncompressed_size'][1],
						'disk_number_start'=>$dir['disk_number_start'][1],
						'internal_attributes'=>$dir['internal_attributes'][1],
						'external_attributes1'=>$dir['external_attributes1'][1],
						'external_attributes2'=>$dir['external_attributes2'][1],
						'relative_offset'=>$dir['relative_offset'][1],
						'file_name'=>$dir['file_name'],
						'extra_field'=>$dir['extra_field'],
						'file_comment'=>$dir['file_comment'],
					);
					$signature = fread($fh, 4);
				}
				
				// If loaded centralDirs, then try to identify the offsetPosition of the compressed data.
				if($this->centralDirList) foreach($this->centralDirList as $filename=>$details){
					$i = $this->_getFileHeaderInformation($fh, $details['relative_offset']);
					$this->compressedList[$filename]['file_name']          = $filename;
					$this->compressedList[$filename]['compression_method'] = $details['compression_method'];
					$this->compressedList[$filename]['version_needed']     = $details['version_needed'];
					$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
					$this->compressedList[$filename]['crc-32']             = $details['crc-32'];
					$this->compressedList[$filename]['compressed_size']    = $details['compressed_size'];
					$this->compressedList[$filename]['uncompressed_size']  = $details['uncompressed_size'];
					$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
					$this->compressedList[$filename]['extra_field']        = $i['extra_field'];
					$this->compressedList[$filename]['contents-startOffset']=$i['contents-startOffset'];
					if(strtolower($stopOnFile) == strtolower($filename))
						break;
				}
				return true;
			}
		}
		return false;
	}
	
	function _loadFileListBySignatures(&$fh, $stopOnFile=false){
		fseek($fh, 0);
		
		$return = false;
		for(;;){
			$details = $this->_getFileHeaderInformation($fh);
			if(!$details){
				$this->debugMsg(1, "Invalid signature. Trying to verify if is old style Data Descriptor...");
				fseek($fh, 12 - 4, SEEK_CUR); // 12: Data descriptor - 4: Signature (that will be read again)
				$details = $this->_getFileHeaderInformation($fh);
			}
			if(!$details){
				$this->debugMsg(1, "Still invalid signature. Probably reached the end of the file.");
				break;
			}
			$filename = $details['file_name'];
			$this->compressedList[$filename] = $details;
			$return = true;
			if(strtolower($stopOnFile) == strtolower($filename))
				break;
		}
		
		return $return;
	}
	
	function _getFileHeaderInformation(&$fh, $startOffset=false){
		if($startOffset !== false)
			fseek($fh, $startOffset);
		
		$signature = fread($fh, 4);
		if($signature == $this->zipSignature){
			# $this->debugMsg(1, "Zip Signature!");
			
			// Get information about the zipped file
			$file['version_needed']     = unpack("v", fread($fh, 2)); // version needed to extract
			$file['general_bit_flag']   = unpack("v", fread($fh, 2)); // general purpose bit flag
			$file['compression_method'] = unpack("v", fread($fh, 2)); // compression method
			$file['lastmod_time']       = unpack("v", fread($fh, 2)); // last mod file time
			$file['lastmod_date']       = unpack("v", fread($fh, 2)); // last mod file date
			$file['crc-32']             = fread($fh, 4);              // crc-32
			$file['compressed_size']    = unpack("V", fread($fh, 4)); // compressed size
			$file['uncompressed_size']  = unpack("V", fread($fh, 4)); // uncompressed size
			$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
			$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
			$file['file_name']          = fread($fh, $fileNameLength[1]); // filename
			$file['extra_field']        = $extraFieldLength[1]?fread($fh, $extraFieldLength[1]):''; // extra field
			$file['contents-startOffset']= ftell($fh);
			
			// Bypass the whole compressed contents, and look for the next file
			fseek($fh, $file['compressed_size'][1], SEEK_CUR);
			
			// Convert the date and time, from MS-DOS format to UNIX Timestamp
			$BINlastmod_date = str_pad(decbin($file['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
			$BINlastmod_time = str_pad(decbin($file['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
			$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
			$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
			$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
			$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
			$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
			$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));
			
			// Mount file table
			$i = Array(
				'file_name'         =>$file['file_name'],
				'compression_method'=>$file['compression_method'][1],
				'version_needed'    =>$file['version_needed'][1],
				'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
				'crc-32'            =>str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
				'compressed_size'   =>$file['compressed_size'][1],
				'uncompressed_size' =>$file['uncompressed_size'][1],
				'extra_field'       =>$file['extra_field'],
				'general_bit_flag'  =>str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
				'contents-startOffset'=>$file['contents-startOffset']
			);
			return $i;
		}
		return false;
	}
}