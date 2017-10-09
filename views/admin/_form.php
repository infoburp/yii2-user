<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\modules\user\Module $module
 * @var app\modules\user\models\User $user
 * @var app\modules\user\models\Profile $profile
 * @var app\modules\user\models\Role $role
 * @var yii\widgets\ActiveForm $form
 */

$module = $this->context->module;
$role = $module->model("Role");
?>

<div class="user-form">

    <?php $form = ActiveForm::begin([
        'enableAjaxValidation' => true,
    ]); ?>

    <?= $form->field($user, 'email')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($user, 'username')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($user, 'secret')->textInput(['maxlength' => 255]) ?>

    <b>Note: This is the secret key in Base 32 format. If you paste a key from the Yubi Key control panel (Secret Key (20 bytes Hex)), it will be automatically converted to Base 32 format.</b>
    <br><br>

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

    <?= $form->field($user, 'count')->textInput(['maxlength' => 255]) ?>

    <b>Note: The count will usually be 0 for a new secret or brand new key, and is referred to as 'Moving Factor Seed' in the Yubi Key control panel. For additional security this can be set to a nonzero positive integer value when a new secret is written to the key. It should be set to the same value here and on the key (select fixed and enter a nonzero positive integer on the Yubi Key control panel).</b>
    <br><br>

    <?= $form->field($user, 'phrase')->textInput(['maxlength' => 255]) ?>

    <b>Note: Phrase can only contain lower case letters (a-z) and numbers (0-9).</b>
    <br><br>

    <?= $form->field($user, 'newPassword')->passwordInput() ?>

    <?= $form->field($profile, 'full_name'); ?>

    <?= $form->field($user, 'role_id')->dropDownList($role::dropdown()); ?>

    <?= $form->field($user, 'status')->dropDownList($user::statusDropdown()); ?>

    <?php // use checkbox for banned_at ?>
    <?php // convert `banned_at` to int so that the checkbox gets set properly ?>
    <?php $user->banned_at = $user->banned_at ? 1 : 0 ?>
    <?= Html::activeLabel($user, 'banned_at', ['label' => Yii::t('user', 'Banned')]); ?>
    <?= Html::activeCheckbox($user, 'banned_at'); ?>
    <?= Html::error($user, 'banned_at'); ?>

    <?= $form->field($user, 'banned_reason'); ?>

    <div class="form-group">
        <?= Html::submitButton($user->isNewRecord ? Yii::t('user', 'Create') : Yii::t('user', 'Update'), ['class' => $user->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
