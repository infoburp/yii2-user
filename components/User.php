<?php

namespace infoburp\yii2\user\components;

use Yii;

/**
 * User component
 */
class User extends \yii\web\User
{
    /**
     * @inheritdoc
     */
    public $identityClass = 'infoburp\yii2\user\models\User';

    /**
     * @inheritdoc
     */
    public $enableAutoLogin = true;

    /**
     * @inheritdoc
     */
    public $loginUrl = ["/user/login"];

    /**
     * @inheritdoc
     */
    public function getIsGuest()
    {
        /** @var \app\modules\user\models\User $user */

        // check if user is banned. if so, log user out and redirect home
        // https://github.com/amnah/yii2-user/issues/99
        $user = $this->getIdentity();
        if ($user && $user->banned_at) {
            $this->logout();
            Yii::$app->getResponse()->redirect(['/'])->send();
        }

        return $user === null;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function getIsLoggedIn()
    {
        return !$this->getIsGuest();
    }

    /**
     * @inheritdoc
     */
    public function getPassedChallenge()
    {
        $user = $this->getIdentity();
        return !$user->challenge;
    }

    /**
     * @inheritdoc
     */
    public function afterLogin($identity, $cookieBased, $duration)
    {
        /** @var \app\modules\user\models\User $identity */
        $identity->updateLoginMeta();
        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * Get user's display name
     * @return string
     */
    public function getDisplayName()
    {
        /** @var \app\modules\user\models\User $user */
        $user = $this->getIdentity();
        return $user ? $user->getDisplayName() : "";
    }

    /**
     * Check if user can do $permissionName.
     * If "authManager" component is set, this will simply use the default functionality.
     * Otherwise, it will use our custom permission system
     * @param string $permissionName
     * @param array $params
     * @param bool $allowCaching
     * @return bool
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        // check for auth manager to call parent
        $auth = Yii::$app->getAuthManager();
        if ($auth) {
            return parent::can($permissionName, $params, $allowCaching);
        }

        // otherwise use our own custom permission (via the role table)
        /** @var \infoburp\yii2\user\models\User $user */
        $user = $this->getIdentity();
        return $user ? $user->can($permissionName) : false;
    }
}
