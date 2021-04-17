<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Bundle\Site\Command\PlacesResortCommand;
use Bundle\Site\Controller\Backend\DistrictListingController;
use Bundle\Site\Entity\Category;
use Bundle\Site\Entity\Place;
use Bundle\Site\Repository\CategoryRepository;
use Bundle\Site\Repository\PlaceRepository;
use Pimple as Container;
use Silex\Application;

class CustomisationExtension extends SimpleExtension
{
    private $app;

    protected function registerRepositoryMappings()
    {
        return [
            'places' => [ Place::class => PlaceRepository::class ],
            'categories' => [ Category::class => CategoryRepository::class ],
        ];
    }

    public function boot(Application $app)
    {
        parent::boot($app);
        $this->app = $app;
    }

    protected function registerNutCommands(Container $container)
    {
        return [
            new PlacesResortCommand()
        ];
    }
    protected function registerTwigFunctions()
    {
        return [
            'menuItems' => 'getMenuItems',
            'getPlacesByCity' => 'getPlacesByCity',
            'getPlaces' => 'getPlaces'
        ];
    }

    public function getPlaces($citySlug = null, $categorySlug = null, $districtSlug = null, $page = 1, $limit = 10, $onlyMainPage = false)
    {
        /** @var \Bundle\Site\Repository\PlaceRepository $placesRepo */
        $placesRepo = $this->app['storage']->getRepository('places');
        $places = $placesRepo->getPlacesByCity($citySlug, $categorySlug, $districtSlug, $page, $limit, $onlyMainPage);

        return $places;
    }

    protected function registerBackendControllers()
    {
        return [
            '/overview/districts' => new DistrictListingController(),
        ];
    }

    protected function registerFrontendControllers()
    {
        return [
            '/' => new \Bundle\Site\Controller\Frontend\PlaceController(),
            '/contact' => new \Bundle\Site\Controller\Frontend\ContactController(),
        ];
    }

    public function getPlacesByCity($cityName)
    {
        /** @var \Bolt\Storage\Database\Connection $dbConnection */
        $dbConnection = $this->app['db'];

        $dbConnection->query('SET SQL_BIG_SELECTS = 1');

        $stmt = $dbConnection->prepare("
            SELECT p.id place_id, p.title, dr.to_id, d.id AS did, d.title dtitle, t.slug
            
            FROM bolt_places p
            
            # District relations
            JOIN bolt_relations dr ON ((dr.from_id = p.id AND dr.from_contenttype = 'places' AND dr.to_contenttype = 'districts') OR (dr.to_id = p.id AND dr.to_contenttype = 'places' AND dr.from_contenttype = 'districts'))
            
            # Disctricts
            JOIN bolt_districts d ON (d.id = dr.to_id AND dr.to_contenttype = 'districts' AND dr.from_contenttype = 'places')

            # District taxonomies
            LEFT JOIN bolt_taxonomy t ON (t.content_id = d.id AND t.contenttype = 'districts' AND t.taxonomytype = 'cities')
            
            WHERE t.slug = :cityName
        ");
        $stmt->bindValue('cityName', $cityName);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $ids = [];


        foreach ($result as $row) {
            $ids[] = $row['place_id'];
        }

        return $ids;
    }

    public function getMenuItems($cityName = null)
    {
        /** @var \Bolt\Storage\Database\Connection $dbConnection */
        $dbConnection = $this->app['db'];

        $dbConnection->query('SET SQL_BIG_SELECTS = 1');

        $stmt = $dbConnection->prepare("
            SELECT
                c.id category_id,
                c.title category_title,
                c.slug category_slug,
                c.sort category_sort_order,

                pr.id p_relation_id,
                pr.to_id p_relation_to_id,
                pr.from_id p_relation_from_id,

                dr.id d_relation_id,

                p.title place_title,
                p.id place_id,

                d.id district_id,
                d.title district_title,
                d.slug district_slug

            FROM bolt_categories c            

            LEFT JOIN bolt_relations pr ON ((pr.to_id = c.id AND pr.to_contenttype = 'categories' AND pr.from_contenttype = 'places') OR (pr.from_id = c.id AND pr.from_contenttype = 'categories' AND pr.to_contenttype = 'places'))
            LEFT JOIN bolt_places p ON ((pr.from_id = p.id AND pr.from_contenttype = 'places' AND pr.to_contenttype = 'categories') OR (pr.to_id = p.id AND pr.to_contenttype = 'places' AND pr.from_contenttype = 'categories'))

            LEFT JOIN bolt_taxonomy cc ON (cc.content_id = c.id AND cc.contenttype = 'categories' AND cc.taxonomytype = 'cities')
            LEFT JOIN bolt_taxonomy dc ON (dc.content_id = c.id AND dc.contenttype = 'places' AND dc.taxonomytype = 'cities')
            
            LEFT JOIN bolt_relations dr ON ((dr.from_id = p.id AND dr.from_contenttype = 'places' AND dr.to_contenttype = 'districts') OR (dr.to_id = p.id AND dr.to_contenttype = 'places' AND dr.from_contenttype = 'districts'))
            LEFT JOIN bolt_districts d ON (d.id = dr.to_id AND dr.to_contenttype = 'districts' AND dr.from_contenttype = 'places')

            " . ($cityName ? 'WHERE cc.slug = :cityName' : '') . "
            
            ORDER BY  c.sort ASC, d.sort ASC
        ");

        if ($cityName) {
            $stmt->bindValue('cityName', $cityName);
        }

        $stmt->execute();
        $result = $stmt->fetchAll();

        $categories = [];
        $categoryKeys = [];

        foreach ($result as $row) {

            if ($row['district_id'] > 0) {
                $categories[$row['category_id']]['districts'][$row['district_id']] = [
                    'id' => $row['district_id'],
                    'title' => $row['district_title'],
                    'slug' => $row['district_slug'],
                ];
            }
            $categories[$row['category_id']]['id'] = $row['category_id'];
            $categories[$row['category_id']]['title'] = $row['category_title'];
            $categories[$row['category_id']]['slug'] = $row['category_slug'];
            $categories[$row['category_id']]['sort_order'] = $row['category_sort_order'];
        }

        return $categories;
    }

    //https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms
    protected function registerTwigPaths()
    {
        if ($this->getEnd() == 'backend') {
            return [
                'view' => ['position' => 'prepend', 'namespace' => 'bolt']
            ];
        }
        return [];
    }

    private function getEnd()
    {
        $backendPrefix = $this->container['config']->get('general/branding/path');
        $end = $this->container['config']->getWhichEnd();

        switch ($end) {
            case 'backend':
                return 'backend';
            case 'async':
                // we have async request
                // if the request begin with "/admin" (general/branding/path)
                // it has been made on backend else somewhere else
                $url = '/' . ltrim($_SERVER['REQUEST_URI'], $this->container['paths']['root']);
                $adminUrl = '/' . trim($backendPrefix, '/');
                if (strpos($url, $adminUrl) === 0) {
                    return 'backend';
                }
            default:
                return $end;
        }
    }
}
