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


function N($var, $val = null)
{
    $conf = Qwwwest\Namaskar\Kernel::getConf();
    $numArgs = func_num_args();
    if ($numArgs === 1)
        return $conf($var);
    elseif ($numArgs === 2)
        return $conf($var, $val);
    die(basename(__FILE__) . "::N numArgs=$numArgs");

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

function array2csv($fields)
{
    $buffer = fopen('php://temp', 'r+');
    fputcsv($buffer, $fields);
    rewind($buffer);
    $csv = fgets($buffer);
    fclose($buffer);
    return $csv;
}

function saveContactToCSV($data, $csvfile)
{

    $baseName = N('folder.sitelogs');


    $filePath = "{$baseName}.$csvfile.csv";
    // Ensure the data array has the required keys
    //$requiredFields = ['firstname', 'lastname', 'email', 'phone', 'city', 'country', 'message', 'date'];
    $requiredFields = array_keys($data);
    $values = array_values($data);
    // foreach ($requiredFields as $field) {
    //     if (!isset($data[$field])) {
    //         throw new Exception("Missing required field: $field");
    //     }
    // }

    // Open the CSV file for appending
    $fileExists = file_exists($filePath);


    $rest = '';
    if ($fileExists) {
        $content = file_get_contents($filePath);
        $raw = explode("\n", $content, 2);

        //dd($raw[1]);
        $rest = $raw[1] ?? '';


    }


    $file = fopen($filePath, 'w');

    if (!$file) {
        throw new Exception("Unable to open or create the file: $filePath");
    }

    //  write the headers
    fputcsv($file, $requiredFields);

    fputcsv($file, $values);
    fwrite($file, $rest);



    // Close the file
    fclose($file);

    return true;
}


function getUserIpAddress()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // IP from shared internet
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // IP passed from a proxy or load balancer
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        // Direct IP address
        return $_SERVER['REMOTE_ADDR'];
    }

    return 'UNKNOWN'; // Fallback if no IP found
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