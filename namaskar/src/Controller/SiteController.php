<?php

namespace App\Controller;


use Qwwwest\Namaskar\PageDataBuilder;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;



class SiteController extends AbstractController
{

    private $mempad = null;


 

    #[Route('/{url*}')]
    public function showPage($url = '/'): ?Response
    {
/*
        if (strpos($url, 'root') === 0)
            return null;
        $domain = $GLOBALS['mempad'] ?? null;

        if (!$domain) {
            debug('domain not found', '');
            return null;
        }
        */
      
        return $this->render($url);
    }


    #[Route('/sites')]
    public function list(): Response
    {

        $folder = N('folder.data');
        $content = '';
        foreach (glob("$folder/sites/*") as $filename) {

            if (!is_dir($filename))
                continue;
            $basename = basename($filename);
            $absroot = N('absroot');

            $content .= "<a href='$absroot/sites/$basename'>$basename ggg    </a><br>";
        }

        $vars = [
            'navbar-expand' => 'lg',
            'navbar-container' => 'container-full',
            'main-container' => 'container',
            'navbar-brand' => 'Plop',
            'language' => 'fr',
            'navbarExpand' => 'md',
            'footer' => 'FOOTER',
            'aside' => false,

            'title' => 'Sites build with Namaskar',
            'content' => $content,
            'public' => N('absroot'),
        ];
        $this->buildTemplates('bootstrap5');
        return $this->render('bootstrap5', $vars);


    }

    public function page404(): Response
    {
        $folder = N('folder.data');
        $content = '';
        foreach (glob("$folder/sites/*") as $filename) {

            $basename = basename($filename);
            $content .= "<a href='sites/$basename'>$basename</a><br>";
        }

        $vars = [
            'navbar-expand' => 'lg',
            'navbar-container' => 'container-full',
            'main-container' => 'container',
            'navbar-brand' => 'Plop',
            'language' => 'fr',
            'navbarExpand' => 'md',
            'footer' => 'FOOTER',
            'aside' => 'MENU LEFT',

            'title' => 'HOMEPAGE',
            'content' => $content,
            'public' => N('public'),
        ];

        return $this->render('bootstrap5/index', $vars);

    }




}