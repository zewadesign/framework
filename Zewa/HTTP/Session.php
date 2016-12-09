<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Container;
use Zewa\Security;

final class Session extends SuperGlobal
{
    /**
     * @var array
     */
    private $flashdata = [];

    /**
     * @var string index for flash data
     */
    public $flashdataId = '__flash_data';

    public function __construct(Container $container, Security $security)
    {
        parent::__construct($container, $security);

        $this->flashManagement();
        $session = $_SESSION ?? [];
        $this->registerGlobal($session);
    }

    /**
     * Processes current requests flashdata, recycles old.
     * @access private
     */
    private function flashManagement()
    {
        $flashdata = $_SESSION[$this->flashdataId] ?? null;

        if ($flashdata !== null) {
            $flashdata = unserialize(base64_decode($flashdata));
            unset($_SESSION[$this->flashdataId]);
            if (!empty($flashdata)) {
                $this->flashdata = $flashdata;
                $this->incrementFlashStorage();
            }
        }
    }

    private function incrementFlashStorage()
    {
        foreach ($this->flashdata as $variable => $data) {
            if ($this->flashdata[$variable]['increment'] > 1) {
                unset($_SESSION[$variable], $this->flashdata[$variable]);
            } else {
                $this->flashdata[$variable]['value'] = $data['value'];
                $this->flashdata[$variable]['increment'] ++;
            }
        }

        if (!empty($this->flashdata)) {
            $_SESSION[$this->flashdataId] = base64_encode(serialize($this->flashdata));
        }
    }

    // Because sessions persist, we need to do a little more work here..
    // In addition, most superglobals are immuteable, whereas session is not
    public function set(string $key, $value)
    {
        $key = $this->security->normalize($key);
        $value = $this->security->normalize($value);
        parent::set($key, $value); // TODO: Change the autogenerated stub
        $_SESSION[$key] = $value;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setFlash($name, $value)
    {
        $current = $this->fetch($this->flashdataId);
        $append = base64_encode(serialize(['value' => $value, 'increment'   => 0]));
        array_push($current, [$name => $append]);

        $flash = $this->security->normalize($current);
        $_SESSION[$this->flashdataId] = $flash;
        $this->flashdata = $flash;
    }

    /**
     * @param string $key
     * @param null $default
     * @return array|null
     */
    public function getFlash(string $key, $default = null)
    {
//        print_r($this->flashdata);

        return $this->flashdata[$key]['value'] ?? $default;
    }

    /**
     * destroys a session and related cookies
     */
    public function destroy()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $time = time() - 42000;
            $path = $params['path'];
            $domain = $params['domain'];
            $secure = $params['secure'];
            $http = $params['httponly'];
            setcookie(session_name(), '', $time, $path, $domain, $secure, $http);
        }

        session_destroy();
    }
}
