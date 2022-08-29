<?php

namespace frontend\controllers;


use common\models\Bonus;
use common\models\Order;
use common\models\Salon;
use common\models\SalonService;
use common\models\User;
use console\controllers\BonusController;
use frontend\models\search\ClientSearch;
use frontend\models\search\OrderSearch;
use Yii;
use yii\base\BaseObject;


class AdminJournalController extends \yii\web\Controller
{
    public function actionIndex()
    {
        if (!User::canAdminPermission())
        return $this->redirect(['client/main']);

        $cookies_response = Yii::$app->response->cookies;
        $error_mes = '';

        $request_post = Yii::$app->request->post();

        if($request_post["OrderSearch"]['salon_id'])
        {
            if (!is_numeric($request_post["OrderSearch"]['salon_id']))
            {
                $salon = Salon::find()
                    ->where(['name' => $request_post["OrderSearch"]['salon_id']])
                    ->one();

                if($salon)
                {
                    $salon_id = $salon->id;
                    $cookies_response->add(new \yii\web\Cookie([
                        'name' => 'salon_id',
                        'value' => $salon_id,
                    ]));
                }
                if (!$salon)
                {
                    $error_mes = 'Неверно введен ID или название магазина';
                }
            }
            if(is_numeric($request_post["OrderSearch"]['salon_id']))
            {
                $salon_id = $request_post["OrderSearch"]['salon_id'];
                $salon = Salon::find()
                    ->where(['id' => $salon_id])
                    ->one();

                if($salon)
                {
                    $cookies_response->add(new \yii\web\Cookie([
                        'name' => 'salon_id',
                        'value' =>  $request_post["OrderSearch"]['salon_id'],
                    ]));
                }
                if (!$salon)
                {
                    $error_mes = 'Неверно введен ID или название магазина';
                }
            }
        }
        else
        {
            $cookies_request = Yii::$app->request->cookies;
            if($cookies_request['salon_id'])
                $salon_id = $cookies_request['salon_id'];

            if(!$cookies_request['salon_id'])
            {
                $cookies_response = Yii::$app->response->cookies;
                $cookies_response->add(new \yii\web\Cookie([
                    'name' => 'salon_id',
                    'value' => 2,
                ]));
            };
        }


        $request = Yii::$app->request->get();

        if(empty($request['month_year']))
        {
            $month = intval(date('m'));
            $year = intval(date('Y'));
            $month_year = $month .'.'.$year;
        }
        else
        {
            $month_year = $request['month_year'];
        }

        $firstMonthDay = date('Y-m-d', strtotime("1.{$month_year}"));

        $month_year_ex = explode('.',$month_year);
        $month = $month_year_ex[0];
        $year = $month_year_ex[1];

        $lastday = mktime(0, 0, 0, $month + 1, 1, $year);
        $firstday = mktime(0, 0, 0, $month, 1, $year);

        $searchModel = new OrderSearch();

        $statuses = Order::statuses();

        $orders = Order::find()
            ->where('created_at >= '. $firstday)
            ->andWhere('created_at < '. $lastday)
            ->andWhere(['salon_id'=> $salon_id])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        foreach ($orders as $order)
        {
            $user = User::find()
                ->where(['id' => $order['client_id']])
                ->one();
            $salon = Salon::find()
                ->where(['id' => $salon_id])
                ->one();

            if(is_numeric($order->change_cost))
               $cost =  $order->change_cost;

            if(is_null($order->change_cost))
            {
                $service = SalonService::find()
                    ->where(['id' => $order['salon_service_id']])
                    ->one();
                $cost = $service['cost'];
            }

            $order['group_bonus'] =  self::getGroupBonusFromCostOrder($cost, $user, $month);
            $order['salon_service_id'] = $cost;
            $order['salon_id'] = $salon->name;
            $order['client_id'] = $user->last_name .' '. $user->name;

        }

        $this->view->title = Yii::t('app', 'Record log');
        $this->layout = 'admin/admin';
        return $this->render('index' , [
            'journal' => [
                'statuses' => $statuses,
                'orders' => $orders,
                'searchModel' => $searchModel,
                'prev' => date("m.Y", strtotime($firstMonthDay . " -1 month")),
                'next' => date("m.Y", strtotime($firstMonthDay . " +1 month")),
                'month_year' => $month_year?? date("m.Y"),
                'error_mes' => $error_mes,
            ]
        ]);
    }

    public static function getGroupBonusFromCostOrder($cost, $user, $month)
    {
        if(is_integer($user))
        {
            $user = User::find()->where(['id' => $user])->one();
        }
        $mentor_id = $user->mentor_id;
        if(!$mentor_id) return;

        $bonusUser = Bonus::find()
            ->where(['user_id' => $user->id])
            ->andWhere(['month' => $month])
            ->one();
        $user_qualification = BonusController::getQualification($bonusUser->group, $bonusUser->personal);


        $cost = $cost/150;

        $group_bonus = $cost * 0.2; //Получили BP для ментора
        
        while($mentor_id)
        {
            $bonusMentor = Bonus::find()
                ->where(['user_id' => $mentor_id ])
                ->andWhere(['month' => $month])
                ->one();
            $mentor_qualification =  $bonusMentor->qualification;


            if($mentor_qualification > 1)
            {
                $group_bonus += $cost * \Yii::$app->params['go_k'][$mentor_qualification][$user_qualification];
                $user_qualification = $mentor_qualification;
            }

            $mentor = User::find()
                ->where(['id' => $mentor_id])
                ->one();
            $mentor_id = $mentor->mentor_id;

        }

        return $group_bonus;
    }

}
