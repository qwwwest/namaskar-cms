<?php
/*
 *  Model Class
 *  Creates a template/view object
 */
class Model
{
    private $folder;
    private $files;
    private $file = null;
    private $basename = null;
    private $extension = null;
    private $action = null;
    private $id = null;


    public function __construct()
    {


    }

    public function scan($folder, $extension)
    {
        if (!is_dir($folder))
            die('invalid folder not found ' . $folder);

        $this->folder = $folder;
        $this->extension = $extension;
        $this->files = [];
        foreach (glob("$folder/*.$extension") as $filename) {

            $this->files[] = basename($filename, ".$extension");
        }

        if (count($this->files) === 0)
            die(" no $extension file in $folder");


        return $this;
    }

    public function file($file)
    {
        if($file != basename($file, $this->extension))die('no extentions and no path');
        //$file =
        if (is_file("$this->folder/$file.$this->extension")) {
            // load the json data
            $jsonFile = new JsonFile("$dataFolder/$file.json");


            return $jsonFile;
        } else {

            $statusMessage = "File $file does not exist";
            $statusType = 'danger';
            die($statusMessage);
        }

    }

    /* __get() and __set() are run when writing data to inaccessible properties.
     * Get template variables
     */
    public function __get($key)
    {
        return $this->_variables[$key];
    }

    /*
     * Set template variables
     */
    public function __set($key, $value)
    {
        $this->_variables[$key] = $value;
    }

    /*
     * Render To html
     */


    public function __toString()
    {
        return $this->render();
    }
}