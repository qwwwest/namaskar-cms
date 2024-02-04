<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\Kernel;

/*
 *  Template Class
 *  Creates a template/view object
 */

class TemplateRenderer
{

    private $rendered;
    private $basename;
    private $tfolders = [];
    private $theme;
    private $config;
    private $isAuthed = false;
    private $variables = array();



    public function __construct($templateFolders)
    {

        $this->tfolders = $templateFolders;
        $this->variables = [];
        $this->config = Kernel::service('ZenConfig');
        $this->theme = 'bootstrap5';
    }



    /*
     * Render To html
     */
    public function render($template, &$vars = null)
    {

        $this->variables = &$vars;
        //extract($vars); //private $theme;
        $parts = explode('/', $template);

        $this->theme = $parts[0];
        // echo "theeeeeeeeeeeme" . $this->theme;
        $basename = $parts[1] ?? 'index.html';
        foreach ($this->tfolders as $tfolder) {

            $file = "$tfolder/$this->theme/$basename.php";

            if (!\file_exists($file))
                continue;

            ob_start();
            require($file);
            return $this->rendered = ob_get_clean();
        }
        die("file not found: $file");
    }

    /*
     * include php file in new context with only local variables
     */
    public function include($template, $vars = null)
    {
        static $depth = 0;
        $depth++;
        if ($depth > 20)
            die('too deeeeeep for me.'); // to avoid infinite recursivity

        extract($vars ?? $this->variables, EXTR_OVERWRITE); // extract our template variables locally.
        $notFound = '';
        $conf = $this->config;
        if (strpos($template, '/') !== false) {
            $parts = explode('/', $template);
            $theme = $parts[0];
            $basename = $parts[1];
        } else {
            $theme = $conf('site.theme');
            $basename = $template;
        }




        foreach ($this->tfolders as $tfolder) {

            $file = "$tfolder/$theme/$basename.php";

            if (!\file_exists($file)) {
                $notFound .= "<br>\n$file";
                continue;
            }


            require $file;
            $depth--;
            return;
        }
        die("Template $theme / $basename not found in TemplateRenderer: $notFound");


    }

    public function getValue($str, $ref = null)
    {
        $conf = $this->config;
        $conf($str);
        $parts = explode('.', $str);


        if ($ref) {
            $tmp = $ref;
            array_shift($parts);


            for ($i = 0; $i < count($parts); $i++) {

                if ($parts[$i] === '#') {

                    if (is_array($tmp))
                        return count($tmp);
                    else
                        return null;
                } else if (is_array($tmp))
                    $tmp = $tmp[$parts[$i]] ?? ("$str:  $parts[$i] is null");
                else if (is_object($tmp))
                    $tmp = $tmp->{$parts[$i]} ?? ("$str: $parts[$i] is null");
                else
                    $tmp = null;
            }
        } else {
            $tmp = $conf($str);
        }

        return $tmp;
    }
    public function isElementAccessible($elt)
    {
        if (!$elt)
            return false;
        if (strpos($elt->title, '.') === 0)
            return false;
        if (strpos($elt->title, '!') === 0)
            return $this->isAuthed;
        if (strpos($elt->path, '/!') !== false)
            return $this->isAuthed;
        if (strpos($elt->path, '!') === 0)
            return $this->isAuthed;

        return true;
    }
}
