<?php

namespace Qwwwest\Namaskar;

use App\Entity\UserEntity;
use Qwwwest\Namaskar\TemplateRenderer;
use Qwwwest\Namaskar\TemplateBuilder;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\ZenConfig;

class AbstractController
{
    protected $response;
    protected $conf;
    protected $currentUser;
    protected $pageBuilder = null;



    public function render($url)
    {
        $domain = $GLOBALS['mempad'];
        $domain = basename($domain);

        debug('page.type', N('page.type'));
        $theme = N('page.theme') ?? N('site.theme') ?? 'bootstrap5';
        $this->buildTemplates($theme);



        $this->pageBuilder = new PageDataBuilder($domain);
        $this->pageBuilder->renderMainContent($url);

        $t = new TemplateRenderer(N('folder.templates'));
        $html = $t->render($theme, $vars);

        if ($this->pageBuilder) {

            $html = $this->pageBuilder->renderShortcodes($html, $t);
        }
        debug('render-template:', $theme);

        return $this->response($html, $this->pageBuilder->codeStatus);
    }

    public function buildTemplates($theme)
    {
        $folders = [];
        foreach (N('folder.templates') as $key => $templateFolder) {

            $dirname = $templateFolder . "/$theme";
            if (strpos($theme, '/') !== false)
                $dirname = dirname($templateFolder . "/$theme");

            if (is_dir($dirname))
                $folders[] = $dirname;


        }


        TemplateBuilder::build($folders);

    }

    public function response($content, $code = 200, $headers = []): Response
    {
        return new Response($content, $code, $headers);
    }

    public function redirect($location): Response
    {
        $response = new Response();
        return $response->redirect($location);
    }

    public function json($content): Response
    {
        $response = new Response();
        return $response->json($content);
    }
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function forward($controller, $params = []): Response
    {
        [$className, $method] = explode('::', $controller);
        $fullClassName = 'App\\Controller\\' . $className;
        $obj = new $fullClassName();
        $response = $obj->{$method}(...$params);
        return $response;
    }
    public function isGranted($role): bool
    {
        $user = Kernel::service('CurrentUser');
        return $user && $user->isGranted($role);
    }
    public function addFlash($type, $message, $icon, $close): bool
    {
        $flashMessage = Kernel::service('FlashMessage');
        return $flashMessage->addFlash($type, $message);
    }

    public function getUser(): UserEntity
    {
        $response = new Response();
        return $response->redirect($location);
    }
}
