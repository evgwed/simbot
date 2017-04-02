<?php

namespace app\models;

/***
 * Class ParsedUserItem
 * @package app\models
 *
 * @property int $id
 * @property string $name
 * @property array $imagesUrl
 */
class ParsedUserItem
{
    public $id = 0;
    public $name = '';
    public $imagesUrl = [];

    public function __construct(int $id, string $name, array $imagesUrl)
    {
        $this->id = $id;
        $this->name = $name;
        $this->imagesUrl = $imagesUrl;
    }
}