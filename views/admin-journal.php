<?php

use frontend\assets\AdminAsset;
use kartik\datetime\DateTimePicker;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;


/* @var $searchModel \frontend\models\search\MasterSearch */
/* @var $journal array */
/* @var $this yii\web\View */

AdminAsset::register($this);
?>
<main>
            <div> <?php echo $journal['error_mes'] ?> </div>
            <div class="col-sm-offset-3 col-xs-offset-0 col-sm-6 col-xs-12 top-search-row__block">
                <?php $form = ActiveForm::begin([
                    'action' => Url::toRoute(['admin-journal/index']),
                    'method' => 'post',
                    'id' => 'master-search-form',
                    'options' => [
                        'class' => ''
                    ]
                ]); ?>
                <div class="top-search-form">
                    <div class="top-search-form__icon">
                        <img src="<?= Url::to('/uploads/images/search.svg'); ?>" alt="search">
                    </div>
                    <div class="top-search-form__input-wrapper">
                        <?= $form->field($journal['searchModel'], 'salon_id', [
                            'enableClientValidation' => false
                        ])->textInput([
                            'placeholder' => 'Название салона или ID'
                        ])->label(false) ?>
                    </div>
                    <div class="top-search-form__submit-wrapper">
                        <div class="form-group">
                            <?= Html::submitButton(Yii::t('app', 'Find'), ['class' => 'btn-main top-search-btn']) ?>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>


    <div class="col-xs-12">
        <div class="date-widget-wrapper">
            <div class="prev-day"><a class="simple-link" href="<?= Url::toRoute(['admin-journal/index',  'month_year' => $journal['prev']]) ?>"><i class="fas fa-arrow-left"></i></a></div>
            <div class="widget-block-wrapper txtalign">
                <div class="widget-week-day-info text-center">
                    <span><?= Yii::t('app', ucfirst(date("F", strtotime("1.{$journal['month_year']}")))); ?></span>
                </div>
            </div>
            <div class="next-day"><a class="simple-link" href="<?= Url::toRoute(['admin-journal/index', 'month_year' => $journal['next']]); ?>"><i class="fas fa-arrow-right"></a></i></div>
        </div>
    </div>


    <div id="right_structure">
    <table class="table_journal">
        <tr>
            <th>
                Салон
            </th>
            <th>
                № записи
            </th>
            <th>
                Визит
            </th>
            <th>
                Клиент
            </th>
            <th>
               Стоимость, ₽
            </th>
            <th>
                Баллы
            </th>
            <th>
                Вознаграждение, ₽
            </th>
            <th>
                Групповой бонус, ₽
            </th>
            <th>
                Списано, ₽
            </th>
            <th>
                Статус
            </th>
        </tr>
        <?php if ($journal['orders']):?>
            <?php
            $count_bonus  = 0;
            $count_price = 0;
            $count_point = 0;
            $count_group_bonus = 0;
            $count_used_bonus = 0;
            foreach ($journal['orders'] as $order):?>

                <tr>
                    <td>
                         <?= $order->salon_id?>
                    </td>
                   <td>
                        <?= $order->id?>
                   </td>
                    <td>
                        <?= $order->date?>
                        <br>
                        <?= $order->start_time?>

                    </td>
                    <td>
                        <?= $order->client_id ?>
                    </td>
                    <td>
                        <?echo $order->salon_service_id;
                        if($order->status == 2)
                            $count_price += intval($order->salon_service_id);
                        ?>
                    </td>
                    <td>
                        <? echo round(intval($order->salon_service_id) / 150, 2);
                        if($order->status == 2)
                            $count_point += round(intval($order->salon_service_id) / 150, 2) ?>
                    </td>
                    <td>
                        <? echo  round(intval($order->salon_service_id) / 150 *0.2 *35, 2);
                        if($order->status == 2)
                            $count_bonus += ( round(intval($order->salon_service_id) / 150, 2)*0.2)*35;?>
                    </td>
                  <td>
                    <? echo round($order->group_bonus * 35, 2);
                    if($order->status == 2)
                        $count_group_bonus +=  round($order->group_bonus * 35, 2)?>
                 </td>
                    <td>
                        <? echo $order->used_bonus;
                        if($order->status == 2)
                            $count_used_bonus += $order->used_bonus; ?>
                    </td>
                    <td>
                        <?= $journal['statuses'][$order->status] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th>

                </th>
                <th>
                    Итого
                </th>
                <th>

                </th>
                <th>

                </th>
                <th>
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    <? echo $count_price; ?>
                </th>
                <th>
                    &nbsp
                    &nbsp

                    <? echo $count_point ?>
                </th>
                <th>
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp

                    <? echo $count_bonus ?>
                </th>
                <th>
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp

                    <? echo $count_group_bonus ?>
                </th>
                <th>
                    &nbsp
                    &nbsp
                    &nbsp
                    &nbsp

                    <?= $count_used_bonus ?>
                </th>
                <th>
                </th>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="8"><?= Yii::t('app', 'No results in structure') ?></td>
            </tr>
        <?php endif; ?>
    </table>
    </div>
</main>
