<?php

namespace Namaskar;

//Global variables and Global strategy for all websites using this code base

session_start();

require_once __DIR__ . "/../config.php";

spl_autoload_register(function ($class) {

    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }
    $file = __DIR__ . '/vendor/' . $class . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }

    $file = __DIR__ . '/' . str_replace('Namaskar\\', '', $class) . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }

    $file = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }
    die($file . " not found.'$class' Me so sorry.");

});


$url = $_GET['url'] ?? '';
$adminZone = $USER['adminPage'] ?? 'admin';

if ($url === $adminZone) {

    // we load the React.js Admin App
    $index = file_get_contents(__DIR__ . '/admin/index.html');
    echo str_replace("=\"/", "=\"./$adminZone/", $index);
    exit();
}

// we load the FileManager to handle files (upload, rename, delete...)
if ($url === "$adminZone/media") {
    session_start();
    if (!isset($_SESSION['valid'])) {
        $parent = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "")
            . "://" . $_SERVER['SERVER_NAME']
            . substr($_SERVER['PHP_SELF'], 0, -10)
            . "/$adminZone";
        header("Location: $parent");
        exit();
    }

    if ($_SESSION["readOnly"] ?? false) {
        echo "<h1>no File Manager in demo mode</h1>";
        exit();
    }
    ;

    include_once __DIR__ . "/vendor/cdn.libifm.php";
    $ifm = new \IFM([
        'root_dir' => MEDIA_FOLDER,
        "root_public_url" => "",
        "forbiddenChars" => array('.php'),
        "auth" => 0,

        "extract" => 0,
        "search" => 0,
        "download" => 1,
        "chmod" => 0,
        "edit" => 1,
        "zipnload" => 1,
        "createarchive" => 1,
        "showlastmodified" => 1,
        "showfilesize" => 1,
        "showowner" => 0,
        "showgroup" => 0,
        "showpermissions" => 0,
        "showhiddenfiles" => 0,
        "remoteupload" => 0,
        "showpath" => 1,
    ]);
    $ifm->run();
    exit();
}

// Assets CSS & JS assets needed for the ReactJS Admin App
if (strpos($url, "$adminZone") === 0) {
    $file = substr($url, strlen("$adminZone"));
    $content = file_get_contents(__DIR__ . "/admin/$file");
    if (substr($url, -4) === '.css') {
        header('content-type: text/css');
    }

    if (substr($url, -3) === '.js') {
        header('content-type: text/js');
    }

    echo $content;
    exit();
}

// for themes Assets CSS & JS assets outside of root (and global to all sites)
//   $folders[] = __DIR__ . '/data/themes/' . $theme;
if (strpos($url, "assets/") === 0) {

    $file = substr($url, 6);
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    if ($ext === 'html') {
        die('loading template files is not allowed.');
    }
    if ($ext === 'php') {
        die('loading php files is not allowed.');
    }

    if (is_file(__DIR__ . "/../themes/$file")) {
        $content = file_get_contents(__DIR__ . "/../themes/$file");
    } else if (is_file(__DIR__ . "/../themes/default/$file")) {
        $content = file_get_contents(__DIR__ . "/../themes/default/$file");
    } else {
        $content = "Asset file not found in theme folders: $file";
    }

    if ($ext === 'js' || $ext === 'css') {
        header("content-type: text/$ext");
    }
    echo $content;
    exit();
}

$ext = substr($url, -4);
if ($ext === '.jpg' || $ext === '.png' || $ext === '.gif') {

    // Create a blank image and add some text
    $im = imagecreatetruecolor(800, 600);
    $text_color = imagecolorallocate($im, 255, 255, 255);
    imagestring($im, 5, 5, 100, $url . ' not found', $text_color);
    header('Content-Type: image/jpeg');
    // Output the image
    imagejpeg($im);
    imagedestroy($im);
    logger("404 for asset: $url");
    exit();
}

if (!constant("MEMPAD_FILE")) {
    logger('ERR: $mempad_File is not set');
    die('See Logs...');
}

//RENDER
// API for the react.JS Admin App
if ($url === 'api' || substr($url, 0, 4) === 'api/') {

    include_once "api.php";
    exit();
}

$n = new Renderer(MEMPAD_FILE);

if (defined("MEMPAD_CONF")) {
    $n->configPage(MEMPAD_CONF);
}

echo $n->renderPage($url);

logger("info: render time for $url="
    . round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000)
    . "ms");

function logger($message)
{
    file_put_contents(LOG_FILE, date("Y-n-j h:i:s") . " $message\n", FILE_APPEND);
}

function debug($var, $name = '')
{

    $fileinfo = 'no_file_info';
    $backtrace = debug_backtrace();
    if (!empty($backtrace[0]) && is_array($backtrace[0])) {
        $fileinfo = $backtrace[0]['file'] . ":" . $backtrace[0]['line'];
    }

    ini_set("highlight.comment", "#008000");
    ini_set("highlight.default", "#000000");
    ini_set("highlight.html", "#808080");
    ini_set("highlight.keyword", "#0000BB; font-weight: bold");
    ini_set("highlight.string", "#AA0000");

    $text = preg_replace("|^array *\((.*),\n\)$|s", '[$1]', var_export($var, 1));

    $text = preg_replace("| =>\s*\n\s*array \(|", " = [", $text);
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
    $text = preg_replace("|&lt;\?php&nbsp;|", "", $text, 1);
    $text = preg_replace('|=&gt;&nbsp;<br />&nbsp;&nbsp;array&nbsp;\(<br />|', " = [", $text, 1);

    $text = "<style> div.code {background-color:#eee}</style>\n<h2>DEBUG $name:(from $fileinfo)</h2><div class='code'>$text</div>";
    echo $text;
}
