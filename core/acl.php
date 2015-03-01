<?php

namespace core;

class Acl
{

    private $userId = false;
    private $roleId = false;
    private $module;
    private $controller;
    private $method;
    private $database;
    private $cache;

    public function __construct($userId = false, $roleId = false) {
        $this->database = Registry::get('_database');
        $this->cache = Registry::get('_memcached');

        if(!$userId) {

            $this->roleId = Registry::get('_loader')->config('core','acl')['guestId'];

        } else {

            $this->roleId = $roleId;
            $this->userId = $userId;

        }


    }

    public function hasAccessRights($module, $controller, $method) {

        $this->module = $module;
        $this->controller = $controller;
        $this->method = $method;

        if(!$this->userId)
            return $this->hasGuestAccessRights();


        $_cacheKey = 'hasAccessRights::'.$module.'::'.$controller.'::'.$method.'::'.$this->userId;

        if($result = $this->cache->get($_cacheKey)) {

            $access = $result;

        } else {

            $where = 'UserRole.user_id = ? AND RoleAccess.role_id = ?';
            $where .= ' AND ( RoleAccess.role_module = ? OR RoleAccess.role_module = ? ) ';
            $where .= ' AND ( RoleAccess.role_controller = ? OR RoleAccess.role_controller = ? ) ';
            $where .= ' AND ( RoleAccess.role_method = ? OR RoleAccess.role_method = ? ) ';

            $access = $this->database->select('UserRole.user_id, UserRole.role_id')
                ->table('UserRole')
                ->join('RoleAccess','UserRole.role_id = RoleAccess.role_id')
                ->where($where, array(
                    'userid' => $this->userId,
                    'roleid' => $this->roleId,
                    'module' => $module,
                    'ormodule' => '%',
                    'controller' => $controller,
                    'orcontroller' => '%',
                    'method' => $method,
                    'ormethod' => '%',
                ))
                ->limit(1)
                ->fetch();

            $this->cache->set($_cacheKey, $access, time() + 300);
        }


        if(!$access)
            return 3; //no access permitted

        return 1;

    }

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