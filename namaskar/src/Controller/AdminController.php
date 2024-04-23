<?php
namespace App\Controller;

use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;

//#[IsGranted('ROLE_ADMIN')]


class AdminController extends AbstractController
{
    private $adminZone = null;
    private $isGranted = 'admin';
    private $superfile = '';


    public function __construct()
    {

        $this->superfile = N('folder.data') . '/super.ini';


    }




    #[Route('/admin', methods: ['GET'])]
    public function showAdmin(): ?Response
    {


        $initialize = "login\npassword";

        $superfile = N('folder.data') . '/super.ini';

        if (!is_file($superfile) || filesize($superfile) === 0) {
            file_put_contents($superfile, $initialize);
        }

        $superini = trim(file_get_contents($superfile));


        $superadmin = explode("\n", $superini);
        $initialize = explode("\n", $initialize);

        // file is not encoded. Let's do it
        if (count($superadmin) === 2) {


            $login = trim($superadmin[0]);
            $password = trim($superadmin[1]);

            if ($login === $initialize[0] || $password === $initialize[1]) {
                return $this->response("set the SuperAdmin account in data/super.ini");
            }

            // check login and password and encode them...


            if (strlen($login) < 3)
                die("login is too short");
            if (strlen($password) < 8)
                die("password is too short");


            $encoded = password_hash("$login::$password", PASSWORD_BCRYPT);
            file_put_contents($superfile, "$encoded");


        }

        // file is encoded.  
        return null;
        $index = file_get_contents(N('folder.core') . '/admin/index.html');
        $admin = str_replace("=\"/", "=\"./admin/", $index);

        return $this->response($admin);

    }


    #[Route('/admin/media', methods: ['GET', 'POST'])]
    public function showMediaManager(): ?Response
    {

        if (!isset($_SESSION['valid'])) {
            // $parent = "http" . (!empty($_SERVER['HTTPS']) ? "s" : "")
            //     . "://" . $_SERVER['SERVER_NAME']
            //     . substr($_SERVER['PHP_SELF'], 0, -10)
            //     . "/admin";
            // header("Location: $parent");
            // exit();
            return null; //
        }

        if ($_SESSION["readOnly"] ?? false) {
            echo "<h1>no File Manager in demo mode</h1>";
            exit();
        }
        ;



        $this->loadIFM();


        return $this->response('');

    }

    #[Route('/admin/static/{asset*}', methods: ['GET'])]
    public function adminAssetManager($asset = '/'): ?Response
    {

        // if domain is null, we use from global
        if (!$asset)
            $domain = $GLOBALS['mempad'];

        return $this->adminStaticAssetManager($asset);

    }

    private function adminStaticAssetManager($asset): ?Response
    {

        $filename = N('folder.core') . '/admin/static/' . $asset;

        if (is_file($filename)) {
            $response = new Response();
            $response->file($filename);
            return $response;
        }


        // return new Response('// not found: ' . $asset);

        return null;

    }


    private function loadIFM()
    {

        $root_public_url = N('absroot') . '/media';

        $ifmconfig = [
            // general config
            "auth" => 0,
            //   "auth_source" => 'inline;admin:$2y$10$0Bnm5L4wKFHRxJgNq.oZv.v7yXhkJZQvinJYR2p6X1zPvzyDRUVRC',
            "auth_ignore_basic" => 0,

            'root_dir' => 'media',
            "root_public_url" => '',  //. '/admin/media',
            "tmp_dir" => "",
            "timezone" => "",
            "forbiddenChars" => ['.php', '.exe'],
            "language" => "###vars:default_lang###",
            "selfoverwrite" => 0,
            "session_name" => false,

            // api controls
            "ajaxrequest" => 1,
            "chmod" => 0,
            "copymove" => 1,
            "createdir" => 1,
            "createfile" => 1,
            "edit" => 0,
            "delete" => 1,
            "download" => 1,
            "extract" => 1,
            "upload" => 1,
            "remoteupload" => 0,
            "remoteupload_disable_ssrf_check" => 0,     // security default
            "remoteupload_enable_follow_location" => 0, // security default
            "rename" => 1,
            "zipnload" => 1,
            "createarchive" => 1,
            "search" => 1,
            "paging" => 0,
            "pageLength" => 50,

            // gui controls
            "showlastmodified" => 1,
            "showfilesize" => 1,
            "showowner" => 0,
            "showgroup" => 0,
            "showpermissions" => 0,
            "showhtdocs" => 0,
            "showhiddenfiles" => 0,
            "showpath" => 1,
            "contextmenu" => 1,
            "disable_mime_detection" => 0,
            "showrefresh" => 1,
            "forceproxy" => 1,
            "confirmoverwrite" => 1
        ];

        include_once N('folder.core') . '/vendor/libifm.php';
        $ifm = new \IFM($ifmconfig);

        $ifm->run();
    }


}