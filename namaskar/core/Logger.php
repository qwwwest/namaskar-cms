<?php

/**
 * PHP class to keep logs in CSV format.
 *
 * @author     Qwwwest.com
 * @link       https://github.com/qwwwest
 * @license    N/A
 */

class Logger
{

    /**
     * Name of the log file.
     * @access private
     */
    private $folder;
    private $ext;

    public const PAGE = 'page';
    public const PAGE404 = 'page404';
    public const ASSET404 = 'page404';

    function Logger($folder, $ext = ".log.txt")
    {
        $this->folder = $folder;
        $this->ext = $ext;


    }


    function page($message, $misc = null)
    {
        $this->log('page', $message, $misc);
    }
    function page404($message, $misc = null)
    {
        $this->log(2, $message, $misc);
    }
    function asset($message, $misc = null)
    {
        $this->log(2, $message, $misc);
    }
    function asset404($message, $misc = null)
    {
        $this->log(2, $message, $misc);
    }
    function api($message, $misc = null)
    {
        $this->log(2, $message, $misc);
    }
    function trace($message, $misc = null)
    {
        $this->log(0, $message, $misc);
    }
    function debug($message, $misc = null)
    {
        $this->log(1, $message, $misc);
    }
    function info($message, $misc = null)
    {
        $this->log(2, $message, $misc);
    }
    function warning($message, $misc = null)
    {
        $this->log(3, $message, $misc);
    }
    function error($message, $misc = null)
    {
        $this->log(4, $message, $misc);
    }
    function error404($message, $misc = null)
    {
        $this->log(404, $message, $misc);
    }
    function fatal($message, $misc = null)
    {
        $this->log(5, $message, $misc);
    }
    /**
     * Method for writing messages to the log file.
     * 
     * @param string $level Custom error level (info, debug, etc...)
     * @param string $message The message that will be written to the log file.
     */
    public function log($basename, $message, $misc)
    {


        $file = "$this->folder/$basename.$this->ext";
        $datetime = date("Y-m-d H:i:s");
        $message = preg_replace("/\s+/", " ", trim($message));

        if (!file_exists($file)) {

            file_put_contents($file, $message);
        } else {
            file_put_contents($file, PHP_EOL . $message, FILE_APPEND);
        }









    }


}