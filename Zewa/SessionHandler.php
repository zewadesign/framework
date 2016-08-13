<?php
namespace Zewa;

class SessionHandler
{
    private $dbh;
    private $sessionLifetime;
    private $lockTimeout;
    private $lockToIp;
    private $lockToUserAgent;
    private $tableName;
    private $sessionLock;
    private $hash = "";
    private $domain = "";

    public function __construct(
        $interface,
        $security_code,
        $session_lifetime = '',
        $domain = '',
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
        ini_set('session.cookie_domain', $domain);

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

        $this->domain = $domain;

        $this->generateHash();
        $this->initializeHandler($interface);

    }

    private function generateHash()
    {
        $this->hash = '';

        // You repeated the next 6 lines in the SessionHandler->write() method. DRY
        if ($this->lockToUserAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->hash .= $_SERVER['HTTP_USER_AGENT'];
        }

        if ($this->lockToIp && isset($_SERVER['REMOTE_ADDR'])) {
            $this->hash .= $_SERVER['REMOTE_ADDR'];
        }
        $this->hash = md5($this->hash . $this->domain . $this->security_code);
    }

    private function initializeHandler($interface)
    {

        if ($interface !== 'file') {
            try {
                $database = new Database();//App::getService('database')->fetchConnection();
                $this->dbh = $database->fetchConnection('default');

                session_set_save_handler(
                    [&$this, 'open'],
                    [&$this, 'close'],
                    [&$this, 'read'],
                    [&$this, 'write'],
                    [&$this, 'destroy'],
                    [&$this, 'gc']
                    //                    ,[&$this, 'create_sid']
                );

                session_start();

            } catch (\PDOException $e) {
                echo "<strong>PDOException:</strong> <br/>";
                echo 'We can\'t find the Session table so we re-created it. Alright! Give it a refresh.';
                $this->createSessionTable();
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

        $sth = $this->dbh->prepare('COUNT(id) as count FROM' . $this->tableName);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_OBJ);

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

    //@TODO: implement.. can't figure out a way to make the sessionId fresh
    private function regenerateId()
    {
        $oldSessionId = session_id();
//        session_write_close();
        session_regenerate_id();
        $sessionId = session_id();

        $query = "UPDATE " . $this->tableName . " SET id = ? WHERE hash = ?";
        $this->dbh->prepare($query)->execute([$sessionId, $oldSessionId]);
        $this->destroy($oldSessionId);
    }

    public function close()
    {
        $lock = $this->dbh->prepare('SELECT RELEASE_LOCK(?)')
            ->execute([$this->sessionLock]);

        if (!$lock) {
            throw new Exception\StateException('Session: Could not release session lock!');
        }
        return true;

    }

    public function destroy($sessionId)
    {
        $success = false;
        $query = "DELETE FROM ". $this->tableName
            . " WHERE id = ?";

        $success = $this->dbh->prepare($query)
            ->execute([$sessionId]);

        if ($success) {
            return true;
        }

        return false;
    }

    public function gc()
    {

        $query = "DELETE FROM ". $this->tableName
            . " WHERE session_expire < ?";

        return $this->dbh->prepare($query)
            ->execute([time()]);
    }

    public function open($save_path, $session_name)
    {
        //??
        return true;

    }

    public function read($sessionId)
    {
        $this->sessionLock = 'session_' . $sessionId;

        $lock = $this->fetchSessionLock();
        $session = $this->fetchSessionData($sessionId);

        if ($session) {
            if ($session->session_regeneration >= 20) {
                //@TODO: implement for session hijack prevention, even though e'rythan ssl now
//                $this->regenerateId($sessionId);
            }
            return $session->session_data;
        }

        return '';

    }

    public function write($sessionId, $sessionData)
    {
        if ($this->insertSession($sessionId, $sessionData)) {
            return true;
        }

        return false;

    }

    private function insertSession($sessionId, $sessionData)
    {
        $success = false;

        try {
            $session = [
                'id'             => $sessionId,
                'hash'           => $this->hash,
                'session_data'   => $sessionData,
                'session_expire' => (time() + $this->sessionLifetime),
                'session_regeneration' => 0
            ];

            $fields = array_keys($session);
            $arguments = array_values($session);
            $fieldCount = count($fields);
            $query = "INSERT INTO " . $this->tableName
                . " (" . implode(', ', $fields) . ") VALUES "
                . " (" . rtrim(str_repeat('?,', $fieldCount), ',') . ")"
                . " ON DUPLICATE KEY UPDATE"
                . " session_data = VALUES(session_data),"
                . " session_expire = VALUES(session_expire),"
                . " session_regeneration = session_regeneration + 1";

            $success = $this->dbh->prepare($query)
                ->execute($arguments);
        } catch (\PDOException $e) {
            echo "<strong>PDOException:</strong> <br/>";

            echo $e->getMessage();
//            exit;
        }

        return $success;

    }

    private function fetchSessionLock()
    {
        $lock = $this->dbh->prepare('SELECT GET_LOCK(?, ?)')
            ->execute([
                $this->sessionLock,
                $this->lockTimeout
            ]);

        if (!$lock) {
            throw new Exception\StateException('Session: Could not obtain session lock!');
        }

        return $lock;
    }

    private function fetchSessionData($sessionId)
    {
        $query = "SELECT session_data, session_regeneration FROM " . $this->tableName
            . " WHERE id = ? AND session_expire > ? AND hash = ?"
            . " LIMIT 1";


        $arguments = [
            $sessionId,
            time(),
            $this->hash
        ];

        $sth = $this->dbh->prepare($query);

        $sth->execute($arguments);
        $result = $sth->fetch(\PDO::FETCH_OBJ);
        return $result;
    }

    private function createSessionTable()
    {
        echo "<PRE>";
        echo 'The Session table does not exist, we\'re going to try and do this for you! Please refresh.';
        echo "</PRE>";

        $query = "CREATE TABLE `Session` ("
            . "`id` varchar(32) NOT NULL DEFAULT '',"
            . "`hash` varchar(32) NOT NULL DEFAULT '',"
            . "`session_data` blob NOT NULL,"
            . "`session_expire` int(11) NOT NULL DEFAULT '0',"
            . "`session_regeneration` int(2) NOT NULL DEFAULT '0',"
            . "PRIMARY KEY (`id`)"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $this->dbh->prepare($query)
            ->execute();

    }
}
