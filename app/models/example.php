<?php

namespace app\models;
use \core as core;

Class Example extends core\Model
{

    public function processLoginByToken($token) {

        $user = $this->database->select('User.id as uid, client_id, email_address, firstname, lastname, unique_id, role_id, credit')
            ->where(array(
                'token' => $token
            ))->table('User')
            ->join('UserRole','UserRole.user_id = User.id')
            ->fetch();

        if($user) {

            $this->database->table('User')
                ->where('token', $token)
                ->update(array(
                    'token' => ''
                ));

            if($user->client_id != core\Registry::get('_license')->client_id) // && $user->role_id != $adminId)
                return false;
            return $user;
        }


        return false;

    }

//    public function deductCreditBalance($uid, $credit) {
//        $credit = str_replace(',','',$credit);
//        $params = [$uid];
//
//        $this->database->query('UPDATE User SET credit = credit - '.$credit.' WHERE id = ?', $params);
//        return true;
//
//    }

    public function setCreditBalance($uid, $credit) {

        $this->database->table('User')
            ->where('id', $uid)
            ->update(array(
                'credit' => $credit
            ));

        return true;

    }

    public function processLogin($email = false, $password = false) {

        if(
            $email &&
            $password &&
            filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            //@TODO: check system settings, determine WHERE the user is coming from

            $user = $this->database->select('User.id as uid, client_id, email_address, firstname, lastname, unique_id, role_id, credit')
                ->where(array(
                    'email_address' => $email,
                    'password' => sha1($password)
                ))->table('User')
                ->join('UserRole','UserRole.user_id = User.id')
                ->fetch();

            if($user) {

                $adminId = core\Registry::get('_loader')->config('core','acl')['adminId'];

                if($user->client_id != core\Registry::get('_license')->client_id && $user->role_id != $adminId)
                    return false;

                return $user;
            }

        }

        return false;

    }
    public function insertBatch($clientId, $users, $uniqueIds) {

        if($this->database->table('User')->insertBatch($users)) {



            $ids = $this->fetchIdsByUniqueIds($clientId, $uniqueIds);

            $userRoles = array();

            if(!empty($ids)) {
                foreach($ids as $id) {

                    $userRoles[] = array(
                        'user_id' => $id->id,
                        'role_id' => 4
                    );

                }


                if($this->database->table('UserRole')->insertBatch($userRoles)) {
                    return true;
                }

            } else {
                return false;
            }


        } else {

            return false;

        }
    }

    public function fetchIdsByUniqueIds($clientId, $uniqueIds) {

        return $this->database->select('User.id')
//            ->join('UserRole','UserRole.user_id = User.id')
            ->table('User')
            ->where('client_id', $clientId)
            ->whereIn('User.unique_id',$uniqueIds)
            ->fetch('result');
    }

    public function insert($user, $role) {

        $result = $this->database->table('User')
            ->insert($user);

        if($result) {

            $userId = $this->database->lastInsertId();

            $this->database->table('UserRole')
                ->insert(array(
                    'role_id' => $role,
                    'user_id' => $userId
                ));

            return $userId;

        }

        return false;

    }

    public function update($clientId, $userId, $user, $role = false) {

        $result = $this->database->table('User')
            ->where(array(
                'id' => $userId,
                'client_id' => $clientId
            ))
            ->update($user);

        if($result) {

            if($role) {
                $this->database->table('UserRole')
                    ->where('user_id', $userId)
                    ->update(array(
                        'role_id' => $role,
                    ));
            }

            return true;

        }

        return false;

    }


    public function activate($clientId, $userId) {

        $result = $this->database->table('User')
            ->where(array(
                'client_id' => $clientId,
                'id' => $userId
            ))->update(array(
                'status' => 'activated'
            ));

        if($result) {

            return true;

        }

        return false;

    }

    public function deactivate($clientId, $userId) {

        $result = $this->database->table('User')
            ->where(array(
                'client_id' => $clientId,
                'id' => $userId
            ))->update(array(
                'status' => 'deactivated'
            ));

        if($result) {

            return true;

        }

        return false;

    }
    public function delete($clientId, $userId) {

        return $this->database->where(array(
                'client_id' => $clientId,
                'id' => $userId
            ))->table('User')
            ->delete();

    }

    public function fetchByEmail($clientId, $email) {

        return $this->database->select('User.*, UserRole.role_id')
            ->join('UserRole','UserRole.user_id = User.id')
            ->table('User')
            ->where(array(
                'User.email_address' => $email,
                'client_id' => $clientId
            ))->fetch();

    }

    public function fetch($clientId, $userId) {

        return $this->database->select('User.*, UserRole.role_id')
            ->join('UserRole','UserRole.user_id = User.id')
            ->table('User')
            ->where(array(
                'User.id' => $userId,
                'client_id' => $clientId
            ))->fetch();

    }
    //@TODO: update to run $this->clientId
    public function updateShipping($clientId, $userId, $shippingAddress) {

        $result = $this->database->table('User')
            ->where(array(
                'id' => $userId,
                'client_id' => $clientId
            ))->update($shippingAddress);

        if($result) {

            return true;

        }

        return false;

    }

    public function fetchShippingAddress($userId) {

        $result = $this->database->select('shipping_address')
            ->table('User')
            ->where(array(
                'User.id' => $userId,
                'shipping_address !=' => 'NULL'
            ))->fetch();

        if($result) {
            return json_decode($result->shipping_address);
        }

        return false;

    }
    /*@TODO REMOVE
    public function fetchResult($clientId, $page) {

        $limit = 10;

        $offset = ($page - 1) * $limit;

        $result = $this->database->select('User.firstname, User.lastname, User.status, User.email_address, User.credit, User.phone, User.address, User.city, User.state, User.zip, User.shipping_address')
            ->table('User')
            ->where(array(
                'client_id' => $clientId
            ))->orderBy('id','desc')
            ->limit($limit, ($offset == 0 ? 0 : $offset))
            ->fetch('result');

        return $result;


    }
    */
}