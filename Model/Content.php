<?php

namespace Model;

class Content {
    protected $dbo;

    public $title;
    public $json;

    function __construct()
    {
        $this->dbo = \JFactory::getDbo();
    }

    function newContent()
    {
        var_dump($this);
    }
}