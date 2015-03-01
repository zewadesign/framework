<?php

namespace app\models;
use \core as core;

Class Role extends core\Model
{

    public function fetchModules() {

        $string = '<option value="%">%</option>';

        foreach(glob(APP_PATH.'/modules/*', GLOB_ONLYDIR) as $directory) {
            $directory = basename($directory);
            $string .= '<option value="'.$directory.'">'.ucfirst(str_replace('_', ' ', $directory)).'</option>';
        }

        return $string;
    }
    //@TODO: make these return arrays, make controllers do string logic
    public function fetchControllers($module) {

        $string = '<option value="%">%</option>';

        foreach(glob(APP_PATH.'/modules/'.$module.'/controllers/*.php') as $controller) {
            $controller = basename($controller, '.php');
            $string .= '<option value="'.$controller.'">'.ucfirst(str_replace('_', ' ', $controller)).'</option>';
        }

        return $string;
    }

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

    public function deletePermission($permissionId) {

        return $this->database->where('id',$permissionId)
            ->table('RoleAccess')->delete();

    }

    public function delete($roleId) {

        return $this->database->where('id',$roleId)
            ->table('Role')
            ->delete();

    }

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

    public function fetchPermissionCount($roleId) {
        return $this->database->select('COUNT(id) as count')
            ->table('RoleAccess')
            ->where('role_id',$roleId)
            ->fetch();
    }

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
    public function fetchRoleList() {

        return $this->database->select('id, role_name')
            ->table('Role')
            ->orderBy('id','asc')
            ->fetch();

    }

}