<?php

namespace Bundle\Site\Entity;

use Bolt\Storage\Entity\Content;


class ContactMessage extends Content
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}