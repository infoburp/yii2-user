<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\user\models\forms\LoginForm $model
 */

$this->title = Yii::t('user', 'Login');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-default-login">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2 control-label'],
        ],

    ]); ?>
    <?php $alphaNumerics = [''=>'','a'=>'a','b'=>'b','c'=>'c','d'=>'d','e'=>'e','f'=>'f','g'=>'g','h'=>'h','i'=>'i','j'=>'j','k'=>'k','l'=>'l','m'=>'m','n'=>'n','o'=>'o','p'=>'p','q'=>'q','r'=>'r','s'=>'s','t'=>'t','u'=>'u','v'=>'v','w'=>'w','x'=>'x','y'=>'y','z'=>'z','0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9'] ?>
    <?= $form->field($model, 'email') ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <!--<?= $form->field($model, 'password')->dropDownList ( $alphaNumerics , $options = [] )->label('Phrase letter 4') ?>
    <?= $form->field($model, 'password')->dropDownList ( $alphaNumerics , $options = [] )->label('Phrase letter 6') ?>
    <?= $form->field($model, 'password')->dropDownList ( $alphaNumerics , $options = [] )->label('Phrase letter 9') ?>-->

    <?= $form->field($model, 'otp')->passwordInput()->label('Yubi Key') ?>
    <?= $form->field($model, 'rememberMe', [
        'template' => "{label}<div class=\"col-lg-offset-2 col-lg-3\">{input}</div>\n<div class=\"col-lg-7\">{error}</div>",
    ])->checkbox() ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <?= Html::submitButton(Yii::t('user', 'Login'), ['class' => 'btn btn-primary']) ?>

            <br/><br/>

        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <br>

    <b>Note: For the purposes of this demo, the login is U: ncoders, P: ncoders, Phrase: ncoders, Yubi Key Secret Hex: 5d a3 21 0b 37 75 f3 9c 6f 0e a4 b2 fe 7c 02 c4 68 3e d5 ab (LWRSCCZXOXZZY3YOUSZP47ACYRUD5VNL in base 32)</b>

</div>
