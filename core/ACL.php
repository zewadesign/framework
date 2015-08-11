<?php
namespace core;
use app\modules as modules;

class ACL
{

    /**
     * System configuration
     *
     * @var object
     */
    private $configuration;

    /**
     * Database object reference
     *
     * @access private
     * @var object
     */

    private $dbh;

    /**
     * Cache object reference
     *
     * @access private
     * @var mixed
     */

    private $cache = false;

    /**
     * Requesting user id
     *
     * @var int
     */

    private $userId = false;

    /**
     * Requesting role id
     *
     * @var int
     */

    private $roleId = false;

    /**
     * Module being requested
     *
     * @var string
     */

    private $module;

    /**
     * Controller being requested
     *
     * @var string
     */

    private $controller;

    /**
     * Method being requested
     *
     * @var string
     */

    private $method;

    /**
     * Load up some basic configuration settings.
     *
     * @access public
     *
     * @param boolean|int $userId
     * @param boolean|int $roleId
     * @TODO: use PDO directly, decouple from database
     */

    private $returnQueryString = 'r';

    public function __construct($userId = false, $roleId = false)
    {
        $this->configuration = App::getConfiguration();
        $this->dbh = Database::getInstance()->fetchConnection();

        if ($this->configuration->cache) {
            $this->cache = Cache::getInstance();
        }

        if (!$userId) {
            $guest = array_search('guest', (array) $this->configuration->acl->roles);
            $this->roleId = $guest;
        } else {
            $this->roleId = $roleId;
            $this->userId = $userId;
        }

    }

    /**
     * Handles client request within  ACL
     *
     * @access private
     */
    public function secureStart(callable $initiateApp) {

        $authorizationCode = $this->hasAccessRights(
            $this->configuration->router->module,
            $this->configuration->router->controller,
            $this->configuration->router->method
        );

        switch ($authorizationCode) {
            case '1':
                $initiateApp();
                break;
            case '2':
                $this->secureRedirect();
                break;
            case '3': //@TODO: setup module 404's.
                $this->output = $this->noAccessRedirect();
                break;
        }
    }


    /**
     * Set 401 header, provide no access view if authenticated
     * and access is insufficient / protected
     *
     * @access private
     */
    private function noAccessRedirect()
    {

        return Router::showNoAccess(['errorMessage' => 'No access']);

    }

    /**
     * Redirect if guest and access is insufficient / protected
     *
     * @access private
     */
    private function secureRedirect()
    {

        //@TODO:: add flash message to login?
        $currentURL = $this->configuration->router->currentURL;
        $baseURL = $this->configuration->router->baseURL;
        $aclRedirect = $this->configuration->acl->redirect;

        $redirect = base64_encode(str_replace($baseURL, '', $currentURL));

        $authenticationURL = $baseURL . '/';
        $authenticationURL .= $aclRedirect . '?' . $this->returnQueryString . '=' . $redirect;

        $this->redirect($authenticationURL);

    }

    private function redirect($url)
    {

        $url = str_replace(array('\r', '\n', '%0d', '%0a'), '', $url);

        if (headers_sent()) {
            return false;
        }

        // trap session vars before redirect
        session_write_close();

        header('HTTP/1.1 401 Access Denied');
        header("Location: $url");
        exit;
    }

    /**
     * Check if client has permission for request
     *
     * @access public
     *
     * @param string $module
     * @param string $controller
     * @param string $method
     *
     * @return int 1 = permitted, 2 = please authenticate, 3 = no access
     */

    public function hasAccessRights($module, $controller, $method)
    {

        $this->module = $module;
        $this->controller = $controller;
        $this->method = $method;

        if (!$this->userId) {
            return $this->hasGuestAccessRights();
        }

        $access = false;
        try {
            $query = "SELECT UserRole.user_id, UserRole.role_id FROM UserRole"
                . " LEFT JOIN RoleAccess ON UserRole.role_id = RoleAccess.role_id"
                . " WHERE UserRole.user_id = ? AND RoleAccess.role_id = ?"
                . " AND ( RoleAccess.role_module = ? OR RoleAccess.role_module = '%' )"
                . " AND ( RoleAccess.role_controller = ? OR RoleAccess.role_controller = '%' )"
                . " AND ( RoleAccess.role_method = ? OR RoleAccess.role_method = '%' )"
                . " LIMIT 1";
            $sth = $this->dbh->prepare($query);
            $sth->execute([$this->userId, $this->roleId, $module, $controller, $method]);
            $access = $sth->fetch(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

        if (!$access) {
            return 3;
        } //no access permitted

        return 1;

    }


    /**
     * Check if unauthenticated client is permitted for request
     *
     * @access private
     * @return int 1 = permitted, 2 = please authenticate
     */

    private function hasGuestAccessRights()
    {
        $access = false;
        try {
            $query = "SELECT RoleAccess.role_id FROM RoleAccess"
                . " WHERE RoleAccess.role_id = ?"
                . " AND ( RoleAccess.role_module = ? OR RoleAccess.role_module = ? )"
                . " AND ( RoleAccess.role_controller = ? OR RoleAccess.role_controller = ? )"
                . " AND ( RoleAccess.role_method = ? OR RoleAccess.role_method = ? )"
                . " LIMIT 1";

            $sth = $this->dbh->prepare($query);
            $sth->execute([$this->roleId, $this->module, '%', $this->controller, '%', $this->method, '%']);
            $access = $sth->fetch(\PDO::FETCH_OBJ);

        } catch (\PDOException $e) {
            $this->createACLTables();
        }

        if (!$access) {
            return 2;
        } // please login

        return 1; // you can view..

    }

    /**
     * Returns a reference of object once instantiated
     *
     * @access public
     * @return object
     */

    public static function &getInstance()
    {

        try {

            if (self::$instance === null) {
                throw new \Exception('Unable to get an instance of the load class. The class has not been instantiated yet.');
            }

            return self::$instance;

        } catch(\Exception $e) {

            echo 'Message' . $e->getMessage();

        }

    }

    private function createRoleTable()
    {
        try {
            $query = "CREATE TABLE `Role` ("
                . "`id` int(11) NOT NULL AUTO_INCREMENT,"
                . "`role_name` varchar(45) DEFAULT NULL,"
                . "PRIMARY KEY (`id`),"
                . "UNIQUE KEY `role_name_UNIQUE` (`role_name`)"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

            $this->dbh->prepare($query)->execute();
            foreach($this->configuration->acl->roles as $id => $roleName) {
                $role = ['role_name' => $roleName];

                $fields = array_keys($role);
                $arguments = array_values($role);
                $fieldCount = count($fields);
                $query = "INSERT INTO Role"
                    . " (" . implode(', ', $fields) . ") VALUES "
                    . " (" . rtrim(str_repeat('?,', $fieldCount), ',') . ")";

                $this->dbh->prepare($query)->execute($arguments);
            }
        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

    }

    private function createRoleAccessTable()
    {
        try {
            $query = "CREATE TABLE `RoleAccess` ("
                . "`id` int(11) NOT NULL AUTO_INCREMENT,"
                . "`role_id` int(11) DEFAULT NULL,"
                . "`role_module` varchar(65) DEFAULT NULL,"
                . "`role_controller` varchar(255) DEFAULT NULL,"
                . "`role_method` varchar(65) DEFAULT NULL,"
                . "PRIMARY KEY (`id`),"
                . "KEY `IXRole` (`role_controller`,`role_method`,`role_module`),"
                . "KEY `IXRoleID` (`role_id`),"
                . "CONSTRAINT `fk_RoleAccess_1` FOREIGN KEY (`role_id`) REFERENCES `Role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

            $this->dbh->prepare($query)->execute();

            $guestId = array_search('guest', (array) $this->configuration->acl->roles);

            $guestAccess = [
                'role_id' => $guestId,
                'role_module' => '%',
                'role_controller' => '%',
                'role_method' => '%'
            ];

            $fields = array_keys($guestAccess);
            $arguments = array_values($guestAccess);
            $fieldCount = count($fields);
            $query = "INSERT INTO RoleAccess"
                . " (" . implode(', ', $fields) . ") VALUES "
                . " (" . rtrim(str_repeat('?,', $fieldCount), ',') . ")";

            $this->dbh->prepare($query)->execute($arguments);

        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

    }

    private function createUserRoleTable()
    {
        try {
            $query = "CREATE TABLE `UserRole` ("
                . "`id` int(11) NOT NULL AUTO_INCREMENT,"
                . "`role_id` int(11) DEFAULT NULL,"
                . "`user_id` int(11) DEFAULT NULL,"
                . "PRIMARY KEY (`id`),"
                . "UNIQUE KEY `UX_UserID` (`user_id`),"
                . "KEY `IX_RoleID` (`role_id`),"
                . "CONSTRAINT `fkUserID1` FOREIGN KEY (`user_id`)"
                . " REFERENCES `" . $this->configuration->acl->userTable . "` (`" . $this->configuration->acl->userId . "`)"
                . " ON DELETE CASCADE ON UPDATE NO ACTION"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

            $this->dbh->prepare($query)->execute();

        } catch (\PDOException $e) {
            echo '<pre>', $e->getMessage(), '</pre>';
        }

    }

    private function createACLTables()
    {
        echo "<PRE>";
        echo 'The ACL tables do not exist, we\'re going to try and do this for you! Please refresh.';
        echo "</PRE>";

        $this->createRoleTable();
        $this->createRoleAccessTable();
        $this->createUserRoleTable();

        exit;
    }
}