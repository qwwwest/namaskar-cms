<?php
namespace App\Entity;

use Qwwwest\Namaskar\Kernel;

class UserEntity
{

    private $rights = [
        'R_MEMPAD' => 1,
        'W_MEMPAD' => 2,
        'R_MEDIAMANAGER' => 4,
        'W_MEDIAMANAGER' => 8,
        'EDITOR' => 16,
        'EDITOR_ASSETS' => 32,
        'ADMIN' => 64,
        'SUPERADMIN' => 128
    ];



    private $roles = [];

    private $currentUser;



    public function __construct()
    {

        $this->roles['ANONYMOUS'] = 0;
        $this->roles['MEMBER'] = ($b = 1);
        $this->roles['DEMO'] = ($b *= 2);
        $this->roles['ADMIN_READONLY'] = ($b *= 2);
        $this->roles['EDITOR'] = ($b *= 2);
        $this->roles['EDITOR_ASSETS'] = ($b *= 2);
        $this->roles['ADMIN'] = ($b *= 2);
        $this->roles['SUPERADMIN'] = ($b *= 2);
        $this->roles['LOCALHOST'] = ($b *= 2);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $zadmin = Kernel::service('ZenAdmin');
        $ip = $_SERVER['REMOTE_ADDR'];
        $isLocalhost = in_array($ip, ['127.0.0.1', '::1'], true);

        $localhostAutologin = $isLocalhost && $zadmin('localhost.autologin');



        if (isset($_SESSION['currentuser'])) {
            $this->currentUser = unserialize($_SESSION['currentuser']);
        } else if ($localhostAutologin) {
            $this->currentUser = ['username' => 'localhost', 'email' => null, 'role' => 'LOCALHOST'];
        } else {
            $this->currentUser = ['username' => null, 'email' => null, 'role' => 'ANONYMOUS'];
        }

    }


    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->currentUser['username'];
    }


    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->currentUser['role'];
    }



    private function isValidRole(string $role): bool
    {
        return (isset($this->roles[$role]));
    }

    public function isGranted(string $role): bool
    {
        return ($this->roles[$this->currentUser['role']] >= $this->roles[$role]);
    }

    //ex  isUser('ADMIN') isUser('ADMIN')
    public function hasUserRole(string $role): bool
    {

        if (!$this->isValidRole($role))
            die(__CLASS__ . '::' . __METHOD__ . ' unknown user role: ' . $role);

        if ($role === 'LOCALHOST')
            return Kernel::service('ZenAdmin')->parsed['localhost']['autologin'];

        return $this->currentUser['role'] === $role;

    }
    /**
     * @param string $login 
     * @param string $password 
     * @return bool
     */
    public function login(int $uid, string $username, string $password): bool
    {
        $zadmin = Kernel::service('ZenAdmin');
        $conf = Kernel::service('ZenConfig');
        $currentUser = Kernel::service('CurrentUser');
        $url = $conf('url');

        $users = $zadmin('users');
        $user = $users[$uid] ?? null;

        // demo password are not encoded.  ^^
        $isValidDemoUser = $user !== null && $user['url'] === $url && $user['username'] === $username && $user['role'] === 'DEMO' && $user['password'] === $password;

        if ($isValidDemoUser) {

            $this->currentUser = array('username' => $username, 'email' => null, 'role' => 'DEMO');

            $_SESSION['currentuser'] = serialize($this->currentUser);
            return true;

        }


        if ($user === null || $user['url'] !== $url || $user['username'] !== $username || strlen(trim($password)) < 8)
            return false;


        if (password_verify($password, $user['password'])) {

            $this->currentUser = array('username' => $username, 'email' => null, 'role' => $user['role']);

            $_SESSION['currentuser'] = serialize($this->currentUser);
            return true;

        }

        return false;
    }
    public function savePassword($uid, $username, $password, $password2): bool
    {
        $conf = Kernel::service('ZenConfig');
        $superIniFile = $conf('folder.data') . "/namaskar.ini";
        $mempadIniFile = substr($conf('mempadFile'), 0, -4) . '.ini';

        $content = file_get_contents($superIniFile);

        $users = explode("[users[]]", $content);

        $numUserInSuperIniFile = count($users) - 1;
        if ($uid < count($users) - 1) {
            // the user is in namaskar.ini
            $userStr = $users[$uid + 1];
            if (strpos($userStr, "username: \"$username\"") !== false) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $users[$uid + 1] = str_replace("password: true", "password: \"$hash\"", $userStr);
                file_put_contents($superIniFile, implode('[users[]]', $users));
                return true;
            }
            die('cannot save password.');
        } else {
            // the user is maybe in $mempadIniFile ini file
            $content = file_get_contents($mempadIniFile);
            $users = explode("[users[]]", $content);
            $uid = $uid - $numUserInSuperIniFile;
            $userStr = $users[$uid + 1] ?? null;

            if ($userStr && strpos($userStr, "username: \"$username\"") !== false) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $users[$uid + 1] = str_replace("password: true", "password: \"$hash\"", $userStr);
                file_put_contents($mempadIniFile, implode('[users[]]', $users));
                return true;
            }
            die('cannot save password.');

        }
        return false;
    }
    /**

     * @return self
     */
    public function logout(): void
    {
        $this->currentUser = array('username' => null, 'email' => null, 'role' => 'ANONYMOUS');
        session_destroy();
        $_SESSION = [];
    }

}