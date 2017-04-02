<?php

namespace app\commands;

use app\services\SocialParser;
use yii\console\Controller;

class ParserController extends Controller
{
    public function actionParse()
    {
        $base_uri = \Yii::$app->params['social_base_uri'];
        $social_login = \Yii::$app->params['social_login'];
        $social_password = \Yii::$app->params['social_password'];

        $parser = new SocialParser($base_uri, $social_login, $social_password);

        if ($parser->login()) {

        } else {
            $this->stderr('Ошибка авторизации.' . PHP_EOL);
        }
    }
}
