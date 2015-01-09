<?
class dZip{
    var $filename;
    var $overwrite;
    
    var $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
    var $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
    var $dirSignatureE= "\x50\x4b\x05\x06"; // end of central dir signature
    var $files_count  = 0;
    var $fh;
    
    function dZip($filename, $overwrite=true){
        $this->filename  = $filename;
        $this->overwrite = $overwrite;
    }
    function addDir($dirname, $fileComments=''){
        if(substr($dirname, -1) != '/')
            $dirname .= '/';
        $this->addFile(false, $dirname, $fileComments);
    }
    function addFile($filename, $cfilename, $fileComments='', $data=false){
        if(!($fh = &$this->fh))
            $fh = fopen($this->filename, $this->overwrite?'wb':'a+b');
        
        // $filename can be a local file OR the data wich will be compressed
        if(substr($cfilename, -1)=='/'){
            $details['uncsize'] = 0;
            $data = '';
        }
        elseif(file_exists($filename)){
            $details['uncsize'] = filesize($filename);
            $data = file_get_contents($filename);
        }
        elseif($filename){
            echo "<b>Cannot add $filename. File not found</b><br>";
            return false;
        }
        else{
            $details['uncsize'] = strlen($filename);
            // DATA is given.. use it! :|
        }
        
        // if data to compress is too small, just store it
        if($details['uncsize'] < 256){
            $details['comsize'] = $details['uncsize'];
            $details['vneeded'] = 10;
            $details['cmethod'] = 0;
            $zdata = &$data;
        }
        else{ // otherwise, compress it
            $zdata = gzcompress($data);
            $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug (thanks to Eric Mueller)
            $details['comsize'] = strlen($zdata);
            $details['vneeded'] = 10;
            $details['cmethod'] = 8;
        }
        
        $details['bitflag'] = 0;
        $details['crc_32']  = crc32($data);
        
        // Convert date and time to DOS Format, and set then
        $lastmod_timeS  = str_pad(decbin(date('s')>=32?date('s')-32:date('s')), 5, '0', STR_PAD_LEFT);
        $lastmod_timeM  = str_pad(decbin(date('i')), 6, '0', STR_PAD_LEFT);
        $lastmod_timeH  = str_pad(decbin(date('H')), 5, '0', STR_PAD_LEFT);
        $lastmod_dateD  = str_pad(decbin(date('d')), 5, '0', STR_PAD_LEFT);
        $lastmod_dateM  = str_pad(decbin(date('m')), 4, '0', STR_PAD_LEFT);
        $lastmod_dateY  = str_pad(decbin(date('Y')-1980), 7, '0', STR_PAD_LEFT);
        
        # echo "ModTime: $lastmod_timeS-$lastmod_timeM-$lastmod_timeH (".date("s H H").")\n";
        # echo "ModDate: $lastmod_dateD-$lastmod_dateM-$lastmod_dateY (".date("d m Y").")\n";
        $details['modtime'] = bindec("$lastmod_timeH$lastmod_timeM$lastmod_timeS");
        $details['moddate'] = bindec("$lastmod_dateY$lastmod_dateM$lastmod_dateD");
        
        $details['offset'] = ftell($fh);
        fwrite($fh, $this->zipSignature);
        fwrite($fh, pack('s', $details['vneeded'])); // version_needed
        fwrite($fh, pack('s', $details['bitflag'])); // general_bit_flag
        fwrite($fh, pack('s', $details['cmethod'])); // compression_method
        fwrite($fh, pack('s', $details['modtime'])); // lastmod_time
        fwrite($fh, pack('s', $details['moddate'])); // lastmod_date
        fwrite($fh, pack('V', $details['crc_32']));  // crc-32
        fwrite($fh, pack('I', $details['comsize'])); // compressed_size
        fwrite($fh, pack('I', $details['uncsize'])); // uncompressed_size
        fwrite($fh, pack('s', strlen($cfilename)));   // file_name_length
        fwrite($fh, pack('s', 0));  // extra_field_length
        fwrite($fh, $cfilename);    // file_name
        // ignoring extra_field
        fwrite($fh, $zdata);
        
        // Append it to central dir
        $details['external_attributes']  = (substr($cfilename, -1)=='/'&&!$zdata)?16:32; // Directory or file name
        $details['comments']             = $fileComments;
        $this->appendCentralDir($cfilename, $details);
        $this->files_count++;
    }
    function setExtra($filename, $property, $value){
        $this->centraldirs[$filename][$property] = $value;
    }
    function save($zipComments=''){
        if(!($fh = &$this->fh))
            $fh = fopen($this->filename, $this->overwrite?'w':'a+');
        
        $cdrec = "";
        foreach($this->centraldirs as $filename=>$cd){
            $cdrec .= $this->dirSignature;
            $cdrec .= "\x0\x0";                  // version made by
            $cdrec .= pack('v', $cd['vneeded']); // version needed to extract
            $cdrec .= "\x0\x0";                  // general bit flag
            $cdrec .= pack('v', $cd['cmethod']); // compression method
            $cdrec .= pack('v', $cd['modtime']); // lastmod time
            $cdrec .= pack('v', $cd['moddate']); // lastmod date
            $cdrec .= pack('V', $cd['crc_32']);  // crc32
            $cdrec .= pack('V', $cd['comsize']); // compressed filesize
            $cdrec .= pack('V', $cd['uncsize']); // uncompressed filesize
            $cdrec .= pack('v', strlen($filename)); // file comment length
            $cdrec .= pack('v', 0);                // extra field length
            $cdrec .= pack('v', strlen($cd['comments'])); // file comment length
            $cdrec .= pack('v', 0); // disk number start
            $cdrec .= pack('v', 0); // internal file attributes
            $cdrec .= pack('V', $cd['external_attributes']); // internal file attributes
            $cdrec .= pack('V', $cd['offset']); // relative offset of local header
            $cdrec .= $filename;
            $cdrec .= $cd['comments'];
        }
        $before_cd = ftell($fh);
        fwrite($fh, $cdrec);
        
        // end of central dir
        fwrite($fh, $this->dirSignatureE);
        fwrite($fh, pack('v', 0)); // number of this disk
        fwrite($fh, pack('v', 0)); // number of the disk with the start of the central directory
        fwrite($fh, pack('v', $this->files_count)); // total # of entries "on this disk" 
        fwrite($fh, pack('v', $this->files_count)); // total # of entries overall 
        fwrite($fh, pack('V', strlen($cdrec)));     // size of central dir 
        fwrite($fh, pack('V', $before_cd));         // offset to start of central dir
        fwrite($fh, pack('v', strlen($zipComments))); // .zip file comment length
        fwrite($fh, $zipComments);
        
        fclose($fh);
    }
    
    // Private
    function appendCentralDir($filename,$properties){
        $this->centraldirs[$filename] = $properties;
    }
}
