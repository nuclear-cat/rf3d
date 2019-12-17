<?php
namespace Bundle\Site\Controller\Frontend;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Drop Bear routes.
 *
 * @author Kenny Koala <kenny@dropbear.com.au>
 */
class PlaceController extends Base
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/test/category/{category}/district/{district}', [$this, 'places']);

        return $c;
    }

    /**
     * @param Request $request
     * @param string  $type
     *
     * @return Response
     */
    public function places(Request $request, $category, $district)
    {
//        dump($category, $district); die;
        return new Response('Koala in a tree!', Response::HTTP_OK);
    }
}