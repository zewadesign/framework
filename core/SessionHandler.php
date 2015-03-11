<?php
// Can't really use this or even play with it until it's built out to
// function on it's own. db setup, etc.
// in general this is a strange class.
namespace core;

class SessionHandler
{
    private $database;
    private $sessionLifetime;
    private $lockTimeout;
    private $lockToIp;
    private $lockToUserAgent;
    private $tableName;
    private $sessionLock;

    function __construct(
        $interface,
        $security_code,
        $session_lifetime = '',
        $lock_to_user_agent = true,
        $lock_to_ip = false,
        $gc_probability = 1,
        $gc_divisor = 100,
        $table_name = 'Session',
        $lock_timeout = 60
    ) {

        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 0);

        if ($session_lifetime != '' && is_integer($session_lifetime)) {
            ini_set('session.gc_maxlifetime', (int) $session_lifetime);
        }


        if ($gc_probability != '' && is_integer($gc_probability)) {
            ini_set('session.gc_probability', $gc_probability);
        }


        if ($gc_divisor != '' && is_integer($gc_divisor)) {
            ini_set('session.gc_divisor', $gc_divisor);
        }


        $this->sessionLifetime = ini_get('session.gc_maxlifetime');
        $this->security_code = $security_code;
        $this->lockToUserAgent = $lock_to_user_agent;
        $this->lockToIp = $lock_to_ip;
        $this->tableName = $table_name;
        $this->lockTimeout = $lock_timeout;

        if($interface !== 'file') {

            try {
                $this->database = Database::getInstance();

                session_set_save_handler(
                    array(&$this, 'open'),
                    array(&$this, 'close'),
                    array(&$this, 'read'),
                    array(&$this, 'write'),
                    array(&$this, 'destroy'),
                    array(&$this, 'gc')
                );

                session_start();
            } catch(\Exception $e) {

                echo "<PRE>";
                echo 'The Session table does not exist, we\'re going to try and do this for you! Please refresh.';
                echo "</PRE>";
                $sql = "CREATE TABLE `Session` ("
                    . "`id` varchar(32) NOT NULL DEFAULT '',"
                    . "`hash` varchar(32) NOT NULL DEFAULT '',"
                    . "`session_data` blob NOT NULL,"
                    . "`session_expire` int(11) NOT NULL DEFAULT '0',"
                    . "PRIMARY KEY (`id`)"
                    . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";
                $this->database->query($sql);

                exit;

            }

        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }

    }

    public function getActiveSessions()
    {

        $this->gc();
        $result = $this->database->select('COUNT(id) as count')
             ->table($this->tableName)
             ->fetch();

        return $result->count;

    }

    public function getSettings()
    {

        $gc_maxlifetime = ini_get('session.gc_maxlifetime');
        $gc_probability = ini_get('session.gc_probability');
        $gc_divisor = ini_get('session.gc_divisor');

        print_r(array(
            'session.gc_maxlifetime' => $gc_maxlifetime . ' seconds (' . round($gc_maxlifetime / 60) . ' minutes)',
            'session.gc_probability' => $gc_probability,
            'session.gc_divisor'     => $gc_divisor,
            'probability'            => $gc_probability / $gc_divisor * 100 . '%'
        ));

    }

    public function regenerateId()
    {

        $old_session_id = session_id();
        session_regenerate_id();
        $this->destroy($old_session_id);

    }

    public function close()
    {

        $this->database->query('SELECT RELEASE_LOCK(?)', array($this->sessionLock));
        return true;

    }

    public function destroy($session_id)
    {
        $success = $this->database->where('id', $session_id)
              ->table($this->tableName)// totally looks like you'd be deleting a table. lol
              ->delete();

        if ($success) {
            return true;
        }

        return false;

    }

    public function gc()
    {

        $this->database->where('session_expire <', time())
               ->table($this->tableName)
               ->delete();

    }

    public function open($save_path, $session_name)
    {
        //??
        return true;

    }

    public function read($session_id)
    {

        $this->sessionLock = 'session_' . $session_id;

        $lock = $this->database->query('SELECT GET_LOCK(?, ?)', array($this->sessionLock, $this->lockTimeout));

        if (!$lock) {
            throw new \Exception('Session: Could not obtain session lock!');
        }

        $hash = '';

        // You repeated the next 6 lines in the SessionHandler->write() method. DRY
        if ($this->lockToUserAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $hash .= $_SERVER['HTTP_USER_AGENT'];
        }

        if ($this->lockToIp && isset($_SERVER['REMOTE_ADDR'])) {
            $hash .= $_SERVER['REMOTE_ADDR'];
        }

        $session = $this->database->select('session_data')
                    ->where(array(
                      'id'                => $session_id,
                      'session_expire > ' => time(),
                      'hash'              => md5($hash . $this->security_code)
                    ))
                    ->table($this->tableName)
                    ->limit(1)
                    ->fetch();

        if ($session) {
//change to not empty?
            return $session->session_data;
        }

        $this->regenerateId();

        return '';

    }

    public function write($session_id, $session_data)
    {

        $hash = '';

        if ($this->lockToUserAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $hash .= $_SERVER['HTTP_USER_AGENT'];
        }
        // This doesn't work if you're forwarding traffic thru proxies or load balancers.
        // and since you don't use cookies to validate sessions (and user agents are 100% spoofable)
        // then this would be an obscure but potentially painful oversight.
        if ($this->lockToIp && isset($_SERVER['REMOTE_ADDR'])) {
            $hash .= $_SERVER['REMOTE_ADDR'];
        }

        $hash = md5($hash . $this->security_code);

        $session = array(
            'id'             => $session_id,
            'hash'           => $hash,
            'session_data'   => $session_data,
            'session_expire' => time() + $this->sessionLifetime
        );

        $command = "ON DUPLICATE KEY UPDATE";
        $command .= " session_data = VALUES(session_data),";
        $command .= " session_expire = VALUES(session_expire)";

        $success = $this->database->table($this->tableName)
            ->insert($session, $command);


        if ($success) {
            return true;
        }

        return false;

    }
}
