<?php

namespace infoburp\yii2\user\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use infoburp\yii2\user\models\User;
/**
 * Default controller for User module
 */
class DefaultController extends Controller
{
    /**
     * @var \app\modules\user\Module
     * @inheritdoc
     */
    public $module;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'confirm', 'resend', 'logout'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['account', 'profile', 'resend-change', 'cancel', 'challenge'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['login', 'register', 'forgot', 'reset', 'login-email', 'login-callback'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Display index - debug page, login page, or account page
     */
    public function actionIndex()
    {
        if (defined('YII_DEBUG') && YII_DEBUG) {
            $actions = $this->module->getActions();
            return $this->render('index', ["actions" => $actions]);
        } elseif (Yii::$app->user->isGuest) {
            return $this->redirect(["/user/login"]);
        } else {
            return $this->redirect(["/user/account"]);
        }
    }

    /**
     * Display login page
     */
    public function actionLogin()
    {
        /** @var \app\modules\user\models\forms\LoginForm $model */
        $model = $this->module->model("LoginForm");

        // load post data and login
        $post = Yii::$app->request->post();
        if ($model->load($post) && $model->validate()) {
            $returnUrl = $this->performLogin($model->getUser(), $model->rememberMe);
            return $this->redirect($returnUrl);
        }

        return $this->render('login', compact("model"));
    }

    /**
     * Login/register via email
     */
    public function actionLoginEmail()
    {
        /** @var \app\modules\user\models\forms\LoginEmailForm $loginEmailForm */
        $loginEmailForm = $this->module->model("LoginEmailForm");

        // load post data and validate
        $post = Yii::$app->request->post();
        if ($loginEmailForm->load($post) && $loginEmailForm->sendEmail()) {
            $user = $loginEmailForm->getUser();
            $message = $user ? "Login link sent" : "Registration link sent";
            $message .= " - Please check your email";
            Yii::$app->session->setFlash("Login-success", Yii::t("user", $message));
        }

        return $this->render("loginEmail", compact("loginEmailForm"));
    }

    /**
     * Login/register callback via email
     */
    public function actionLoginCallback($token)
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\Profile $profile */
        /** @var \app\modules\user\models\Role $role */
        /** @var \app\modules\user\models\UserToken $userToken */

        $user = $this->module->model("User");
        $profile = $this->module->model("Profile");
        $userToken = $this->module->model("UserToken");

        // check token and log user in directly
        $userToken = $userToken::findByToken($token, $userToken::TYPE_EMAIL_LOGIN);
        if ($userToken && $userToken->user) {
            $returnUrl = $this->performLogin($userToken->user, $userToken->data);
            $userToken->delete();
            return $this->redirect($returnUrl);
        }

        // load post data
        $post = Yii::$app->request->post();
        $userLoaded = $user->load($post);
        $profileLoaded = $profile->load($post);
        if ($userToken && ($userLoaded || $profileLoaded)) {

            // ensure that email is taken from the $userToken (and not from user input)
            $user->email = $userToken->data;

            // validate and register
            if ($user->validate() && $profile->validate()) {
                $role = $this->module->model("Role");
                $user->setRegisterAttributes($role::ROLE_USER, $user::STATUS_ACTIVE)->save();
                $profile->setUser($user->id)->save();

                // log user in and delete token
                $returnUrl = $this->performLogin($user);
                $userToken->delete();
                return $this->redirect($returnUrl);
            }
        }

        $user->email = $userToken ? $userToken->data : null;
        return $this->render("loginCallback", compact("user", "profile", "userToken"));
    }

    /**
     * Perform the login
     */
    protected function performLogin($user, $rememberMe = true)
    {
        // log user in
        $loginDuration = $rememberMe ? $this->module->loginDuration : 0;
        
        // set the memorable info challenge flag to 1 (user will be asked for memorable information)
        $user->challenge = 1;
        $user->save(false);

        //log the user in
        Yii::$app->user->login($user, $loginDuration);

        //send the user to the challenge page to enter memorable information
        return Url::to(['challenge']);
    }

    public function actionChallenge()
    {

        $session = Yii::$app->session;

        //find the user
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();

        //if the user has already answered the challenge, bounce them out of the challenge page
        if ($user->challenge != 1) {
            return $this->redirect(Url::to(['/site/index']));
        }

        //get the user's memorable phrase
        $phrase = $user->phrase;

        if ($post = Yii::$app->request->post()) {

            //get the letter responses to check from the post
            $c0check = $post['c0'];
            $c1check = $post['c1'];
            $c2check = $post['c2'];

            //get the letter codes that were requested from the session
            $c0 = $session->get('c0');
            $c1 = $session->get('c1');
            $c2 = $session->get('c2');

            //get the correct letter values for the requested letters
            $c0safe = substr($phrase, $c0, 1);
            $c1safe = substr($phrase, $c1, 1);
            $c2safe = substr($phrase, $c2, 1);

            //check that all 3 reponses match the correct values
            if ($c0check == $c0safe && $c1check == $c1safe && $c2check == $c2safe) {
                //remove the challenge from the user
                $user->challenge = 0;
                $user->save(false);
                //reset the requested letters so the user will be asked for different letters next login
                $session->set('c0', null);
                $session->set('c1', null);
                $session->set('c2', null);
                //let the user in
                return $this->redirect(Url::to(['/site/index']));
            } else {
                //the user didn't provide correct letters, bounce them out back to login
                Yii::$app->user->logout();
                return $this->redirect(Url::to(['login']));
            }


        } else {

            //get the random letter codes from the session
            $c0 = $session->get('c0');
            $c1 = $session->get('c1');
            $c2 = $session->get('c2');


            //if we haven't got any random letter codes
            if (!$c0 && !$c1 && !$c2) {
        
                //get the length of the phrase
                $phraseLength = strlen($phrase) -1;

                //choose 3 distinct random, positive, nonzero integers up to length of phrase
                do {
                    $randomCharCodes = [rand(0,$phraseLength), rand(0,$phraseLength), rand(0,$phraseLength)];
                    $randomCharCodesCheck = array_unique($randomCharCodes);
                } while (count($randomCharCodes) != count($randomCharCodesCheck));

                //sort the random integers in ascending order
                sort($randomCharCodes,1);

                //store the requested phrase letter codes in the session for later
                $session = Yii::$app->session;

                $session->set('c0', $randomCharCodes[0]);
                $session->set('c1', $randomCharCodes[1]);
                $session->set('c2', $randomCharCodes[2]);

            } else {
                //we have got random letter codes, so use them again (they haven't been answered yet)
                //this way if the user refreshes the challenge page, they will be asked for the same 3 letters
                $randomCharCodes[0] = $c0;
                $randomCharCodes[1] = $c1;
                $randomCharCodes[2] = $c2;
            }

            //challenge the already logged in user for 3 digits of their memorable information
            return $this->render('challenge',['randomCharCodes' => $randomCharCodes]);
        
        }

    }

    /**
     * Log user out and redirect
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        // handle redirect
        $logoutRedirect = $this->module->logoutRedirect;
        if ($logoutRedirect) {
            return $this->redirect($logoutRedirect);
        }
        return $this->goHome();
    }

    /**
     * Display registration page
     */
    public function actionRegister()
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\Profile $profile */
        /** @var \app\modules\user\models\Role $role */

        // set up new user/profile objects
        $user = $this->module->model("User", ["scenario" => "register"]);
        $profile = $this->module->model("Profile");

        // load post data
        $post = Yii::$app->request->post();
        if ($user->load($post)) {

            // ensure profile data gets loaded
            $profile->load($post);

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user, $profile);
            }

            // validate for normal request
            if ($user->validate() && $profile->validate()) {

                // perform registration
                $role = $this->module->model("Role");
                $user->setRegisterAttributes($role::ROLE_USER)->save();
                $profile->setUser($user->id)->save();
                $this->afterRegister($user);

                // set flash
                // don't use $this->refresh() because user may automatically be logged in and get 403 forbidden
                $successText = Yii::t("user", "Successfully registered [ {displayName} ]", ["displayName" => $user->getDisplayName()]);
                $guestText = "";
                if (Yii::$app->user->isGuest) {
                    $guestText = Yii::t("user", " - Please check your email to confirm your account");
                }
                Yii::$app->session->setFlash("Register-success", $successText . $guestText);
            }
        }

        return $this->render("register", compact("user", "profile"));
    }

    /**
     * Process data after registration
     * @param \app\modules\user\models\User $user
     */
    protected function afterRegister($user)
    {
        /** @var \app\modules\user\models\UserToken $userToken */
        $userToken = $this->module->model("UserToken");

        // determine userToken type to see if we need to send email
        $userTokenType = null;
        if ($user->status == $user::STATUS_INACTIVE) {
            $userTokenType = $userToken::TYPE_EMAIL_ACTIVATE;
        } elseif ($user->status == $user::STATUS_UNCONFIRMED_EMAIL) {
            $userTokenType = $userToken::TYPE_EMAIL_CHANGE;
        }

        // check if we have a userToken type to process, or just log user in directly
        if ($userTokenType) {
            $userToken = $userToken::generate($user->id, $userTokenType);
            if (!$numSent = $user->sendEmailConfirmation($userToken)) {

                // handle email error
                //Yii::$app->session->setFlash("Email-error", "Failed to send email");
            }
        } else {
            Yii::$app->user->login($user, $this->module->loginDuration);
        }
    }

    /**
     * Confirm email
     */
    public function actionConfirm($token)
    {
        /** @var \app\modules\user\models\UserToken $userToken */
        /** @var \app\modules\user\models\User $user */

        // search for userToken
        $success = false;
        $email = "";
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByToken($token, [$userToken::TYPE_EMAIL_ACTIVATE, $userToken::TYPE_EMAIL_CHANGE]);
        if ($userToken) {

            // find user and ensure that another user doesn't have that email
            //   for example, user registered another account before confirming change of email
            $user = $this->module->model("User");
            $user = $user::findOne($userToken->user_id);
            $newEmail = $userToken->data;
            if ($user->confirm($newEmail)) {
                $success = true;
            }

            // set email and delete token
            $email = $newEmail ?: $user->email;
            $userToken->delete();
        }

        return $this->render("confirm", compact("userToken", "success", "email"));
    }

    /**
     * Account
     */
    public function actionAccount()
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\UserToken $userToken */
        //find the user
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            
            // set up user and load post data
            $user = Yii::$app->user->identity;
            $user->setScenario("account");
            $loadedPost = $user->load(Yii::$app->request->post());

            // validate for ajax request
            if ($loadedPost && Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user);
            }

            // validate for normal request
            $userToken = $this->module->model("UserToken");
            if ($loadedPost && $user->validate()) {

                // check if user changed his email
                $newEmail = $user->checkEmailChange();
                if ($newEmail) {
                    $userToken = $userToken::generate($user->id, $userToken::TYPE_EMAIL_CHANGE, $newEmail);
                    if (!$numSent = $user->sendEmailConfirmation($userToken)) {

                        // handle email error
                        //Yii::$app->session->setFlash("Email-error", "Failed to send email");
                    }
                }

                // save, set flash, and refresh page
                $user->save(false);
                Yii::$app->session->setFlash("Account-success", Yii::t("user", "Account updated"));
                return $this->refresh();
            } else {
                $userToken = $userToken::findByUser($user->id, $userToken::TYPE_EMAIL_CHANGE);
            }

            return $this->render("account", compact("user", "userToken"));
          }
          else {
              return $this->redirect(['/user/challenge']);
          }
    }

    /**
     * Profile
     */
    public function actionProfile()
    {
        /** @var \app\modules\user\models\Profile $profile */

        // set up profile and load post data
        $profile = Yii::$app->user->identity->profile;
        $loadedPost = $profile->load(Yii::$app->request->post());

        // validate for ajax request
        if ($loadedPost && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($profile);
        }

        // validate for normal request
        if ($loadedPost && $profile->validate()) {
            $profile->save(false);
            Yii::$app->session->setFlash("Profile-success", Yii::t("user", "Profile updated"));
            return $this->refresh();
        }

        return $this->render("profile", compact("profile"));
    }

    /**
     * Resend email confirmation
     */
    public function actionResend()
    {
        /** @var \app\modules\user\models\forms\ResendForm $model */

        // load post data and send email
        $model = $this->module->model("ResendForm");
        if ($model->load(Yii::$app->request->post()) && $model->sendEmail()) {

            // set flash (which will show on the current page)
            Yii::$app->session->setFlash("Resend-success", Yii::t("user", "Confirmation email resent"));
        }

        return $this->render("resend", compact("model"));
    }

    /**
     * Resend email change confirmation
     */
    public function actionResendChange()
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\UserToken $userToken */

        // find userToken of type email change
        $user = Yii::$app->user->identity;
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByUser($user->id, $userToken::TYPE_EMAIL_CHANGE);
        if ($userToken) {

            // send email and set flash message
            $user->sendEmailConfirmation($userToken);
            Yii::$app->session->setFlash("Resend-success", Yii::t("user", "Confirmation email resent"));
        }

        return $this->redirect(["/user/account"]);
    }

    /**
     * Cancel email change
     */
    public function actionCancel()
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\UserToken $userToken */

        // find userToken of type email change
        $user = Yii::$app->user->identity;
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByUser($user->id, $userToken::TYPE_EMAIL_CHANGE);
        if ($userToken) {
            $userToken->delete();
            Yii::$app->session->setFlash("Cancel-success", Yii::t("user", "Email change cancelled"));
        }

        return $this->redirect(["/user/account"]);
    }

    /**
     * Forgot password
     */
    public function actionForgot()
    {
        /** @var \app\modules\user\models\forms\ForgotForm $model */

        // load post data and send email
        $model = $this->module->model("ForgotForm");
        if ($model->load(Yii::$app->request->post()) && $model->sendForgotEmail()) {

            // set flash (which will show on the current page)
            Yii::$app->session->setFlash("Forgot-success", Yii::t("user", "Instructions to reset your password have been sent"));
        }

        return $this->render("forgot", compact("model"));
    }

    /**
     * Reset password
     */
    public function actionReset($token)
    {
        /** @var \app\modules\user\models\User $user */
        /** @var \app\modules\user\models\UserToken $userToken */

        // get user token and check expiration
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByToken($token, $userToken::TYPE_PASSWORD_RESET);
        if (!$userToken) {
            return $this->render('reset', ["invalidToken" => true]);
        }

        // get user and set "reset" scenario
        $success = false;
        $user = $this->module->model("User");
        $user = $user::findOne($userToken->user_id);
        $user->setScenario("reset");

        // load post data and reset user password
        if ($user->load(Yii::$app->request->post()) && $user->save()) {

            // delete userToken and set success = true
            $userToken->delete();
            $success = true;
        }

        return $this->render('reset', compact("user", "success"));
    }
}