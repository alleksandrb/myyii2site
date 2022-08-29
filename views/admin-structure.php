<?php

/* @var $this yii\web\View */
/* @var $clients User */

use common\models\User;
use frontend\assets\AdminAsset;
use yii\helpers\Url;
use console\controllers\BonusController;


AdminAsset::register($this);
?>
<main>
    <div id="right_structure">
        <form id="search_client" action="<?= Url::toRoute(['admin-structure/index']) ?>" method="get">
            <img src="<?= Url::to('/uploads/images/search.svg'); ?>" alt="Поиск" >
            <input type="text" name="idTemp" class="ID_client" placeholder="ID" value="<?= $_GET['idTemp'] ?>">
            <input type="tel" name="phone" class="phone_client" placeholder="Телефон" value="<?= $_GET['phone'] ?>">
            <input type="submit" name="submit" value="Найти" class="submit" >
            <br>
            <input type="text" id="userId" placeholder="ID верхнего уровня">
            <input type="text" id="userCount" placeholder="количество">
            <button id="addUsers">Добавить фейковых пользователей в структуру</button>
            <br>
            <button id="generateUserBonus">Сгенерировать фейковые бонусы</button>
            <button id="calculateBonus">Просчитать бонусы</button>
        </form>

        <?php if ($breadcrumbsArr):?>
            <div class="breadcrumbs">
                <?= \yii\helpers\Html::a('Структура', ['admin-structure/index']); ?>&raquo;
                <?php foreach ($breadcrumbsArr as $key => $link):?>
                    <?php if ($key < count($breadcrumbsArr) - 1): ?>
                        <?= \yii\helpers\Html::a($link['title'], ['admin-structure/index', 'id' => $link['id']]) ?>&raquo;
                    <?php else: ?>
                        <span><?= $link['title'] ?></span>
                    <?php endif; ?>

                <?php endforeach;?>
            </div>
        <?php endif; ?>
        <table>
            <tr>
                <th>
                    ID Менеджера
                </th>
                <th>
                    Квал
                </th>
                <th>
                    Имя Менеджера
                </th>
                <th>
                    ID Наставника
                </th>
                <th>
                    ЛО, б
                </th>
                <th>
                    БП, б
                </th>
                <th>
                    ГО, б
                </th>
                <th>
                    ЛО, ₽
                </th>
                <th>
                    ГО, ₽
                </th>    
                <th>
                    Текущее возн.
                </th>                
                <th>
                    
                </th>
                <!--<th> 
                    Уровень
                </th> -->
            </tr>
            <?php if ($clients):?>
            <?php foreach ($clients as $client):?>
            <tr>
                <td>
                    EM <?= $client->id ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->qualificationLevelLabel ?>
                </td>
                <td>
                    <?php if ($client->partners):?>
                        <a href="<?= $client->partners? Url::toRoute(['admin-structure/index', 'id' => $client->id]): '#' ?>"><?= "{$client->last_name} {$client->name} {$client->second_name}" ?></a>
                    <?php else:?>
                        <?= "{$client->last_name} {$client->name} {$client->second_name}"; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $client->mentor_id ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->personal ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->partner_bonus ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->group ?>
                    <?// echo '<pre>'; var_dump(count($client->bonuses)); echo '</pre>'; ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->personal * Yii::$app->params['lo_k'] * Yii::$app->params['usd_to_rub'] ?>
                </td>
                <td>
                    <?= $client->bonuses[count($client->bonuses) - 1]->group_ye * Yii::$app->params['usd_to_rub'] + $client->bonuses[count($client->bonuses) - 1]->group_BP * Yii::$app->params['usd_to_rub'];  ?>
                </td>
                <td>     
                    <?php  echo BonusController::getCurentAward($client->id); ?>
                </td>
                <td>              
                        <button type="button" class="btn-main blue-btn payed-bonus-button" bonus = "<?php  echo BonusController::getCurentAward($client->bonuses[0]->user_id); ?>" value="<?= $client->bonuses[0]->user_id ?>" name= "<?= $client->last_name.' '. $client->name .' '. $client->second_name ?> ">              
                            Оплатить
                        </button>
                </td>

            </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8"><?= Yii::t('app', 'No results in structure') ?></td>
                </tr>
            <?php endif; ?>
        </table>
        <div id="arrowStructur">
            <div>
                <a href="#"><img src="<?= Url::to('/uploads/images/leftArrow.svg'); ?>" alt="Стрелка влево" ></a>
            </div>
            <div class="number">
                <a href="#">1</a>
            </div>
            <div class="number">
                <a href="#">2</a>
            </div>
            <div class="number" >
                <a href="#">3</a>
            </div>
            <div>
                <a href="#"><img src="<?= Url::to('/uploads/images/rightArrow.svg'); ?>" alt="Стрелка вправо" ></a>
            </div>
        </div>
    </div>


<div class="modal-payed-wrapper" style="display:none;">
        <div class="modal-payed-title">Оплата вознаграждения для: <strong></strong></div>
        <div id="current-bonus">Текущее вознаграждение: <strong></strong></div>
        <input id="id-user-payed" type="number" name='idUser' value='' style="display: none;">
        <input type="number" id="bonus-count-payed">    
        <button type="submit" id="payed-bonus" name="payed-bonus">Оплатить</button>  
</div>
</main>

