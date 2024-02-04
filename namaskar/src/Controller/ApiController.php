<?php
namespace App\Controller;

use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\MemPad;


class ApiController extends AbstractController
{

    private const OK = 'ok';
    private const ERROR = 'error';


    private $m;


    private function isAdmin()
    {

        return isset($_SESSION['valid']);

    }

    private function isAuthed()
    {

        return isset($_SESSION['valid']);

    }

    #[Route('/api/login', methods: ['POST'])]
    public function login(): ?Response
    {

        sleep(1);

        $data = file_get_contents("php://input");
        $json = json_decode($data, true);
        $login = $json['glop'] ?? '';
        $pwd = $json['plop'] ?? '';

        $zen = Kernel::service('ZenConfig');
        $hash = '';

        $superini = $zen('folder.data') . '/super.ini';
        if (is_file($superini))
            $hash = file_get_contents($superini);




        $pwdOk = $hash && password_verify("$login::$pwd", $hash);


        if ($pwdOk) {

            $_SESSION['valid'] = true;
            $_SESSION['timeout'] = time();
            $_SESSION['login'] = $login;
            $_SESSION['role'] = 'SUPER_ADMIN';
            $_SESSION['readOnly'] = false;

            $_SESSION['currentUser'] = [
                'login' => $login,
                'role' => 'ROLE_SUPER_ADMIN',
                'domain' => '*',
            ];

            return $this->statusOK();

        }

        return $this->statusError('Wrong username or password');

    }

    #[Route('/api/save', methods: ['POST'])]
    public function save(): Response
    {


        if (!$this->isAdmin()) {
            return $this->statusError('User must be Admin');
        }


        $domain = $GLOBALS['mempad'];
        $this->m = new MemPad(N('mempadFile'), "", true);

        // if ($_SESSION['login'] === "demo") {
        //             echo json_encode(["status" => "error", "message" => "could not saved data"]);
        //             exit();
        //         }
        $data = file_get_contents("php://input");
        return $this->response($this->m->reactSortableTreeSave($data));
    }


    #[Route('/api/logout')]
    public function logout(): Response
    {

        session_destroy();
        $_SESSION = [];

        return $this->statusOK();

    }
    #[Route('/api/check')]
    public function check(): Response
    {
        return $this->statusOK();

    }


    #[Route('/api/tree')]
    public function tree(): Response
    {


        $domain = $GLOBALS['mempad'];


        if (!$this->isAuthed())
            return $this->statusError('no user');

        $this->m = new MemPad(N('mempadFile'), '', true);
        $response = new Response($this->m->getStructureAsJson());
        return $response;

    }



    #[Route('/api/page/{id?}')]
    public function page($id = 0): Response
    {


        $this->m = new MemPad(N('mempadFile'), '', true);

        $page = $this->m->getElementById($id) ?? (object) ['id' => -1];
        $page->content = $this->m->getContentById($id) ?? "";
        echo json_encode($page);
        exit();
    }


    #[Route('/api/{action*}')]
    public function default($action = null): ?Response
    {
        if ($action === null)
            return null;
        $user = ($this->isAuthed()) ? 'authed ' : 'no user ';
        $url = N('url');
        $message = "ERROR: $user $action $url  $_SERVER[REQUEST_METHOD]";
        return $this->statusError($message);

    }

    private function status($status, $message = null): Response
    {

        $content = [];
        $content['status'] = $status;
        if ($message)
            $content['message'] = $message;

        $response = new Response();
        return $response->json($content);
    }

    private function statusOK(): Response
    {
        $response = new Response();
        return $response->json(["status" => self::OK]);
    }

    private function statusError($message): Response
    {

        $response = new Response();
        return $response->json(["status" => self::ERROR, "message" => $message]);
    }

}