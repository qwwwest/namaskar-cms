<?php

namespace Qwwwest\Namaskar;

use App\Entity\UserEntity;
use Qwwwest\Namaskar\Router;

class Kernel
{

    private $zconf;
    private $zadmin;

    private static $kernel = null;
    private $currentUser = null;
    private static $debug = '';
    private $url;
    private $public;

    private $salt;


    private $listeners = [];
    private $sorted = [];

    public function handle(): Response
    {

        if (self::$kernel)
            die('one instance only');

        self::$kernel = $this;

        // absroot is the folder in which we find the index.php
        // usefull in case of multisites in one domain
        $absroot = explode('/index.php', $_SERVER['SCRIPT_NAME'])[0];


        // projectDir that is the parent of the current File, 
        //that is the CMS source folder
        $projectDir = \dirname(__DIR__);




        // that is project (or domain) folder name at root level in sites 
        // sites/DOMAIN/public


        if (isset($GLOBALS['mempad']))
            $prj_folder = $GLOBALS['mempad'];
        else {

            if (
                preg_match(
                    '#.+?/data/sites/([^/]+)/public/index.php#',
                    $_SERVER['SCRIPT_FILENAME'],
                    $matches
                )
            )
                $prj_folder = $matches[1];
            else {
                $prj_folder = basename(dirname($_SERVER['SCRIPT_FILENAME']));

            }


        }


        if (strpos($_SERVER['SERVER_SOFTWARE'], 'PHP ') === 0)
            $absroot = '';

        $urlwithparams = $url = substr($_SERVER['REQUEST_URI'], strlen($absroot));

        $urlparams = '';
        if (strpos($url, '?') !== false)
            [$url, $urlparams] = explode('?', $url);


        $conf = new ZenConfig();
        $this->zconf = $conf;

        $isHttps = $_SERVER['HTTPS'] ?? false === 'on' || $_SERVER['SERVER_PORT'] == 443;
        // $conf('app.domain', $_SERVER['SERVER_NAME']);
        $conf('app.domain', $_SERVER['HTTP_HOST']);
        $conf('app.protocol', $isHttps ? "https" : "http");

        $canonical = ($isHttps ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        $conf('seo.canonical', $canonical);




        /*

        absroot & url :
        exemple 1:
        url http://localhost/nam/data/files/site1/public/path/to/page
        absroot = /nam/data/files/site1/public
        url = /path/to/page

        */

        $conf('absroot', rtrim($absroot, '/'));
        $conf('media', trim($absroot . '/media'));
        $conf('asset', trim($absroot . '/asset'));
        $conf('url', $url);
        $conf('urlwithparams', $urlwithparams);
        $conf('urlparam', $urlparams);
        $conf('server', $_SERVER);
        $conf('request', $_REQUEST);
        $conf('post', $_POST);
        $conf('query', $_GET);
        $conf('cookies', $_COOKIE);
        $conf('files', $_FILES);

        $conf('folder.app', $projectDir); //  abc/namaskar

        $conf('folder.project', $prj_folder);

        $conf('folder.src', "$projectDir/src");
        $conf('folder.controllers', "$projectDir/src/Controller");
        $conf('folder.entities', "$projectDir/src/Entity");
        $conf('folder.core', __DIR__);

        // the folder where index.php is to be  found.
        $publicFolder = substr($_SERVER['SCRIPT_FILENAME'], 0, -10);
        $conf('folder.public', $publicFolder);

        $conf('folder.webroot', $_SERVER['DOCUMENT_ROOT']);
        $conf('folder.data', dirname("$projectDir/") . "/data");


        //    $conf('folder.sites', $conf('folder.data') . '/sites');
        $conf('folder.sites', dirname("$projectDir/") . "/sites");

        $conf('folder.site', $conf('folder.sites') . "/$prj_folder");
        $conf('folder.asset', $conf('folder.public') . "/asset");
        $conf('folder.media', $conf('folder.public') . "/media");

        $conf('folder.var', $conf('folder.data') . '');
        $conf('folder.logs', $conf('folder.var') . '/logs');
        $conf('folder.sitelogs', $conf('folder.logs') . "/$prj_folder");

        //wlog('Start', $urlwithparams);

        // template folders are in projectDir & possibly in site folder
        $conf('folder.themes', [$conf('folder.asset') . "/themes", $conf('folder.site') . "/themes", "$projectDir/themes"]);


        $sitesFolder = $conf('folder.sites'); // .../sites/
        $publicFolder = $conf('folder.public');
        $project = $conf('folder.project');

        $fullPaths = [

            "$sitesFolder/$project/*.lst",
            "$sitesFolder/$project/public/*.lst",

            "$publicFolder/*.lst",
            "$publicFolder/media/*.lst",
            "$publicFolder/media/_data/*.lst",


        ];

        foreach ($fullPaths as $fullPath) {

            $arr = $this->getMempadFile($fullPath);
            if (!$arr)
                continue;
            [$dataFolder, $mempadFile] = $arr;
            break;

        }

        if ($arr === null) {
            die('Kernel :: No dataFolder or mempad file found for: ' . $project);
        }


        $conf('mempadFile', "$dataFolder/$mempadFile");

        //die($conf('mempadFile'));

        // LOADING ADMIN...

        $this->zadmin = $z = new ZenConfig();
        $superFile = $conf('folder.data') . "/super.ini";
        if (!file_exists($superFile)) {
            $superFileContent = <<<plop
            [superadmin]
            env: "auto"

            [localhost]
            autologin: false
            debug: true

            [users[]]
            url: "/admin/login/secretadminslug"
            role: "SUPERADMIN"
            username: "super"
            password: null
            plop;
            file_put_contents($superFile, $superFileContent);
        }
        $z->addFile($superFile, false);
        //$z->addFile(substr($mempadFile, 0, -4) . '.ini', true);
        // $z->addFile(substr('_private', 0, -4) . '.ini', true);
        //$z->addFile(substr("$dataFolder/_private", 0, -4) . '.ini', true);
        $z->addFile("$dataFolder/_private.ini", true);

        $this->currentUser = new UserEntity();

        $conf('app.user', $this->currentUser);

        $mempadFilePath = "$dataFolder/$mempadFile";
        // die($mempadFilePath);
        if ($configIni = MemPad::getConfig($mempadFilePath)) {

            foreach ($configIni as $key => $ini) {
                $conf->parseString($ini);
            }


        } else {
            if (!$conf->addFile("$dataFolder/site.ini", true)) {
                // no ini file. we use minemalistic values.
                $conf->parseString("
[site]
name: '$project' 
domain: '$project' 
language: 'en'
theme: 'kotek'
auto.title: 'yes'
                  
                        ");
            }

            $conf->addFile("$dataFolder/theme.ini", true);
            $conf->addFile("$dataFolder/data.ini", true);
        }



        $router = new Router($conf('url'), $conf('folder.controllers'));

        $conf('routes', $router->getAllRoutes());
        $conf('matches', $router->matches());
        $conf('controllers', $router->getControllers());


        $response = $router->findRoute();
        $code = $response->getStatusCode();
        $urlwithparams = $conf('urlwithparams');
        if ($code === 404)
            wlog($code, $urlwithparams);

        if (
            $code === 200
            && strpos($urlwithparams, '/asset') !== 0
            && strpos($urlwithparams, '/admin') !== 0
            && strpos($urlwithparams, '/api') !== 0
        )
            wlog('page', $urlwithparams);

        return $response; // $router->findRoute()?? new Response('404 sigh...');
    }
    public static function getKernel()
    {
        return self::$kernel;
    }


    // public function zenConfig($val)
    // {
    //     $conf = $this->zconf;

    //     if ($val === null)
    //         return $this->zconf->parsed;

    //     return $conf($val);
    // }

    public static function getConf(): ZenConfig
    {
        return self::getKernel()->zconf;
    }

    public static function service($service)
    {
        if ($service === 'ZenConfig')
            return self::getKernel()->zconf;
        if ($service === 'ZenAdmin')
            return self::getKernel()->zadmin;
        if ($service === 'CurrentUser') {
            return self::getKernel()->currentUser;
        }
        //  return self::getKernel()->zconf;
        if ($service === 'FlashMessage')
            return self::getKernel()->zconf;
        if ($service === 'Logger')
            return self::getKernel()->zconf;

        die("Unknown service : $service");
    }

    public static function debug($key = null, $val = null)
    {

        static $debug = null;
        if ($debug === false)
            return;
        if ($debug === null) {
            if ($key === true)
                $debug = "Debugging Start\n";
            return;
        }

        if ($key !== null)
            $debug .= "$key: $val\n";
        else {

            $ext = pathinfo(N('url'), PATHINFO_EXTENSION);

            $time = round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
            $debug .= "render time: $time ms\n";
            if (
                $ext === 'css'
                || $ext === 'js'
            )
                return "\n/* $ext:\n" . $debug . '*/';
            else
                return "\n<!--" . $debug . '-->';
        }
    }


    public function getMempadFile($fullPath)
    {
        // Ensure the folder ends with a trailing slash
        //  $fullPath = rtrim($fullPath, '/') . '/';

        $matchingFiles = glob($fullPath);

        if (!$matchingFiles)
            return null;

        // get last matching file.
        // to match latest file with a date for example : _toto-2024-12-23.lst
        $pathFile = $matchingFiles[count($matchingFiles) - 1];

        $pos = strrpos($pathFile, '/');

        $file = substr($pathFile, $pos + 1);
        $path = substr($pathFile, 0, $pos);

        return [$path, $file];

    }


}