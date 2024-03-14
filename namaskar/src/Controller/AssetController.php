<?php

namespace App\Controller;

use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\Response;
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



    #[Route('/{domain?}/asset/{asset*}')]
    public function domainAssetManager($domain = null, $asset = null): ?Response
    {

        if ($asset === null || $asset === '')
            return null;

        // if domain is null, we use from global
        if (!$domain)
            $domain = $GLOBALS['mempad'];


        $zen = Kernel::service('ZenConfig');
        $theme = $zen('site.theme') ?? 'bootstrap5';
        return $this->_staticAssetLoader($theme, $asset);

    }

    #[Route('/asset/{asset*}')]
    public function singleAssetManager($asset = null): ?Response
    {

        if ($asset === null || $asset === '')
            return null;
        // if domain is null, we use from global

        $domain = $GLOBALS['mempad'];



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

        foreach (N('folder.themes') as $templateFolder) {

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




    }

}