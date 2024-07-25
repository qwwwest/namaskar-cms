<?php

function blep___loadClass($className)
{
    $fileName = '';
    $namespace = '';
    $s = DIRECTORY_SEPARATOR;
    $qns = 'Qwwwest\Namaskar';


    if (strpos($className, $qns) === 0) {

        $fileName = str_replace('\\', $s, substr($className, strlen($qns)));
        //$fileName = __DIR__ . $s . 'core' . $s . $fileName . '.php';
        $fileName = __DIR__ . $s . $fileName . '.php';
        if (file_exists($fileName)) {
            require $fileName;
        } else {
            echo 'Class "' . $fileName . '" does not exist in core.';
        }

        return;
    }


    // Sets the include path as the "src" directory
    $includePath = __DIR__ . $s . '..' . $s . 'src';

    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', $s, $namespace) . $s;
    }
    $fileName .= str_replace('_', $s, $className) . '.php';
    $fullFileName = $includePath . $s . $fileName;

    if (file_exists($fullFileName)) {
        require $fullFileName;
    } else {
        echo 'Class "' . $className . '" does not exist.';
    }
}

//spl_autoload_register('loadClass'); // Registers the autoloader


function N($val = null)
{
    $k = Qwwwest\Namaskar\Kernel::getKernel();
    return $k->zenConfig($val);
}

function debug($key = null, $val = null)
{
    return Qwwwest\Namaskar\Kernel::debug($key, $val);
}


function wlog($code, $message)
{
    // $logFolder = N('folder.sitelogs');
    //@mkdir($logFolder, 0755, true);

    //data/var/logs/qwwwest.com
    $baseName = N('folder.sitelogs');


    $filename = "{$baseName}.$code.txt";

    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d H:i:s');
    $time = round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3);

    $time = number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3);
    $method = $_SERVER['REQUEST_METHOD'];
    $message = "$date [$ip] $time $method $message\n";
    //  die("$filename");
    file_put_contents("$filename", $message, FILE_APPEND);
}

function dump($var, $name = '', $return = false)
{

    ini_set("highlight.comment", "#008000");
    ini_set("highlight.default", "#000000");
    ini_set("highlight.html", "#808080");
    ini_set("highlight.keyword", "#0000BB; font-weight: bold");
    ini_set("highlight.string", "#AA0000");

    $text = preg_replace("|^array *\((.*),\n\)$|s", '[$1]', var_export($var, 1));

    $text = preg_replace("| =>\s*\n\s*array \(|", " = [", $text);
    ;
    $text = preg_replace("|,(\n *)\),|", "\$1\n]", $text);

    // highlight_string() requires opening PHP tag or otherwise it will not colorize the text
    $text = highlight_string("<?php " . $text, true);
    $text = preg_replace_callback(
        '|<br />((&nbsp;)+)</span>|',
        function ($matches) {
            $tmp = '</span><br />' . preg_replace("|&nbsp;&nbsp;|", ".&nbsp;", $matches[1]);
            return $tmp;
        },
        $text
    );
    $text = preg_replace("|&lt;\?php |", "", $text, 1);

    $text = preg_replace('|=&gt;&nbsp;<br />&nbsp;&nbsp;array&nbsp;\(<br />|', " = [", $text, 1);

    $text = "<style> div.code {background-color:#eee}</style>\n<h2>DEBUG $name:</h2><div class='code'>$text</div>";
    if ($return)
        return $text;
    echo $text;
}

function dd($var, $name = '')
{

    dump($var, $name);
    die('');

}