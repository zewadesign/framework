<?php
// This model/class builds pieces of html forms and manipulates the db?
namespace app\models;
use \core as core;

/**
 * Fetches and sets user roles
 * @package app\models
 */

Class Role extends core\Model
{
    /**
     * Grab the modules from the database
     * @return string
     */
    public function fetchModules() {

        $string = '<option value="%">%</option>';

        foreach(glob(APP_PATH.'/modules/*', GLOB_ONLYDIR) as $directory) {
            $directory = basename($directory);
            $string .= '<option value="'.$directory.'">'.ucfirst(str_replace('_', ' ', $directory)).'</option>';
        }

        return $string;
    }

    /**
     * Grab controllers from the database
     * @param $module
     * @return string
     *
     * @TODO: make these return arrays, make controllers do string logic
     */
    public function fetchControllers($module) {

        $string = '<option value="%">%</option>';

        foreach(glob(APP_PATH.'/modules/'.$module.'/controllers/*.php') as $controller) {
            $controller = basename($controller, '.php');
            $string .= '<option value="'.$controller.'">'.ucfirst(str_replace('_', ' ', $controller)).'</option>';
        }

        return $string;
    }

    /**
     * Grab methods from the database
     * @param $module
     * @param $controller
     * @return string
     */
    public function fetchMethods($module, $controller) {

        $string = '<option value="%">%</option>';

        $class = 'app\\modules\\'.$module.'\\controllers\\'.ucfirst($controller);
        $reflection = new \ReflectionClass($class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach($methods as $method) {
            if(substr($method->name, 0, 2) !== '__' && $method->name !== 'getInstance')
                $string .= '<option value="'.$method->name.'">'.ucfirst(str_replace('_', ' ', $method->name)).'</option>';
        }

        return $string;

    }

    /**
     * Delete permission from ACL list
     * @param $permissionId
     * @return mixed
     */
    public function deletePermission($permissionId) {

        return $this->database->where('id',$permissionId)
            ->table('RoleAccess')->delete();

    }

    /**
     * Delete role from ACL list
     * @param $roleId
     * @return mixed
     */
    public function delete($roleId) {

        return $this->database->where('id',$roleId)
            ->table('Role')
            ->delete();

    }

    /**
     * Update role in ACL list
     * @param $roleId
     * @param $roleName
     * @return bool
     */
    public function update($roleId, $roleName) {

        $roleId = (int) $roleId;

        $role = array(
            'role_name' => $roleName
        );

        $result = $this->database->where('id',$roleId)
            ->table('Role')
            ->update($role);

        if($result) {

            return true;

        }

        return false;

    }

    /**
     * Update permission in ACL list
     * @param $permissionId
     * @param $roleModule
     * @param $roleController
     * @param $roleMethod
     * @return bool
     */
    public function updatePermission($permissionId, $roleModule, $roleController, $roleMethod) {

        $permission = array(
            'role_module' => $roleModule,
            'role_controller' => $roleController,
            'role_method' => $roleMethod
        );

        $result = $this->database->where('id',$permissionId)
            ->table('RoleAccess')
            ->update($permission);

        if($result) {

            return true;

        }

        return false;

    }

    /**
     * Add permission to the ACL list
     * @param $id
     * @param $roleModule
     * @param $roleController
     * @param $roleMethod
     * @return bool
     */
    public function insertPermissions($id, $roleModule, $roleController, $roleMethod) {

        $permission = array(
            'role_id' => $id,
            'role_module' => $roleModule,
            'role_controller' => $roleController,
            'role_method' => $roleMethod
        );

        $result = $this->database->table('RoleAccess')->insert($permission);

        if($result) {
            $permissionId = $this->database->lastInsertId();
            return $permissionId;
        }

        return false;

    }

    /**
     * insert role into ACL list
     * @param $roleName
     * @return bool
     */
    public function insert($roleName) {

        $result = $this->database->table('Role')
            ->insert(array(
                'role_name' => $roleName
            ));


        if($result) {

            $roleId = $this->database->lastInsertId();
            return $roleId;

        }

        return false;

    }

    /**
     * fetch permission total from ACL list
     * @param $roleId
     * @return mixed
     */
    public function fetchPermissionCount($roleId) {
        return $this->database->select('COUNT(id) as count')
            ->table('RoleAccess')
            ->where('role_id',$roleId)
            ->fetch();
    }

    /**
     * fetch permission from ACL list
     * @param $roleId
     * @param int $page
     * @return mixed
     */
    public function fetchPermissions($roleId, $page = 1) {

        $limit = 10;
        $roleId = (int) $roleId;
        $offset = (int) ($page - 1) * $limit;

        return $this->database->select('id, role_id, role_module, role_controller, role_method')
            ->table('RoleAccess')
            ->where('role_id',$roleId)
            ->limit($limit,$offset)
            ->fetch('result');

    }

    /**
     * fetch Roles from the ACL list
     * @return mixed
     */
    public function fetchRoleList() {

        return $this->database->select('id, role_name')
            ->table('Role')
            ->orderBy('id','asc')
            ->fetch();

    }

}