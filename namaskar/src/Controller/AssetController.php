<?php

namespace App\Controller;

use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\PageDataBuilder;
use App\Entity\PageEntity;
use Qwwwest\Namaskar\Kernel;



class AssetController extends AbstractController
{


    #[Route('/sites/{domain}/asset/{asset*}')]
    public function assetManager($domain, $asset = '/'): ?Response
    {

        $domain = basename($domain);
        $pageEntity = new PageEntity();

        if (!$pageEntity->init($domain)) {
            die("blep $domain");

        }

        $zen = Kernel::service('ZenConfig');


        $theme = $zen('site.theme') ?? 'bootstrap5';

        return $this->_staticAssetLoader($theme, $asset);



    }

    #[Route('/{domain?}/aaaasset/{theme}/{asset*}')]
    public function aathemeAssetManager($domain, $theme, $asset = '/'): ?Response
    {



        // if domain is null, we use from global
        if (!$domain)
            $domain = $GLOBALS['mempad'];


        $page = new PageDataBuilder($domain);

        $zen = Kernel::service('ZenConfig');
        $folder = $zen('folder.templates') . '/' . $theme;
        if (!is_dir($folder))
            return null;

        //   $theme = $zen('site.theme') ?? 'bootstrap5';
        return $this->_staticAssetLoader($theme, $asset);

    }


    #[Route('/{domain?}/asset/{asset*}')]
    public function domainAssetManager($domain, $asset = '/'): ?Response
    {

        // if domain is null, we use from global
        if (!$domain)
            $domain = $GLOBALS['mempad'];


        $page = new PageDataBuilder($domain);

        $zen = Kernel::service('ZenConfig');
        $theme = $zen('site.theme') ?? 'bootstrap5';
        return $this->_staticAssetLoader($theme, $asset);

    }

    #[Route('/asset/{asset*}')]
    public function singleAssetManager($asset = '/'): ?Response
    {

        // if domain is null, we use from global
       
            $domain = $GLOBALS['mempad'];


        $page = new PageDataBuilder($domain);

        $zen = Kernel::service('ZenConfig');
        $theme = $zen('site.theme') ?? 'bootstrap5';
        return $this->_staticAssetLoader($theme, $asset);

    }

    private function _staticAssetLoader($theme, $rest): ?Response
    {

        $filenames = [];
 

        $filenames[] = "/$theme/asset/$rest";
        $filenames[] = "/bootstrap5/asset/$rest";
        $filenames[] = "/4all/asset/$rest";

        foreach (N('folder.templates') as $templateFolder) {

            foreach ($filenames as $filename) {
                $filepath = $templateFolder . $filename;
                if (is_file($filepath)) {
                    $response = new Response();
                    $response->file($filepath);
                    return $response;
                }

           
            }
        }
        return $this->response('asset not found' . $filename);
        //
        // foreach ($filenames as $filename) {
        //     if (is_file($filename)) {
        //         $response = new Response();
        //         $response->file($filename);
        //         return $response;
        //     }
        // }

      


    }


    // #[Routddde('/asset/dynamic.css', name: 'app_asset')]
    // public function index(Infini $ini): Response
    // {

    //     $response = new Response();
    //     $response->setStatusCode(200);
    //     // sets a HTTP response header to CSS
    //     $response->headers->set('Content-Type', 'text/css');

    //     $domain = $GLOBALS['mempad'];

    //     $dataFolder = $this->getParameter('kernel.project_dir') . "/var/sites/$domain";

    //     $theme = file_get_contents("$dataFolder/theme.ini");


    //     $theme = $ini->parseString($theme);
    //     $siteall = $theme;

    //     return $this->render('asset/index.css.twig', [
    //         'domain' => $domain,
    //         'theme' => $theme['theme'],

    //     ], $response);



    // }
}