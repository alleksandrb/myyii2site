<?php

namespace frontend\controllers;

use common\models\Bonus;
use common\models\User;
use console\controllers\BonusController;
use Faker\Factory;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * AdminStructureController.
 */
class AdminStructureController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Service models.
     * @param null $id
     * @return mixed
     */
    public function actionIndex($id = null)
    {
        if (!User::canAdminPermission()) {
            return $this->redirect(['client/main']);
        }

        $breadcrumbsArr = [];
        $month = intval(date('m'));
        $year = intval(date('Y'));

        if ($this->request->isGet) {
            $idTemp = $this->request->get('idTemp');
            $phone = $this->request->get('phone');
        }

        if (intval($idTemp) || trim($phone)) {
            $clients = User::find()->where([
                'OR',
                intval($idTemp)? ['id' => $idTemp]:[],
                trim($phone)? ['like', 'phone', '%' . $phone . '%', false]: []
            ])->all();

        } else {
            if ($id) {
                $currentUser = User::findByID($id);

                $breadcrumbsArr[] = [
                    'id' => $currentUser->id,
                    'title' => "{$currentUser->last_name} {$currentUser->name} {$currentUser->second_name}"
                ];

                if ($currentUser->mentor) {
                    $breadcrumbsArr = User::recursiveGetMentor($currentUser, $breadcrumbsArr);
                }
            }
            $dateArray = [$month, $year];

            $clients = User::find()
                ->joinWith(['bonuses' => function($q) use ($dateArray) {
                    $q->onCondition(['bonus.month' => $dateArray[0], 'bonus.year' => $dateArray[1]]);
                }])
                ->joinWith(['partners partner' => function($q) {
                    $q->onCondition(['partner.status' => User::STATUS_ACTIVE]);
                }])
                ->where(['user.mentor_id' => $id])
                ->andWhere(['user.status' => User::STATUS_ACTIVE])
                ->all();
        }

        

        $this->layout = 'admin/admin';
        return $this->render('index', [
            'clients' => $clients,
            'breadcrumbsArr' => array_reverse($breadcrumbsArr)
        ]);
    }


    /**
     * Оплачиваем кэшбэк клиенту
     */

    public function actionPayBonuses()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $responseArr = [
            'status' => 'error'
        ];


        if (Yii::$app->request->isAjax) 
        {
            $month = intval(date('m'));

            $userCount = Yii::$app->request->post('count');
            $id = Yii::$app->request->post('id');

            if ($id && !User::findOne($id)) 
            {       
                $responseArr['message'] = 'ID отсутствует в базе';
                return $responseArr;
            }

            $userBonus = Bonus::find()
                ->andWhere(['user_id' => $id])
                ->andWhere(['month' => $month])
                ->one();
            

            $old_used =  $userBonus->personal_used;
            $userBonus->personal_used = $old_used + $userCount;
            $userBonus->save(); 
            
            $responseArr['status'] = 'success';
        }


        return $responseArr;

    }  

    /**
     * Displays a single Service model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!User::canAdminPermission()) {
            return $this->redirect(['client/main']);
        }

        $this->layout = 'admin/admin';
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Generate fake users.
     *
     * @return mixed
     */
    public function actionGenerateUsers()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $responseArr = [
            'status' => 'error'
        ];

        if (Yii::$app->request->isAjax) {
            $userCount = Yii::$app->request->post('count');
            $id = Yii::$app->request->post('id');

            if ($id && !User::findOne($id)) {
                $responseArr['message'] = 'ID указанный как родительский ID генерируемой струтуры отсутствует в базе';

                return $responseArr;
            }

            $faker = Factory::create("ru_RU");

            if ($userCount) {
                for ($i = $userCount; $i > 0; $i--) {
                    $gender = $faker->randomDigit() === 9? User::USER_GENDER_MALE: User::USER_GENDER_FEMALE;

                    $user = new User();
                    $fio = explode(" ", $faker->name($gender === User::USER_GENDER_FEMALE? 'female': 'male'));
                    $birthday = $faker->dateTimeBetween('-45 years', '-18 years');

                    if ($id) {
                        $userIds = User::getAllPartnerIds($id);
                        $userIds = array_unique($userIds);
                    } else {
                        $userIds = ArrayHelper::getColumn(User::find()->where(['is not', 'mentor_id', new Expression('null')])->all(), 'id');
                    }

                    $user->auth_key = 'Utp7dfCjJF4-e4EkIh9CEjrmNd9_osOI';
                    $user->password_hash = '$2y$13$VNcUH/RKwj/88tt6waVO8OXtNZAt6yy5JcSPEn73byVfvxuKx7KXG';
                    $user->verification_token = 'L3Af6lM4ze1vY3adtPOR4DlJJ_poMCtw_1635580726';

                    $user->name = $fio[0];
                    $user->second_name = $fio[1];
                    $user->last_name = $fio[2];

                    $user->email = $faker->unique()->email();
                    $user->phone = "7" . $faker->numerify('##########');
                    $user->birthday = strtotime($birthday->format('Y-m-d'));
                    $user->gender = $gender;
                    $user->status = User::STATUS_ACTIVE;
                    $user->mentor_id = $faker->randomDigit() > 7? ($id?? 1): $faker->randomElement($userIds)?? ($id?? 1);
                    $user->is_email_confirmed = User::EMAIL_CONFIRMED;
                    $user->is_phone_confirmed = User::PHONE_CONFIRMED;
                    $user->created_at = 1635580726;
                    $user->updated_at = 1635580726;

                    $user->save();
                }

                $responseArr['status'] = 'success';
            }
        }

        return $responseArr;
    }

    /**
     * Generate fake user bonuses.
     *
     * @return mixed
     */
    public function actionGenerateUsersBonus()
    {
	return 'not work';
        Yii::$app->response->format = Response::FORMAT_JSON;
        $responseArr = [
            'status' => 'error'
        ];

        if (Yii::$app->request->isAjax) {
            $faker = Factory::create("ru_RU");

            $currentMonth = date('m');
            $currentYear = date('Y');
            $users = User::find()->where(['status' => User::STATUS_ACTIVE])->all();

            foreach ($users as $user) {
                if ($faker->randomDigit() > 6) {
                    $personalPoints = $faker->numberBetween(30, 200);
                } else {
                    $personalPoints = $faker->numberBetween(15, 30);
                }

                $userBonus = Bonus::find()
                    ->where(['user_id' => $user->id])
                    ->andWhere(['month' => intval($currentMonth)])
                    ->andWhere(['year' => intval($currentYear)])
                    ->one();

                if (!$userBonus)
                    $userBonus = new Bonus();

                $userBonus->user_id = $user->id;
                $userBonus->personal = $personalPoints;
                $userBonus->month = intval($currentMonth);
                $userBonus->year = intval($currentYear);
                $userBonus->save();

            }

            $responseArr['status'] = 'success';

        }

        return $responseArr;
    }




    /**
     * Calculate bonus.
     *
     * @return mixed
     */





     public function actionCalculateBonus()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $responseArr = [
            'status' => 'error'
        ];

        if (Yii::$app->request->isAjax) {
            $month = intval(date('m'));
            $year = intval(date('Y'));

            $bonusUsers = Bonus::find()
                ->innerJoinWith(['user.partners'])
                ->andWhere(['bonus.month' => $month])
                ->andWhere(['bonus.year' => $year])
                ->andWhere(['user.status' => User::STATUS_ACTIVE])
                ->orderBy(['user.id' => SORT_DESC])
                ->all();

            //Проверка все ли юзеры есть в таблице бонус     
            $users = User::find()->all();
            foreach($users as $user)
            {
                $exist = 0;
                foreach($bonusUsers as $bonusUser)
                {
                    if($user->id == $bonusUser->user_id)
                    {
                        $exist = 1;  
                    } 
                }

                if($exist == 0)
                {
                    $newBonus = new Bonus();
                    $newBonus->user_id = $user->id;
                    $newBonus->month = $month;
                    $newBonus->year = $year;
                    $newBonus->personal = 0;
                    $newBonus->save();
                }
            }   

            if ($bonusUsers) {
                foreach ($bonusUsers as $key => $bonusUser) 
                {
                    $partners = $bonusUser->user->partners;
                    $bonusUser->group = $bonusUser->personal;
                    $bonusUser->partner_bonus = null;
                    $bonusUser->group_ye = null;

            
                    $bonus_partners = BonusController::getBonusPartners($partners, $bonusUsers);


                    if ($bonus_partners)
                    {

                        foreach ($bonus_partners as $bonus_partner) 
                        {

                            foreach($partners as $partner)
                            {   
                                if($partner->id == $bonus_partner->user_id)
                                {
                                    if($bonusUser->user_id == $partner->mentor->id)
                                    {
                                        $bonusUser->partner_bonus += $bonus_partner->personal;
                                        $bonusUser->group += $bonus_partner->group;
                                      
                                    }    
                                }
                                
                            }
                                 
                        }

                    }
                    

                    $bonusUser->qualification = BonusController::getQualification($bonusUser->group, $bonusUser->personal);

                    $bokovik = $bonusUser->personal;
                    foreach($partners as $partner)
                    {
                        $bonusPartners = Bonus::find()
                            ->where(['user_id' => $partner->id])
                            ->andWhere(['bonus.month' => $month])
                            ->andWhere(['bonus.year' => $year])
                            ->one();
                        if ($bonusPartners->qualification < 5) 
                        {
                            $bokovik += $bonusPartners->group;
                        }
                    }
                    $bonusUser->bokovik = $bokovik;


                  
                    if ($bonusUser->personal >= 0) 
                    {
                 
                        $bonusUser->group_ye = $bonusUser->partner_bonus * \Yii::$app->params['bp_k'];
               
                        if ($bonusUser->qualification >= Bonus::QUALIFICATION_SP && count($bonus_partners)) 
                        {

                            foreach ($bonus_partners as $bonus_partner) 
                            {
                                $partnerQualification = BonusController::getQualification($bonus_partner->group, $bonus_partner->personal);


                                if($partnerQualification < $bonusUser->qualification && $bonus_partner->personal < 20)
                                {
                                    $bonusUser->group_ye += $bonus_partner->personal * \Yii::$app->params['go_k'][$bonusUser->qualification][$partnerQualification];
                                }

                                if ($partnerQualification < $bonusUser->qualification && $bonus_partner->personal >= 20) 
                                {
                                    $bonusUser->group_ye += $bonus_partner->group * \Yii::$app->params['go_k'][$bonusUser->qualification][$partnerQualification];
                                 
                                } elseif ($bonusUser->qualification === Bonus::QUALIFICATION_BP && $partnerQualification === Bonus::QUALIFICATION_BP) {
                                    continue; //TODO
                                }

                            }

                        }
                    }

                    $bonusUser->save();
                }  //endforeach

                $responseArr['status'] = 'success';
            }
        }

        AdminStructureController::calculateSecondBlock($bonusUsers);

        return $responseArr;
    }



    private function calculateSecondBlock($bonusUsers)
    {

        foreach($bonusUsers as $bonusUser)
        {
            if($bonusUser->qualification < 5) continue;

            $user_id = $bonusUser->user_id;
            $partners = $bonusUser->user->partners;

            $all_partners = BonusController::getAllPartners($partners);

            $christmas_tree = BonusController::getPartnersTree($all_partners,$user_id);


            $count = 0;
            foreach($christmas_tree as $key => $bokovik)
            {   
                if(substr($key, -1) == 1 && $bokovik != 0)
                {
                    $count++;
                }
            }

            if($count != 0)
                $bonusUser->qualification = BonusController::getSideQualification($count); 

            $dive_lvl = $count + 1;

            if($count == 1) $bokovik_limit = 2500;
            if($count == 2) $bokovik_limit = 2000;
            if($count == 3) $bokovik_limit = 1500;
            if($count == 4) $bokovik_limit = 1500;
            if($count == 5) $bokovik_limit = 1500;
            if($count > 5) $bokovik_limit = 1000;

            if($bonusUser->bokovik >= $bokovik_limit)
            {    
                $group_BP = 0; 


                foreach($christmas_tree as $key => $bokovik_partner)
                {
                    if(substr($key, -1) <= $dive_lvl)
                    {

                      $deep = substr($key, -1);

                      if ($deep == 1)
                      { 
                        $group_BP += $bokovik_partner * 0.07;                                  
                      }

                      if ($deep == 2)
                      {                        
                        $group_BP += $bokovik_partner * 0.06;                                  
                      }

                      if ($deep == 3)
                      { 
                        $group_BP += $bokovik_partner * 0.05;                                  
                      }

                      if ($deep == 4)
                      {  
                        $group_BP += $bokovik_partner * 0.04;                                  
                      }

                      if ($deep == 5)
                      {
                        $group_BP += $bokovik_partner * 0.03;                                  
                      }

                      if ($deep >= 6)
                      {
                        $group_BP += $bokovik_partner * 0.02;                                  
                      }
                   
                    }

                } //endforeach


                $bonusUser->group_BP = $group_BP;
                $bonusUser->save();
            }      
 
        }

        $responseArr['status'] = 'success';        
    
        return $responseArr;
    }
       

}
