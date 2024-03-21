<?php

namespace Qwwwest\Namaskar;

use App\Entity\UserEntity;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\ZenConfig;

class AbstractController
{
    //protected $response;
    protected $conf;
    protected $currentUser;



    public function render($url)
    {
        $domain = $GLOBALS['mempad'];
        $domain = basename($domain);

        $this->conf = Kernel::service('ZenConfig');

        $pageModel = new PageModel();

        $pageModel->buildModel($url);
        $qRenderer = new QwwwickRenderer(($this->conf)('folder.themes'));

        $theme = N('page.theme') ?? N('site.theme') ?? 'bootstrap5';

        $html = $qRenderer->renderPage($theme);

        $time = intval((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
        $html .= "<!-- $time ms -->";


        return $this->response($html, $pageModel->codeStatus);
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
