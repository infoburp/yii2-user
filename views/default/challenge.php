<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\user\models\forms\LoginForm $model
 */
$this->title = Yii::t('user', 'Memorable Information');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-default-challenge">
    <h1><?= Html::encode($this->title) ?></h1>
	<?php 
		$alphaNumerics = [
			''=>'',
			'a'=>'a',
			'b'=>'b',
			'c'=>'c',
			'd'=>'d',
			'e'=>'e',
			'f'=>'f',
			'g'=>'g',
			'h'=>'h',
			'i'=>'i',
			'j'=>'j',
			'k'=>'k',
			'l'=>'l',
			'm'=>'m',
			'n'=>'n',
			'o'=>'o',
			'p'=>'p',
			'q'=>'q',
			'r'=>'r',
			's'=>'s',
			't'=>'t',
			'u'=>'u',
			'v'=>'v',
			'w'=>'w',
			'x'=>'x',
			'y'=>'y',
			'z'=>'z',
			'0'=>'0',
			'1'=>'1',
			'2'=>'2',
			'3'=>'3',
			'4'=>'4',
			'5'=>'5',
			'6'=>'6',
			'7'=>'7',
			'8'=>'8',
			'9'=>'9'] 
	?>
	<?= Html::beginForm('challenge') ?>

	<div class="row">
		<div class="col-md-12">
			Please enter the following characters from your memorable phrase
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			Character <?= $randomCharCodes[0] + 1 ?> :
		</div>
		<div class="col-md-3">
			<?= Html::dropDownList('c0', null, $alphaNumerics, ['class' => 'form-control'])?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			Character <?= $randomCharCodes[1] + 1 ?> :
		</div>
		<div class="col-md-3">
			<?= Html::dropDownList('c1', null, $alphaNumerics, ['class' => 'form-control'])?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			Character <?= $randomCharCodes[2] + 1?> :
		</div>
		<div class="col-md-3">
			<?= Html::dropDownList('c2', null, $alphaNumerics, ['class' => 'form-control'])?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<input type='submit' name='submit' value='Submit' class='btn btn-success'>
		</div>
	</div>
	<?= Html::endForm() ?>
</div>
<style>
	.row {
		padding-bottom: 15px;
	}
</style>