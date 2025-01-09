<?php

namespace App\Controller;

use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\Response;
use App\Entity\PageEntity;
use Qwwwest\Namaskar\Kernel;



class AssetController extends AbstractController
{




    #[Route('/asset/{asset*}')]
    public function singleAssetManager($asset = null): ?Response
    {

        if ($asset === null || $asset === '')
            return null;




        $zen = Kernel::service('ZenConfig');
        $theme = $zen('site.theme') ?? 'kotek';
        return $this->_staticAssetLoader($theme, $asset);

    }

    private function _staticAssetLoader($theme, $rest): ?Response
    {

        $filenames = [];


        $filenames[] = "/$theme/asset/$rest";
        $filenames[] = "/bootstrap5/asset/$rest";
        $filenames[] = "/4all/asset/$rest";

        if ($rest === 'styles.css') {
            $styles = '';
            //dd(N('folder.themes'));
            foreach (N('folder.themes') as $templateFolder) {

                $filepath = "$templateFolder/4all/asset/styles.css";

                if (is_file($filepath)) {
                    $content = file_get_contents($filepath);


                    $styles .= $content;
                }


            }



            $filepath = N('folder.media') . '/_data/styles.css';
            if (is_file($filepath))
                $styles .= file_get_contents($filepath);


            $response = new Response($styles);
            $response->setContentType('css');
            return $response;
        }

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
        return $this->response('AssetController: asset not found' . $filename);




    }

}