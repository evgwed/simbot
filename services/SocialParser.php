<?php

namespace app\services;
use app\models\ParsedUserItem;
use GuzzleHttp\Client;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/***
 * Class SocialParser
 * @package app\services
 *
 * @property string $login
 * @property string $password
 * @property Client $client
 */
class SocialParser
{
    private $login = '';
    private $password = '';

    private $client = null;

    public function __construct(string $baseUri, string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;

        $defaultHeaders = [
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'Connection' => 'keep-alive',
            'Content-Length' => '75',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Host' => 's3n.simbirsoft',
            'Origin' => 'http://s3n.simbirsoft',
            'Referer' => 'http://s3n.simbirsoft/login',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            'X-Compress' => 'null',
            'X-Requested-With' => 'XMLHttpRequest'
        ];

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 60,
            'cookies' => true,
            'defaults' => [

                'headers' => $defaultHeaders,
                'timeout'  => 60,
                'allow_redirects' => false,
                'exceptions'      => true,
                'decode_content'  => true,
                'verify'          => false,
                'config'          => [
                    'curl'        => [
                        CURLOPT_TIMEOUT => 60,
                        CURLOPT_TIMEOUT_MS => 60,
                        CURLOPT_CONNECTTIMEOUT => 60,
                    ]
                ],
            ]
        ]);
    }

    public function login(): bool
    {
        $loginResponse = $this->client->post(
            '/login',
            [
                'form_params' => [
                    '_username'     => $this->login,
                    '_password'     => $this->password,
                    '_remember_me'  => 'on',
                    '_referer'      => ''
                ]
            ]
        );

        return $loginResponse->getStatusCode() === 200;
    }

    public function getUsersId(): array
    {
        $usersId = [];
        $regexp = "/<a href=\"\/user\/(\d+)\">/i";
        $htmlContent = $this->getAllPortions();

        if (preg_match_all($regexp, $htmlContent, $usersId)) {
            $usersId = array_unique($usersId[1]);
            $usersId = array_map(function ($item){
                return intval($item);
            }, $usersId);
        }
        return $usersId;
    }

    protected const REGEXP_USER_NAME = '/class=\"block about-user\".*class=\"name\".*>(.*)<\/div>/siU';
    protected const REGEXP_PHOTO_MIN = '/class=\"user-avatar left-sidebar-avatar\".*img src=\"(.*)\"/siU';
    protected const REGEXP_PHOTO_MAX = '/class=\"user-avatar left-sidebar-avatar\".*img.*img src=\"(.*)\".*id=\"user-image-large\"/siU';

    protected function parseUserDataHtml(int $userId, string $html) : ParsedUserItem
    {

        $userName = '';
        $userNameMatches = [];
        if (preg_match(static::REGEXP_USER_NAME, $html, $userNameMatches) && count($userNameMatches) > 1){
            $userName = trim($userNameMatches[1]);
        }
        unset($userNameMatches);

        $usersPhoto = [];
        $userPhotosMin = [];
        if (preg_match(static::REGEXP_PHOTO_MIN, $html, $userPhotosMin) && count($userPhotosMin) > 1){
            $usersPhoto[] = trim($userPhotosMin[1]);
        }
        unset($userPhotosMin);

        $userPhotosMax = [];
        if (preg_match(static::REGEXP_PHOTO_MAX, $html, $userPhotosMax) && count($userPhotosMax) > 1){
            $usersPhoto[] = trim($userPhotosMax[1]);
        }
        unset($userPhotosMax);

        if (!empty($userName)) {
            return new ParsedUserItem($userId, $userName, $usersPhoto);
        } else {
            throw new HttpException(500, 'Ошибка обработки html документа.');
        }
    }

    public function getUserData(int $userId) : ParsedUserItem
    {
        $userDataRequest = $this->client->get('/user/' . $userId);
        if ($userDataRequest->getStatusCode() === 200){
            return $this->parseUserDataHtml($userId, $userDataRequest->getBody()->getContents());
        } else {
            throw new NotFoundHttpException('User not fount: ' . $userId, 404);
        }
    }

    public function getUsersData() : array
    {
        $usersIdList = $this->getUsersId();
        $usersDataList = [];

        foreach ($usersIdList as $userId) {
            $usersDataList[] = $this->getUserData($userId);
            usleep(500);
        }

        return $usersDataList;
    }

    protected function getAllPortions() : string
    {
        $content = '';
        $portionItem = null;
        $offset = 0;

        do {
            $portionItem = $this->getPortion($offset);

            $content .= $portionItem['view'];

            $offset += 25;

            usleep(500);
        } while(!isset($portionItem['stop']));

        return $content;
    }

    protected function getPortion(int $offset = 0)
    {
        $usersPortionRequest = $this->client->post(
            '/user/getPortion',
            [
                'form_params' => [
                    'offset' => $offset,
                    'term'   => ''
                ],
            ]
        );

        $responseContent = $usersPortionRequest->getBody()->getContents();

        return \GuzzleHttp\json_decode($responseContent, true);
    }
}