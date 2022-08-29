<?php
/* @var $this yii\web\View */
/* @var $searchModel common\models\Order*/

use frontend\assets\AdminAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;



AdminAsset::register($this);
?>
 <!-- admin-finance/index -->
<main class="main">
    <div class="row">
       <div class="col-sm-offset-3 col-xs-offset-0 col-sm-6 col-xs-12 top-search-row__block">
            <?php $form = ActiveForm::begin([
                'action' => Url::toRoute(['admin-finance/index']),
                'method' => 'post',
                'id' => 'finance-id-form',
                'options' => [
                    'class' => ''
                ]
            ]); ?>
            <div class="top-search-form finance-form">
                <div class="finance-salon-id">
                    <div class="top-search-form__icon">
                        <img src="<?= Url::to('/uploads/images/search.svg'); ?>" alt="search">
                    </div>
                    <div class="top-search-form__input-wrapper">
                        <?= $form->field($searchModel, 'salon_id', [
                            'enableClientValidation' => false
                        ])->textInput([
                            'placeholder' => Yii::t('app', 'ID Salon')
                        ])->label(false) ?>
                    </div>
                    <div class="top-search-form__submit-wrapper">
                        <div class="form-group">
                            <?= Html::submitButton(Yii::t('app', 'Find'), ['class' => 'btn-main top-search-btn  top-search-btn-ajax']) ?>
                        </div>
                    </div>
                </div>
                <div class="finance-lo-go">
                    <div class="top-search-form__input-wrapper finance-paid">
                        <?= $form->field($searchModel, 'paid_LO', [
                            'enableClientValidation' => false
                        ])->textInput([
                            'placeholder' => Yii::t('app', 'PV')
                        ])->label(false) ?>
                    </div>
                    <div class="top-search-form__input-wrapper finance-paid">
                        <?= $form->field($searchModel, 'paid_GO', [
                            'enableClientValidation' => false
                        ])->textInput([
                            'placeholder' => Yii::t('app', 'GV')
                        ])->label(false) ?>
                    </div>
                    <div class="dysp-none month-year order-date">
                        <?= $form->field($searchModel, 'date', [
                            'enableClientValidation' => false,
                        ])?>
                    </div>
                    <div class="top-search-form__submit-wrapper">
                        <div class="form-group">
                            <?= Html::submitButton(Yii::t('app', 'to pay'), ['class' => 'btn-main bg-blue-one top-search-btn']) ?>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12">
                    <div class="date-widget-wrapper">
                        <div class="finance-prev-day prev-day"><i class="fas fa-arrow-left"></i></div>
                        <div class="widget-block-wrapper txtalign">
                            <div class="widget-week-day-info text-center" value="<?= $month_year ?>">
                                <span><?= Yii::t('app', ucfirst(date("F", strtotime("1.{$month_year}")))); ?></span>
                            </div>
                        </div>
                        <div class="finance-next-day next-day"><i class="fas fa-arrow-right"></i></div>
                    </div>
                </div>


            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <div class="row sort-header">
        <div class="col-sm-1 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Salon'); ?></span></div>
        <div class="col-sm-1 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Total number of records'); ?></span></div>
        <div class="col-sm-1 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Reward PV'); ?>, &#8381;</span></div>
        <div class="col-sm-1 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Reward GV'); ?>, &#8381;</span></div>
        <div class="col-sm-2 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Paid for personal records'); ?></span></div>
        <div class="col-sm-2 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Paid for group records'); ?></span></div>
        <div class="col-sm-2 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Salon rewards written off'); ?> &#8381;</span></div>
        <div class="col-sm-2 text-center"><span class="sort-header__link"><?= Yii::t('app', 'Total payable'); ?></span></div>
    </div>

<!-- распихиваю данные в js -->
    <?php for ($i=0;$i<8;$i++): ?>
    <? if($i >= 4 ){ ?>
        <div class="col-sm-2 list-block__info text-center finance-row">
            -
        </div>
    <? } ?>
    <? if($i < 4){ ?>
        <div class="col-sm-1 list-block__info text-left finance-row">
            -
        </div>
    <? } ?>
    <?php endfor ?>

</main>
