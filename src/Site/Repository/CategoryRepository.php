<?php

namespace Bundle\Site\Repository;

use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

class CategoryRepository extends Repository\ContentRepository
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
}