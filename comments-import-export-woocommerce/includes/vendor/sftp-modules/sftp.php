<?php

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/phpseclib/');

require_once('Net/SSH2.php');
require_once('Net/SFTP.php');
require_once('Crypt/RSA.php');

class class_wf_sftp_import_export {

    var $link = false;

    function connect($hostname, $username, $password, $port = 22) {
        $this->link = new Net_SFTP($hostname, $port);
        if (!$this->link->login($username, $password)) {
            return FALSE;
        }
        return TRUE;
    }

    function put_contents($file, $contents, $mode = false) {
        $ret = $this->link->put($file, $contents);

        $this->chmod($file, $mode);

        return false !== $ret;
    }

    function chmod($file, $mode = false, $recursive = false) {
        return $mode === false ? false : $this->link->chmod($mode, $file, $recursive);
    }

    function get_contents($file) {
        return $this->link->get($file);
    }

    function size($file) {
        $result = $this->link->stat($file);
        return $result['size'];
    }

    function get_contents_array($file) {
        $lines = preg_split('#(\r\n|\r|\n)#', $this->link->get($file), -1, PREG_SPLIT_DELIM_CAPTURE);
        $newLines = array();
        for ($i = 0; $i < count($lines); $i+= 2)
            $newLines[] = $lines[$i] . $lines[$i + 1];
        return $newLines;
    }
    
    function delete_file($file){
        return $this->link->delete($file);
    }
    
    function getErrors($when = '') {
        if (!empty($when) && $when == 'last') {
            return $this->link->getLastSFTPError();
        }
        return $this->link->getSFTPErrors();
    }
    
    function getLog(){
        return $this->link->getSFTPLog();
    }
    
    
    function nlist($dir = '.', $file_types = array(), $recursive = false){                
        $list = $this->link->nlist($dir, $recursive);
        if(empty($file_types)){
            return $list; //return all items if not specifying any file types
        }
        $collection = array();
        foreach ($list as $item => $value) {

            $item_pathinfo = pathinfo($dir . DIRECTORY_SEPARATOR . $value);

            $item_extension = isset($item_pathinfo['extension']) ? $item_pathinfo['extension'] : '';

            if (!empty($file_types) && !in_array($item_extension, $file_types)) {
                continue;
            }

            $collection[$item] = $value;
        }
        return $collection;
    }
    
    
    function rawlist($dir = '.', $file_types = array(), $recursive = false) {
        $list = $this->link->rawlist($dir, $recursive);
        if(empty($file_types)){
            return $list; //return all items if not specifying any file types
        }
        $collection = array();
        foreach ($list as $item => $value) {

            $item_pathinfo = pathinfo($dir . DIRECTORY_SEPARATOR . $item);

            $item_extension = isset($item_pathinfo['extension']) ? $item_pathinfo['extension'] : '';

            if (!empty($file_types) && !in_array($item_extension, $file_types)) {
                continue;
            }

            $collection[$item] = $value;
        }
        return $collection;
    }

}
