<?php
namespace core;

class SessionHandler
{
    private $dbh;
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

        $this->initializeHandler($interface);

    }

    private function initializeHandler($interface)
    {

        if($interface !== 'file') {

            try {

                $this->dbh = Database::getInstance()->fetchConnection();

                session_set_save_handler(
                    [&$this, 'open'],
                    [&$this, 'close'],
                    [&$this, 'read'],
                    [&$this, 'write'],
                    [&$this, 'destroy'],
                    [&$this, 'gc']
                );

                session_start();

            } catch(\Exception $e) {

                $this->createSessionTable();

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

        try {
            $sth = $this->dbh->prepare('SELECT RELEASE_LOCK(?)');
            $sth->execute([$this->sessionLock]);
            $lock = $sth->fetch(\PDO::FETCH_OBJ);
            $sth->closeCursor();
        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        if (!$lock) {
            throw new \Exception('Session: Could not release session lock!');
        }

        return true;

    }

    public function destroy($sessionId)
    {
        $success = false;
        try {
            $query = "DELETE FROM ". $this->tableName
                . "WHERE id < ?";

            $sth = $this->dbh->prepare($query);
            $success = $sth->execute([$sessionId]);
            $sth->closeCursor();
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        if ($success) {
            return true;
        }

        return false;
    }

    public function gc()
    {

        try {
            $query = "DELETE FROM ". $this->tableName
                . "WHERE session_expire < ?";

            $sth = $this->dbh->prepare($query);
            $result = $sth->execute([time()]);
            $sth->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

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
            return $session;
        }

        $this->regenerateId();

        return '';

    }

    public function write($sessionId, $sessionData)
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

        if ($this->insertSession($hash, $sessionId, $sessionData)) {
            return true;
        }

        return false;

    }

    private function insertSession($hash, $sessionId, $sessionData)
    {
        $success = false;

        try {
            $session = [
                'id'             => $sessionId,
                'hash'           => $hash,
                'session_data'   => $sessionData,
                'session_expire' => (time() + $this->sessionLifetime)
            ];

            $fields = array_keys($session);
            $arguments = array_values($session);
            $fieldCount = count($fields);
            $query = "INSERT INTO " . $this->tableName
                . " (" . implode(', ', $fields) . ") VALUES "
                . " (" . rtrim(str_repeat('?,', $fieldCount), ',') . ")"
                . " ON DUPLICATE KEY UPDATE"
                . " session_data = VALUES(session_data),"
                . " session_expire = VALUES(session_expire)";

            $sth = $this->dbh->prepare($query);
            $success = $sth->execute($arguments);
            $sth->closeCursor();

        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        return $success;

    }

    private function fetchSessionLock()
    {
        try {
            $sth = $this->dbh->prepare('SELECT GET_LOCK(?, ?)');
            $sth->execute([
                $this->sessionLock,
                $this->lockTimeout
            ]);
            $lock = $sth->fetch(\PDO::FETCH_OBJ);
            $sth->closeCursor();
        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        if (!$lock) {
            throw new \Exception('Session: Could not obtain session lock!');
        }

        return $lock;
    }

    private function fetchSessionData($sessionId)
    {

        $hash = '';

        // You repeated the next 6 lines in the SessionHandler->write() method. DRY
        if ($this->lockToUserAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $hash .= $_SERVER['HTTP_USER_AGENT'];
        }

        if ($this->lockToIp && isset($_SERVER['REMOTE_ADDR'])) {
            $hash .= $_SERVER['REMOTE_ADDR'];
        }

        try {
            $query = "SELECT session_data FROM " . $this->tableName
                . " WHERE id = ? AND session_expire > ? AND hash = ?"
                . " LIMIT 1";
            $sth = $this->dbh->prepare($query);
            $sth->execute([
                $sessionId,
                time(),
                md5($hash . $this->security_code)
            ]);
            $sessionData = $sth->fetch(\PDO::FETCH_OBJ);
            $sth->closeCursor();

            return $sessionData;
        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

    }

    private function createSessionTable()
    {
        echo "<PRE>";
        echo 'The Session table does not exist, we\'re going to try and do this for you! Please refresh.';
        echo "</PRE>";

        try {
            $query = "CREATE TABLE `Session` ("
                . "`id` varchar(32) NOT NULL DEFAULT '',"
                . "`hash` varchar(32) NOT NULL DEFAULT '',"
                . "`session_data` blob NOT NULL,"
                . "`session_expire` int(11) NOT NULL DEFAULT '0',"
                . "PRIMARY KEY (`id`)"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

            $sth = $this->dbh->prepare($query);
            $result = $sth->execute();
            $sth->closeCursor();
        } catch (PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        exit;
    }
}