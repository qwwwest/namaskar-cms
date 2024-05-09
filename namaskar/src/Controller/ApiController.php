<?php
namespace App\Controller;

use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\AbstractController;
use Qwwwest\Namaskar\MemPad;

#[IsGranted('DEMO')]
class ApiController extends AbstractController
{

    private const OK = 'ok';
    private const ERROR = 'error';


    private $m;



    #[Roooooute('/api/login', methods: ['POST'])]
    public function login(): ?Response
    {

        return $this->statusError('nope');

        // not used anymore...
        // $data = file_get_contents("php://input");
        // $json = json_decode($data, true);
        // $login = $json['glop'] ?? '';
        // $pwd = $json['plop'] ?? '';
        // return $this->statusOK();

    }

    #[Route('/api/save', methods: ['POST'])]
    public function save(): Response
    {


        if (!$this->currentUser->isGranted('ADMIN')) {
            return $this->statusError('User must be Admin');
        }


        //$domain = $GLOBALS['mempad'];
        $this->m = new MemPad(N('mempadFile'), "", true);

        $data = file_get_contents("php://input");
        return $this->response($this->m->reactSortableTreeSave($data));
    }


    #[Route('/api/logout')]
    public function logout(): Response
    {

        $this->currentUser->logout();


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



        if (!$this->currentUser->isGranted('DEMO'))
            return $this->statusError('currentUser is not granted access');

        $this->m = new MemPad(N('mempadFile'), '', true);
        $response = new Response($this->m->getStructureAsJson());
        return $response;

    }



    #[Route('/api/page/{id?}')]
    public function page($id = 0): Response
    {


        if (!$this->currentUser->isGranted('DEMO'))
            return $this->statusError('currentUser is not granted access');

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
        $user = $this->currentUser->getRole();
        $url = N('url');
        $message = "ERROR: $user user $action $url  $_SERVER[REQUEST_METHOD]";
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