<?php

namespace infoburp\yii2\user\controllers;

use Yii;
use infoburp\yii2\user\models\User;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * AdminController implements the CRUD actions for User model.
 */
class AdminController extends Controller
{
    /**
     * @var \app\modules\user\Module
     * @inheritdoc
     */
    public $module;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        // check for admin permission (`tbl_role.can_admin`)
        // note: check for Yii::$app->user first because it doesn't exist in console commands (throws exception)
        if (!empty(Yii::$app->user) && !Yii::$app->user->can("admin")) {
            throw new ForbiddenHttpException('You are not allowed to perform this action.');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * List all User models
     * @return mixed
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            /** @var \app\modules\user\models\search\UserSearch $searchModel */
            $searchModel = $this->module->model("UserSearch");
            $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

            return $this->render('index', compact('searchModel', 'dataProvider'));

        } else {
            return $this->redirect(['/user/challenge']);
        }
    }

    /**
     * Display a single User model
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            return $this->render('view', [
                'user' => $this->findModel($id),
            ]);
        } else {
            return $this->redirect(['/user/challenge']);
        }
    }

    /**
     * Create a new User model. If creation is successful, the browser will
     * be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            /** @var \app\modules\user\models\User $user */
            /** @var \app\modules\user\models\Profile $profile */

            $user = $this->module->model("User");
            $user->setScenario("admin");
            $profile = $this->module->model("Profile");

            $post = Yii::$app->request->post();
            $userLoaded = $user->load($post);
            $profile->load($post);

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user, $profile);
            }

            if ($userLoaded && $user->validate() && $profile->validate()) {
                $user->save(false);
                $profile->setUser($user->id)->save(false);
                return $this->redirect(['view', 'id' => $user->id]);
            }

            // render
            return $this->render('create', compact('user', 'profile'));
        } else {
            return $this->redirect(['/user/challenge']);
        }
    }

    /**
     * Update an existing User model. If update is successful, the browser
     * will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            // set up user and profile
            $user = $this->findModel($id);
            $user->setScenario("admin");
            $profile = $user->profile;

            $post = Yii::$app->request->post();
            $userLoaded = $user->load($post);
            $profile->load($post);

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user, $profile);
            }

            // load post data and validate
            if ($userLoaded && $user->validate() && $profile->validate()) {
                $user->save(false);
                $profile->setUser($user->id)->save(false);
                return $this->redirect(['view', 'id' => $user->id]);
            }

            // render
            return $this->render('update', compact('user', 'profile'));
        } else {
            return $this->redirect(['/user/challenge']);
        }
    }

    /**
     * Delete an existing User model. If deletion is successful, the browser
     * will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            // delete profile and userTokens first to handle foreign key constraint
            $user = $this->findModel($id);
            $profile = $user->profile;
            $userToken = $this->module->model("UserToken");
            $userAuth = $this->module->model("UserAuth");
            $userToken::deleteAll(['user_id' => $user->id]);
            $userAuth::deleteAll(['user_id' => $user->id]);
            $profile->delete();
            $user->delete();

            return $this->redirect(['index']);
        } else {
            return $this->redirect(['/user/challenge']);
        }
    }

    /**
     * Find the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $userId = Yii::$app->user->getId();
        $user = User::find()->where(['id' => $userId])->one();
        if ($user->challenge == 0)  {
            /** @var \app\modules\user\models\User $user */
            $user = $this->module->model("User");
            $user = $user::findOne($id);
            if ($user) {
                return $user;
            }

            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
              return $this->redirect(['/user/challenge']);
          }
    }
}
