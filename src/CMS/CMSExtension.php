<?php

namespace Bundle\CMS;

use Bolt\Extension\SimpleExtension;
use Bundle\CMS\Controller\ContentController;

class CMSExtension extends SimpleExtension
{
    protected function registerBackendControllers()
    {
        return [
            '/editcontent' => new ContentController(),
        ];
    }
}
