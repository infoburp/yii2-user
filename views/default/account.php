<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\user\Module $module
 * @var app\modules\user\models\User $user
 * @var app\modules\user\models\UserToken $userToken
 */

$module = $this->context->module;

$this->title = Yii::t('user', 'Account');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-default-account">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($flash = Yii::$app->session->getFlash("Account-success")): ?>

        <div class="alert alert-success">
            <p><?= $flash ?></p>
        </div>

    <?php elseif ($flash = Yii::$app->session->getFlash("Resend-success")): ?>

        <div class="alert alert-success">
            <p><?= $flash ?></p>
        </div>

    <?php elseif ($flash = Yii::$app->session->getFlash("Cancel-success")): ?>

        <div class="alert alert-success">
            <p><?= $flash ?></p>
        </div>

    <?php endif; ?>

    <?php $form = ActiveForm::begin([
        'id' => 'account-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2 control-label'],
        ],
        'enableAjaxValidation' => true,
    ]); ?>

    <?php if ($user->password): ?>
        <?= $form->field($user, 'currentPassword')->passwordInput() ?>
        <?= $form->field($user, 'currentPhrase')->passwordInput() ?>
    <?php endif ?>

    <?= $form->field($user, 'secret') ?>
    <b>Note: This is the secret key in Base 32 format. If you paste a key from the Yubi Key control panel (Secret Key (20 bytes Hex)), it will be automatically converted to Base 32 format.</b>
    <script>
        //convert pasted secret to Base 32 if it is hex
        var secretElement = document.getElementById('user-secret');
        secretElement.onchange = function() {
            var base32_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567"
            function binary_to_base32(input) {
                var ret = new Array();
                var ret_len = 0;
                var i = 0;
            
                var unpadded_length = input.length;
                while (input.length % 5) {
                    input[input.length] = 0;
                }
            
                for(i=0; i<input.length; i+=5) {
                    ret += base32_chars.charAt((input[i] >> 3));
                    ret += base32_chars.charAt(((input[i] & 0x07) << 2) | ((input[i+1] & 0xc0) >> 6));
                    if (i+1 >= unpadded_length) {
                        ret += "======"
                        break;
                    }
                    ret += base32_chars.charAt(((input[i+1] & 0x3e) >> 1));       
                    ret += base32_chars.charAt(((input[i+1] & 0x01) << 4) | ((input[i+2] & 0xf0) >> 4));
                    if (i+2 >= unpadded_length) {
                        ret += "===="
                        break;
                    }        
                    ret += base32_chars.charAt(((input[i+2] & 0x0f) << 1) | ((input[i+3] & 0x80) >> 7));
                    if (i+3 >= unpadded_length) {
                        ret += "==="
                        break;
                    }        
                    ret += base32_chars.charAt(((input[i+3] & 0x7c) >> 2));
                    ret += base32_chars.charAt(((input[i+3] & 0x03) << 3) | ((input[i+4] & 0xe0) >> 5));
                    if (i+4 >= unpadded_length) {
                        ret += "="
                        break;
                    }          
                    ret += base32_chars.charAt(((input[i+4] & 0x1f)));      
                }
                return ret;
            }
            
            function Convert() {
                cleaned_hex = secretElement.value.toUpperCase().replace(/[^A-Fa-f0-9]/g, "");
                if (cleaned_hex.length % 2) {   
                    return;
                }
                var binary = new Array();
                for (var i=0; i<cleaned_hex.length/2; i++) {
                    var h = cleaned_hex.substr(i*2, 2);
                    binary[i] = parseInt(h,16);        
                }
                secretElement.value = binary_to_base32(binary);
            } 

            Convert();
        };
    </script>
    <?= $form->field($user, 'newPhrase')->passwordInput() ?>
    <?= $form->field($user, 'newPhraseConfirm')->passwordInput() ?>
    <b>Note: Your phrase can only contain lower case letters (a-z) and numbers (0-9).</b>
    <br><br>

    <?= $form->field($user, 'count') ?>
    <b>Note: The count will usually be 0 for a new secret or brand new key, and is referred to as 'Moving Factor Seed' in the Yubi Key control panel. For additional security this can be set to a nonzero positive integer value when a new secret is written to the key. It should be set to the same value here and on the key (select fixed and enter a nonzero positive integer on the Yubi Key control panel).</b>

    <hr/>

    <?php if ($module->useEmail): ?>
        <?= $form->field($user, 'email') ?>
    <?php endif; ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">

            <?php if (!empty($userToken->data)): ?>

                <p class="small"><?= Yii::t('user', "Pending email confirmation: [ {newEmail} ]", ["newEmail" => $userToken->data]) ?></p>
                <p class="small">
                    <?= Html::a(Yii::t("user", "Resend"), ["/user/resend-change"]) ?> / <?= Html::a(Yii::t("user", "Cancel"), ["/user/cancel"]) ?>
                </p>

            <?php elseif ($module->emailConfirmation): ?>

                <p class="small"><?= Yii::t('user', 'Changing your email requires email confirmation') ?></p>

            <?php endif; ?>

        </div>
    </div>

    <?php if ($module->useUsername): ?>
        <?= $form->field($user, 'username') ?>
    <?php endif; ?>

    <?= $form->field($user, 'newPassword')->passwordInput() ?>
    <?= $form->field($user, 'newPasswordConfirm')->passwordInput() ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <?= Html::submitButton(Yii::t('user', 'Update'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <?php foreach ($user->userAuths as $userAuth): ?>
                <p>Linked Social Account: <?= ucfirst($userAuth->provider) ?> / <?= $userAuth->provider_id ?></p>
            <?php endforeach; ?>
        </div>
    </div>

</div>