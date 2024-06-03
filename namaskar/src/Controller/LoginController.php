<?php

namespace App\Controller;


use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Response;
use Qwwwest\Namaskar\QwwwickRenderer;
use Qwwwest\Namaskar\AbstractController;



class LoginController extends AbstractController
{

    private $mempad = null;


    #[Route('/{url*}', methods: ['GET'])]
    public function showLoginPage($url = '/'): ?Response
    {


        $zadmin = Kernel::service('ZenAdmin');
        $conf = Kernel::service('ZenConfig');

        $url = $conf('url');

        $users = $zadmin('users');
        $i = 0;
        $found = false;
        while (isset($users[$i])) {
            $user = $users[$i];

            $found = $user['url'] === $url && $user['password'] !== null;

            if ($found) {

                $_SESSION['token'] = bin2hex(random_bytes(32));

                $conf('page.uid', $i);
                $conf('page.token', $_SESSION['token']);

                if ($user['password'] === true) {
                    $conf('page.create', true);
                    $conf('page.title', "First Time");
                    $conf('page.message', "Enter your username and choose your password carefully.");
                    wlog('admin', "show create password for $user[username]");
                } else {
                    $conf('page.create', false);
                    $conf('page.title', "Welcome");
                    wlog('admin', "show login for $user[username]");
                }
                $qRenderer = new QwwwickRenderer($conf('folder.themes'));
                $html = $qRenderer->renderLoginPage();

                return new Response($html);
            }
            $i++;
        }


        return null;
    }

    #[Route('/{url*}', methods: ['POST'])]
    public function validateLoginPage($url = '/'): ?Response
    {

        $zadmin = Kernel::service('ZenAdmin');
        $conf = Kernel::service('ZenConfig');
        $currentUser = Kernel::service('CurrentUser');
        $url = $conf('url');
        $users = $zadmin('users');

        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
        $password2 = $_POST['password2'] ?? null;
        $token = $_POST['token'] ?? null;
        $uid = $_POST['uid'] ?? null;

        if ($_SESSION['token'] !== $token) {
            die('invalid token');
        }

        $qRenderer = new QwwwickRenderer($conf('folder.themes'));

        $_SESSION['token'] = bin2hex(random_bytes(32));

        if (isset($users[$uid])) {
            $user = $users[$uid];
            // 
            if ($user['password'] === null)
                return null;
            $conf('page.uid', $uid);
            $conf('page.token', $_SESSION['token']);
            if ($user['url'] !== $url || $user['username'] !== $username) {
                //die("Computer says no.");
                $conf('page.title', "Nope");

                wlog('error', "error $username for $url");
            } else
                if ($user['password'] === true) {
                    //save password
                    $currentUser->savePassword($uid, $username, $password, $password2);
                    $conf('page.title', "First Login");

                    $conf('page.message', "Your password has been saved. Please, login in.");
                    wlog('admin', "password was saved for $username");

                } else {
                    // login user if username and password are valid
                    if ($currentUser->login($uid, $username, $password)) {
                        header("Refresh:0; url=..");
                        die();
                    }

                    $conf('page.title', "Sorry");
                    $conf('page.message', "Computer says no.");
                }

            $html = $qRenderer->renderLoginPage();
            return new Response($html);
        }

        return null;
    }


}