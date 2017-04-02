<?php
namespace tests\services;

use app\models\ParsedUserItem;
use app\services\SocialParser;

/***
 * Class SocialParserTest
 * @package tests\services
 *
 * @property SocialParser $testModel
 */
class SocialParserTest extends \Codeception\Test\Unit
{
    /** @var SocialParser */
    protected $testModel;

    protected function setUp()
    {
        $base_uri = \Yii::$app->params['social_base_uri'];
        $social_login = \Yii::$app->params['social_login'];
        $social_password = \Yii::$app->params['social_password'];

        $this->testModel = new SocialParser($base_uri, $social_login, $social_password);
        return parent::setUp();
    }

    public function testGetUsersId() : void
    {
        $result = null;

        if ($this->testModel->login()) {
            $result = $this->testModel->getUsersId();
        }

        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result) > 0);
        $this->assertTrue(is_int($result[0]));
    }

    public function testGetUsersData() : void
    {
        $result = null;

        if ($this->testModel->login()) {
            $result = $this->testModel->getUsersData();
        }

        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($result[0] instanceof ParsedUserItem);
    }
}
