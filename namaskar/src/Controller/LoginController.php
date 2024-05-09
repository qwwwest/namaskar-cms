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

            $found = $user['url'] === $url;
            if ($found) {

                if ($user['password'] === true) {
                    $conf('page.create', true);
                    $conf('page.title', "First Time");
                    $conf('page.uid', $i);
                    $conf('page.message', "To create your account, enter your username and chose your password carefully");
                } else {
                    $conf('page.create', false);
                    $conf('page.uid', $i);
                    $conf('page.title', "Welcome");
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
        $uid = $_POST['uid'] ?? null;


        $qRenderer = new QwwwickRenderer($conf('folder.themes'));
        if (isset($users[$uid])) {
            $user = $users[$uid];

            if ($user['url'] !== $url || $user['username'] !== $username) {
                //die("Computer says no.");
                $conf('page.title', "Nope");
                $conf('page.message', "Who do you think you are?");
            } else
                if ($user['password'] === true) {
                    //save password
                    $currentUser->savePassword($uid, $username, $password, $password2);
                    $conf('page.title', "First Login");
                    $conf('page.message', "Your password was saved. Please login for the first time");
                    //header("Refresh: 1");
                    //die('after savePassword was saved');
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