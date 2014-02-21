<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.dss.php                                        //
// module for analyzing Digital Speech Standard (DSS) files    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_dss extends getid3_handler
{

	public function Analyze() {
		$info = &$this->getid3->info;

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$DSSheader  = fread($this->getid3->fp, 1256);

		if (!preg_match('#^(\x02|\x03)ds[s2]#', $DSSheader)) {
			$info['error'][] = 'Expecting "[02-03] 64 73 [73|32]" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes(substr($DSSheader, 0, 4)).'"';
			return false;
		}

		// some structure information taken from http://cpansearch.perl.org/src/RGIBSON/Audio-DSS-0.02/lib/Audio/DSS.pm
		$info['encoding']              = 'ISO-8859-1'; // not certain, but assumed
		$info['dss'] = array();

		$info['fileformat']            = 'dss';
		$info['mime_type']             = 'audio/x-'.substr($DSSheader, 1, 3); // "audio/x-dss" or "audio/x-ds2"
		$info['audio']['dataformat']   =            substr($DSSheader, 1, 3); //         "dss" or         "ds2"
		$info['audio']['bitrate_mode'] = 'cbr';

		$info['dss']['version']       =                            ord(substr($DSSheader,   0,   1));
		$info['dss']['hardware']      =                           trim(substr($DSSheader,  12,  16)); // identification string for hardware used to create the file, e.g. "DPM 9600", "DS2400"
		$info['dss']['unknown1']      =   getid3_lib::LittleEndian2Int(substr($DSSheader,  28,   4));
		// 32-37 = "FE FF FE FF F7 FF" in all the sample files I've seen
		$info['dss']['date_create']   = $this->DSSdateStringToUnixDate(substr($DSSheader,  38,  12));
		$info['dss']['date_complete'] = $this->DSSdateStringToUnixDate(substr($DSSheader,  50,  12));
		$info['dss']['playtime_sec']  = intval((substr($DSSheader,  62, 2) * 3600) + (substr($DSSheader,  64, 2) * 60) + substr($DSSheader,  66, 2)); // approximate file playtime in HHMMSS
		$info['dss']['playtime_ms']   =   getid3_lib::LittleEndian2Int(substr($DSSheader, 512,   4)); // exact file playtime in milliseconds. Has also been observed at offset 530 in one sample file, with something else (unknown) at offset 512
		$info['dss']['priority']      =                            ord(substr($DSSheader, 793,   1));
		$info['dss']['comments']      =                           trim(substr($DSSheader, 798, 100));

		//$info['audio']['bits_per_sample']  = ?;
		//$info['audio']['sample_rate']      = ?;
		$info['audio']['channels']     = 1;

		$info['playtime_seconds'] = $info['dss']['playtime_ms'] / 1000;
		if (floor($info['dss']['playtime_ms'] / 1000) != $info['dss']['playtime_sec']) {
			// *should* just be playtime_ms / 1000 but at least one sample file has playtime_ms at offset 530 instead of offset 512, so safety check
			$info['playtime_seconds'] = $info['dss']['playtime_sec'];
			$this->getid3->warning('playtime_ms ('.number_format($info['dss']['playtime_ms'] / 1000, 3).') does not match playtime_sec ('.number_format($info['dss']['playtime_sec']).') - using playtime_sec value');
		}
		$info['audio']['bitrate'] = ($info['filesize'] * 8) / $info['playtime_seconds'];

		return true;
	}

	public function DSSdateStringToUnixDate($datestring) {
		$y = substr($datestring,  0, 2);
		$m = substr($datestring,  2, 2);
		$d = substr($datestring,  4, 2);
		$h = substr($datestring,  6, 2);
		$i = substr($datestring,  8, 2);
		$s = substr($datestring, 10, 2);
		$y += (($y < 95) ? 2000 : 1900);
		return mktime($h, $i, $s, $m, $d, $y);
	}

}
