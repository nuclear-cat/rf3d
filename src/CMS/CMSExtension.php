<?php

namespace Bundle\CMS;

use Bolt\Extension\SimpleExtension;
use Bundle\CMS\Controller\ContentController;
use Silex\Application;

class CMSExtension extends SimpleExtension
{
    /** @var Application */
    private $app;

    public function boot(Application $app)
    {
        parent::boot($app);

        $this->app = $app;
    }

    protected function registerBackendControllers()
    {
        return [
            '/editcontent' => new ContentController(),
        ];
    }

    protected function registerTwigFunctions()
    {
        return [
            'cities' => 'getCities',
        ];
    }

    public function getCities()
    {
        $cities = $this->getContainer()['config']->get('taxonomy')['cities']['options'];

        asort($cities);

        return $cities;
    }
}
