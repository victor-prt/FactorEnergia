<?php

namespace app\models;

use yii\base\BaseObject;
use yii\web\IdentityInterface;

/**
 * Identidad mínima (la prueba no requiere login; el endpoint API es público de ejemplo).
 */
class User extends BaseObject implements IdentityInterface
{
    public $id;

    public static function findIdentity($id)
    {
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->id ?? '';
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}
