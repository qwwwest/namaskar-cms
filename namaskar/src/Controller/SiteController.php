<?php

namespace App\Controller;



use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\MemPad;
use Qwwwest\Namaskar\Kernel;



class SiteController extends AbstractController
{

    private $mempad = null;

    #[Route('/robot.txt')]
    public function showRobotTxt(): ?Response
    {
        $conf = $this->conf;

        $protocol = $conf('app.protocol');
        $domain = $conf('app.domain');

        $robotTxt = <<<TXT
        User-agent: *
        Allow: /
        
        Sitemap: $protocol://$domain/sitemap.xml
        TXT;

        return $this->response($robotTxt, 200);

    }
    #[Route('/sitemap.xml')]
    public function showSitemapXml(): ?Response
    {
        $conf = $this->conf;

        $protocol = $conf('app.protocol');
        $domain = $conf('app.domain');



        $mempadFile = $conf('mempadFile');
        $absroot = $conf('absroot');
        $mempad = new MemPad($mempadFile, '');

        $urls = '';
        foreach ($mempad->elts as $v) {

            if (
                strpos($v->url, '/.') === false
                && strpos($v->url, '.') !== 0
                && strpos($v->url, '/!') === false
                && strpos($v->url, '!') !== 0
            ) {

                $pattern = "/\nlastmod:[ ]+(\d\d\d\d-\d\d-\d\d)/";

                $lastmod = '';

                $rawContent = $mempad->getContentById($v->id);

                $success = preg_match($pattern, $rawContent, $match);
                if ($success) {


                    $lastmod = <<<MOD

                        <lastmod>$match[1]</lastmod>
                    
                    MOD;



                }



                $urls .= <<<URL
            <url>
                <loc>$protocol://$domain/$v->url</loc>$lastmod</url>

            URL;
            }

        }
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
         $urls
        </urlset> 
        XML;

        return $this->response($xml)->setContentType('xml');
    }
    #[Route('/{url*}')]
    public function showPage($url = '/'): ?Response
    {

        return $this->render($url);
    }


    #[Rouuuute('/sites')]
    public function ____list(): Response
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


        return $this->render('bootstrap5', $vars);


    }

    public function ____page404(): Response
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