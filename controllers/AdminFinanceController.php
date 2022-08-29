<?php

namespace frontend\controllers;


use common\models\Order;
use common\models\PaidBySalon;
use common\models\PayBonusSmsCode;
use common\models\Salon;
use common\models\User;
use console\controllers\BonusController;
use Yii;

class AdminFinanceController extends \yii\web\Controller
{
    public function actionIndex()
    {
        if (!User::canAdminPermission()) {
            return $this->redirect(['client/main']);
        }

        $searchModel = new PaidBySalon();


        if(Yii::$app->request->isAjax){

            $post = Yii::$app->request->post();
            $salon_exist = Salon::find()->where(['id' => $post['PaidBySalon']['salon_id']])->one();

            if($salon_exist)
            {
                $res_array = [];
                //считаем group_bonus и ЛО

                $year = substr($post['PaidBySalon']['date'], 3,7);

                if(substr($post['PaidBySalon']['date'], 0,1) == 0 && substr($post['PaidBySalon']['date'], 1,2) != 9)
                {
                   $endmonth = '0' . (intval(substr($post['PaidBySalon']['date'], 1,2)) + 1) ;
                }
                if(substr($post['PaidBySalon']['date'], 0,2) == 10 || substr($post['PaidBySalon']['date'], 0,2) == 11)
                {
                    $endmonth = intval(substr($post['PaidBySalon']['date'], 0,2)) + 1;
                }
                if(substr($post['PaidBySalon']['date'], 0,2) == 12)
                {
                    $endmonth = '12';
                }
                if(substr($post['PaidBySalon']['date'], 0,2) == 9)
                {
                    $endmonth = '10';
                }

                $orders = Order::find()
                    ->where(['salon_id' => $post['PaidBySalon']['salon_id']])
                    ->andWhere(['status' => 2])
                    ->andWhere(['>=', 'date', $year."-".substr($post['PaidBySalon']['date'], 0,2)."-01"])
                    ->andWhere(['<', 'date', $year."-".$endmonth."-01"])
                    ->all();

                $lo_and_go = BonusController::calculateAllLoAndGoBonus($orders);
                $group_bonus = $lo_and_go['group_bonus'][0];
                $lo_bonus = $lo_and_go['lo_bonus'][0];

                $res_array['lo_bonus'] = round(((intval($lo_bonus) / 150) *0.2)*35, 2);
                $res_array['group_bonus'] = round($group_bonus * 35, 2);
                $res_array['count'] = count($orders);

                //Получаем имя салона

                $salon = Salon::find()->where(['id'=> $post['PaidBySalon']['salon_id']])->one();
                $res_array['salon_name'] = $salon->name;

                $month = Yii::t('app', ucfirst(date("F", strtotime("1.{$post['PaidBySalon']['date']}"))));
                $res_array['month'] = $month;

                if(!empty($post['PaidBySalon']['paid_LO']) || !empty($post['PaidBySalon']['paid_GO']))
                {
                    if(empty($post['PaidBySalon']['paid_LO'])) $post['PaidBySalon']['paid_LO'] = 0;
                    if(empty($post['PaidBySalon']['paid_GO'])) $post['PaidBySalon']['paid_GO'] = 0;


                    $paydBySalon = new PaidBySalon();
                    $paydBySalon->paid_LO = (!empty($post['PaidBySalon']['paid_LO']))? $post['PaidBySalon']['paid_LO'] : 0;
                    $paydBySalon->paid_GO = (!empty($post['PaidBySalon']['paid_GO']))? $post['PaidBySalon']['paid_GO'] : 0;
                    $paydBySalon->salon_id = $post['PaidBySalon']['salon_id'];
                    $paydBySalon->date = $year."-".substr($post['PaidBySalon']['date'], 0,2)."-01" ;
                    if($paydBySalon->save())
                        $res_array['mes_paid'] = 1;
                }

                //списано салоном баллов
                $firstMonthDay = $year."-".substr($post['PaidBySalon']['date'], 0,2)."-01";
                $endMonthDay = $year."-".$endmonth. ((substr($post['PaidBySalon']['date'], 0,2) == 12)? "-31": "-01");

                $paydBySalon = SalonFinanceController::getSummPaydBySalon($post['PaidBySalon']['salon_id'], $firstMonthDay, $endMonthDay);
                $res_array['paid_LO'] = $paydBySalon['paid_LO'];
                $res_array['paid_GO'] = $paydBySalon['paid_GO'];

                $res_array['salon_used_bonus'] = SalonFinanceController::getSalonUsedBonus($post['PaidBySalon']['salon_id'], $firstMonthDay, $endMonthDay);

                $res_array['total'] = round(($res_array['lo_bonus'] - $res_array['salon_used_bonus'] + $res_array['group_bonus']) - ($res_array['paid_LO'] + $res_array['paid_GO']), 2);

                return json_encode($res_array);

            }else{
                $res_array['salon_not_exist'] = 1;
                return json_encode($res_array);
            }

        }

        $this->layout = 'admin/admin';
        return $this->render('index', [
            'searchModel' => $searchModel,
            'month_year' => $month_year?? date("m.Y"),
        ]);
    }

}
