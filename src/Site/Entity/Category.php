<?php

namespace Bundle\Site\Entity;

use Bolt\Storage\Entity\Content;
use Bolt\Helpers\Excerpt;
use Bolt\Storage\Collection;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Mapping;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Carbon\Carbon;
use Twig\Markup;

class Category extends Content
{
}