<?php
namespace App\Controller;

use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;


#[IsGranted('DEMO')]
class AdminController extends AbstractController
{


    #[Route('/admin', methods: ['GET'])]
    public function showAdmin(): ?Response
    {


        $index = file_get_contents(N('folder.core') . '/admin/index.html');
        $admin = str_replace("=\"/", "=\"./admin/", $index);

        return $this->response($admin);

    }

    #[Route('/admin/user', methods: ['GET'])]
    public function showUser(): ?Response
    {
        if (!$this->currentUser->isGranted('ADMIN'))
            return null;

        $html = $this->currentUser->getUsername()
            . ' is '
            . $this->currentUser->getRole();
        return $this->response($html);
    }
    //logs
    #[Route('/admin/logs', methods: ['GET'])]
    public function listLogs(): ?Response
    {

        if (!$this->currentUser->isGranted('ADMIN'))
            return null;

        $filter = "";

        if ($this->currentUser->isGranted('SUPERADMIN')) {
            $filter = N('folder.project');
        }


        $files = glob(N('folder.logs') . "/$filter*.txt");

        $html = '';
        foreach ($files as $file) {

            $basename = basename($file);
            $date = date("Y-m-d H:i:s", filemtime($file));


            $html .= "<a href=\"./logs/$basename\">$basename</a> $date <br>\n";
        }
        return $this->response($html);

    }
    #[Route('/admin/logs/{file}', methods: ['GET'])]
    public function showLogs($file): ?Response
    {

        if (!$this->currentUser->isGranted('ADMIN'))
            return null;

        $project = N('folder.project');

        if (!$this->currentUser->isGranted('SUPERADMIN') && strpos($file, $project) !== 0) {
            return null;
        }


        $content = file_get_contents(N('folder.logs') . "/$file");

        $content = str_replace("\n", '<br>', $content);

        $html = "<h1> $project </h1> $content";

        return $this->response($html);

    }



    #[Route('/admin/media', methods: ['GET', 'POST'])]
    public function showMediaManager(): ?Response
    {

        if (!$this->currentUser->isGranted('ADMIN')) {
            echo "<h1>Truly your forgiveness I implore, but the current user is not granted access to the Asset Manager.</h1>";
            exit();
        }

        $this->loadIFM();
        return $this->response('');

    }

    #[Route('/admin/static/{asset*}', methods: ['GET'])]
    public function adminAssetManager($asset = '/'): ?Response
    {



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

        return null;

    }


    private function loadIFM()
    {

        $root_public_url = N('absroot') . '/media';
        if (!is_dir(N('folder.public') . '/media'))
            die('No media folder for this project');

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