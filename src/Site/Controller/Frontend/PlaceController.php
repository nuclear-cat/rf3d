<?php
namespace Bundle\Site\Controller\Frontend;

use Bolt\Controller\Base;
use Bolt\Controller\ConfigurableBase;
use Bolt\Storage\Database\Schema\Table\ContentType;
use Bolt\Storage\Entity\Content;
use Bundle\Site\Entity\Place;
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
class PlaceController extends ConfigurableBase
{
    protected function getConfigurationRoutes()
    {
        return $this->app['config']->get('routing', []);
    }

    /**
     * Return the listing order.
     *
     * If the ContentType's sort is false (default in Config::parseContentType),
     * either:
     *  - we let `getContent()` sort by itself
     *  - we explicitly set it to sort on the general/listing_sort setting
     *
     * @param ContentType|array $contentType
     *
     * @return null|string
     */
    private function getListingOrder($contentType)
    {
        // An empty default isn't set in config yet, arrays got to hate them.
        if (isset($contentType['taxonomy'])) {
            $taxonomies = $this->getOption('taxonomy');
            foreach ($contentType['taxonomy'] as $taxonomyName) {
                if ($taxonomies[$taxonomyName]['has_sortorder']) {
                    // Let getContent() handle it
                    return null;
                }
            }
        }

        return $this->getOption('theme/listing_sort') ?: $this->getOption('general/listing_sort');
    }

    /**
     * Returns an array of the parameters used in getContent for listing pages.
     *
     * @param string $contentTypeSlug The content type slug
     * @param bool   $allowViewless   Allow viewless contenttype
     *
     * @return array Parameters to use in getContent
     */
    private function getListingParameters($contentTypeSlug, $allowViewless = false)
    {
        $contentType = $this->getContentType(current(explode('/', $contentTypeSlug)));

        // If there is no ContentType, don't get parameters for it
        if ($contentType === false) {
            return [];
        }

        // If the ContentType is 'viewless', don't show the listing / record page.
        if ($contentType['viewless'] && !$allowViewless) {
            $this->abort(Response::HTTP_NOT_FOUND, 'Page ' . $contentType['slug'] . ' not found.');
        }

        // Build the pager
        $page = $this->app['pager']->getCurrentPage($contentType['slug']);
        $order = isset($contentType['listing_sort']) ? $contentType['listing_sort'] : $this->getListingOrder($contentType);

        // CT value takes precedence over theme & config.yml
        if (!empty($contentType['listing_records'])) {
            $amount = $contentType['listing_records'];
        } else {
            $amount = $this->getOption('theme/listing_records') ?: $this->getOption('general/listing_records');
        }

        return ['limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true];
    }


    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/category/{categorySlug}', [$this, 'listingPlacesByCategory']);
        $c->match('/district/{districtSlug}', [$this, 'listingPlacesByDistrict']);
        $c->match('/district/{districtSlug}/category/{categorySlug}', [$this, 'listingPlacesByDistrictAndCategory']);
        $c->match('/city/{citySlug}', [$this, 'listingPlacesByCity']);
        return $c;
    }

    public function listingPlacesByDistrictAndCategory(Request $request, $citySlug = null, $districtSlug, $categorySlug)
    {
        /** @var \Bundle\Site\Repository\PlaceRepository $placesRepo */
        $placesRepo = $this->app['storage']->getRepository('places');
        /** @var \Bolt\Pager\PagerManager $pagerManager */
        $pagerManager = $this->app['pager'];
        /** @var \Bolt\Pager\Pager $pager */
        $pager = $pagerManager->createPager();

        $page = $request->query->get('page');
        $page = $page > 0 ? $page : 1;

        /** @var \Bolt\Storage\Repository\ContentRepository $categoriesRepo */
        $districtRepo = $this->app['storage']->getRepository('districts');

        /** @var \Bolt\Storage\Repository\ContentRepository $categoriesRepo */
        $categoriesRepo = $this->app['storage']->getRepository('categories');

        /** @var Content $category */
        $district = $districtRepo->findOneBy([ 'slug' =>  $districtSlug ]);

        /** @var Content $category */
        $category = $categoriesRepo->findOneBy([ 'slug' =>  $categorySlug ]);

        if (!$district) {
            $this->abort(Response::HTTP_NOT_FOUND, "District {$districtSlug} not found.");
        }

        if (!$category) {
            $this->abort(Response::HTTP_NOT_FOUND, "Category {$categorySlug} not found.");
        }

        foreach ($district->getTaxonomy() as $taxonomy) {
            if ($taxonomy->taxonomytype == 'cities') {
                $citySlug = $taxonomy->slug;
            }
        }

        foreach ($category->getTaxonomy() as $taxonomy) {
            if ($taxonomy->taxonomytype == 'cities') {
                $citySlug = $taxonomy->slug;
            }
        }

        $limit = 10;
        $totalPlaces = $placesRepo->getTotalPlacesByCity(null, $categorySlug, $districtSlug);
        $places = $placesRepo->getPlacesByCity(null, $categorySlug, $districtSlug, $page, $limit);
        $pager->setCount($totalPlaces);
        $pager->setCurrent($page);
        $pager->setTotalpages(ceil($totalPlaces / $limit));

        return $this->render('city_district_and_category.twig', [], [
            'district'  => $district,
            'category'  => $category,
            'city' => $citySlug,
            'places' => $places,
            'pager' => $pager
        ]);
    }

    public function listingPlacesByDistrict(Request $request, $districtSlug)
    {
        $citySlug = null;

        /** @var \Bundle\Site\Repository\PlaceRepository $placesRepo */
        $placesRepo = $this->app['storage']->getRepository('places');
        /** @var \Bolt\Pager\PagerManager $pagerManager */
        $pagerManager = $this->app['pager'];
        /** @var \Bolt\Pager\Pager $pager */
        $pager = $pagerManager->createPager();

        $page = $request->query->get('page');
        $page = $page > 0 ? $page : 1;


        /** @var \Bolt\Storage\Repository\ContentRepository $categoriesRepo */
        $districtRepo = $this->app['storage']->getRepository('districts');

        /** @var Content $category */
        $district = $districtRepo->findOneBy([ 'slug' =>  $districtSlug ]);

        if (!$district) {
            $this->abort(Response::HTTP_NOT_FOUND, "District {$districtSlug} not found.");
        }

        foreach ($district->getTaxonomy() as $taxonomy) {
            if ($taxonomy->taxonomytype == 'cities') {
                $citySlug = $taxonomy->slug;
            }
        }


        $limit = 10;
        $totalPlaces = $placesRepo->getTotalPlacesByCity(null, null, $districtSlug);
        $places = $placesRepo->getPlacesByCity(null, null, $districtSlug, $page, $limit);
        $pager->setCount($totalPlaces);
        $pager->setCurrent($page);
        $pager->setTotalpages(ceil($totalPlaces / $limit));


        return $this->render('city_district.twig', [], [
            'district'  => $district,
            'city' => $citySlug,
            'places' => $places,
            'pager' => $pager
        ]);
    }

    public function listingPlacesByCategory(Request $request, $categorySlug = null)
    {
        /** @var \Bundle\Site\Repository\PlaceRepository $placesRepo */
        $placesRepo = $this->app['storage']->getRepository('places');
        /** @var \Bolt\Pager\PagerManager $pagerManager */
        $pagerManager = $this->app['pager'];
        /** @var \Bolt\Pager\Pager $pager */
        $pager = $pagerManager->createPager();

        $page = $request->query->get('page');
        $page = $page > 0 ? $page : 1;
        $citySlug = null;
        $category = null;


        /** @var \Bolt\Storage\Repository\ContentRepository $categoriesRepo */
        $categoriesRepo = $this->app['storage']->getRepository('categories');

        /** @var Content $category */
        $category = $categoriesRepo->findOneBy([ 'slug' =>  $categorySlug ]);

        if ($categorySlug != null && !$category) {
            $this->abort(Response::HTTP_NOT_FOUND, "Category {$categorySlug} not found.");
        }

        $citySlug = null;
        foreach ($category->getTaxonomy() as $taxonomy) {
            if ($taxonomy->taxonomytype == 'cities') {
                $citySlug = $taxonomy->slug;
            }
        }

        $limit = 10;
        $totalPlaces = $placesRepo->getTotalPlacesByCity(null, $categorySlug, null);
        $places = $placesRepo->getPlacesByCity(null, $categorySlug, null, $page, $limit);

        $pager->setCount($totalPlaces);
        $pager->setCurrent($page);
        $pager->setTotalpages(ceil($totalPlaces / $limit));

        return $this->render('city_category.twig', [], [
            'category'  => $category,
            'city' => $citySlug,
            'places' => $places,
            'pager' => $pager
        ]);
    }


    public function listingPlacesByCity(Request $request, $citySlug)
    {
        $taxonomies = $this->app['config']->get('taxonomy');
        if (!isset($taxonomies['cities']['options'][$citySlug])) {
            $this->abort(Response::HTTP_NOT_FOUND, "City {$citySlug} not found.");
        }
        /** @var \Bundle\Site\Repository\PlaceRepository $placesRepo */
        $placesRepo = $this->app['storage']->getRepository('places');
        $places = $placesRepo->getPlacesByCity($citySlug, null, null, 1, 10000, true);

        return $this->render('city_places.twig', [], [
            'citySlug' => $citySlug,
            'places' => $places,
        ]);
    }
}
