<?php

namespace Qwwwest\Namaskar;

use App\Entity\UserEntity;
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


        $this->pageBuilder = new PageDataBuilder();

        $html = $this->pageBuilder->renderWholePage($url);

        $time = intval((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
        $html .= "<!-- $time ms -->";


        return $this->response($html, $this->pageBuilder->codeStatus);
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
