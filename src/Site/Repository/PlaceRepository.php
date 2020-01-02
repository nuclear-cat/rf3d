<?php

namespace Bundle\Site\Repository;

use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

class PlaceRepository extends Repository\ContentRepository
{
    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        if(empty($alias)){
            $alias = $this->getAlias();
        }

        return parent::createQueryBuilder($alias);
    }

    public function getTotalPlacesByCity($citySlug, $categorySlug = null, $districtSlug = null, $onlyMainPage = false)
    {
        $qb = $this->createPlacesQueryBuilder($citySlug, $categorySlug, $districtSlug, $onlyMainPage);
        $qb->select('DISTINCT COUNT(DISTINCT p.id) as count');
        return ($qb->execute()->fetch()['count']);

    }

    public function getPlacesByCity($citySlug, $categorySlug = null, $districtSlug = null, $page = 1, $limit = 10, $onlyMainPage = false)
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createPlacesQueryBuilder($citySlug, $categorySlug, $districtSlug, $onlyMainPage);
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $this->findWith($qb);
    }


    private function createPlacesQueryBuilder($citySlug, $categorySlug, $districtSlug, $onlyMainPage)
    {
        $qb = $this->createQueryBuilder('p');

        if ($districtSlug) {
            $qb->join('p', 'bolt_relations', 'dr', ' ((dr.from_contenttype = "places" AND dr.from_id = p.id AND dr.to_contenttype = "districts") OR (dr.from_contenttype = "districts" AND dr.to_id = p.id AND dr.to_contenttype = "places"))');
            $qb->join('dr', 'bolt_districts', 'd', '(d.id = dr.to_id AND d.status = "published")');
            $qb->andWhere('d.slug = :districtSlug');
            $qb->setParameter('districtSlug', $districtSlug);
        }

        if ($categorySlug) {
            $qb->join('p', 'bolt_relations', 'cr', '(
                (cr.from_contenttype = "places" AND cr.from_id = p.id AND cr.to_contenttype = "categories") OR 
                (cr.from_contenttype = "categories" AND cr.to_id = p.id AND cr.to_contenttype = "places"))
            ');
//            $qb->join('cr', 'bolt_categories', 'c', '((c.id = cr.to_id AND cr.from_contenttype = "categories" AND cr.to_contenttype = "places") OR (c.id = cr.from_id AND cr.to_contenttype = "places" AND cr.from_contenttype = "categories"))');
            $qb->join('cr', 'bolt_categories', 'c', '((c.id = cr.to_id AND c.status = "published" AND cr.from_contenttype = "places" AND cr.to_contenttype = "categories") OR (c.id = cr.from_id AND c.status = "published" AND cr.to_contenttype = "places" AND cr.from_contenttype = "categories"))');
            $qb->andWhere('c.slug = :categorySlug');
            $qb->setParameter('categorySlug', $categorySlug);
        }

        if ($citySlug) {
            $qb->join('p', 'bolt_taxonomy', 't', "(t.content_id = p.id AND t.contenttype = 'places' AND t.taxonomytype = 'cities')");
            $qb->andWhere('t.slug = :citySlug');
            $qb->setParameter('citySlug', $citySlug);
        }

        if ($onlyMainPage) {
            $qb->andWhere('p.public_on_main_page = :onlyMainPage');
            $qb->setParameter('onlyMainPage', true);
        }
        $qb->orderBy('p.sort');
        return $qb;
    }
}