<?php
// This whole thing is based on a database schema and an interface
// to a schema that doesn't exist.
namespace core;

/**
 * This class handles the access control list for requests
 *
 * <code>
 *
 * $userId = $request['uid'];
 * $roleId = $request['roleId'];
 * $ACL = new Acl($userId, $roleId);
 * $authorizationCode = $ACL->hasAccessRights($this->module, $this->controller, $this->method);
 *
 * </code>
 *
 * @author Zechariah Walden<zech @ zewadesign.com>
 */

class Acl
{

    /**
     * System configuration
     *
     * @var object
     */
    private $_configuration;

    /**
     * Database object reference
     *
     * @access private
     * @var object
     */

    private $database;

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
     * @param boolean|int $userId
     * @param boolean|int $roleId
     */

    public function __construct($userId = false, $roleId = false) {

        $this->_configuration = Registry::get('_configuration');
        $this->database = Registry::get('_database');

        if($this->_configuration->cache) {

            $this->cache = Registry::get('_memcached');

        }

        if(!$userId) {
            $this->roleId = $this->_configuration->acl->roles->guest;
        } else {
            $this->roleId = $roleId;
            $this->userId = $userId;
        }

    }

    /**
     * Check if client has permission for request
     *
     * @access public
     * @param string $module
     * @param string $controller
     * @param string $method
     * @return int 1 = permitted, 2 = please authenticate, 3 = no access
     */

    public function hasAccessRights($module, $controller, $method) {

        $this->module = $module;
        $this->controller = $controller;
        $this->method = $method;

        if(!$this->userId)
            return $this->hasGuestAccessRights();


        $_cacheKey = 'hasAccessRights::'.$module.'::'.$controller.'::'.$method.'::'.$this->userId;

        if($this->cache && $result = $this->cache->get($_cacheKey)) {

            $access = $result;

        } else {

            $where = 'UserRole.user_id = ? AND RoleAccess.role_id = ?';
            $where .= ' AND ( RoleAccess.role_module = ? OR RoleAccess.role_module = ? ) ';
            $where .= ' AND ( RoleAccess.role_controller = ? OR RoleAccess.role_controller = ? ) ';
            $where .= ' AND ( RoleAccess.role_method = ? OR RoleAccess.role_method = ? ) ';

            $access = $this->database->select('UserRole.user_id, UserRole.role_id')->table('UserRole')
                ->join('RoleAccess','UserRole.role_id = RoleAccess.role_id')
                ->where($where,
                    [
                        'userid' => $this->userId, 'roleid' => $this->roleId,
                        'module' => $module, 'ormodule' => '%',
                        'controller' => $controller, 'orcontroller' => '%',
                        'method' => $method, 'ormethod' => '%'
                    ]
                )->limit(1)->fetch();

            if($this->cache) {

                $this->cache->set($_cacheKey, $access, time() + 300);

            }

        }


        if(!$access)
            return 3; //no access permitted

        return 1;

    }


    /**
     * Check if unauthenticated client is permitted for request
     *
     * @access private
     * @return int 1 = permitted, 2 = please authenticate
     */

    private function hasGuestAccessRights() {


        $where = 'RoleAccess.role_id = ?';
        $where .= ' AND ( RoleAccess.role_module = ? OR RoleAccess.role_module = ? ) ';
        $where .= ' AND ( RoleAccess.role_controller = ? OR RoleAccess.role_controller = ? ) ';
        $where .= ' AND ( RoleAccess.role_method = ? OR RoleAccess.role_method = ? ) ';

        $access = $this->database->select('RoleAccess.role_id')
            ->table('RoleAccess')
            ->where($where, array(
                'roleid' => $this->roleId,
                'module' => $this->module,
                'ormodule' => '%',
                'controller' => $this->controller,
                'orcontroller' => '%',
                'method' => $this->method,
                'ormethod' => '%',
            ))
            ->limit(1)
            ->fetch();

        if(!$access)
            return 2; // please login

        return 1; // you can view..

    }

}