<?php
namespace Bundle\Site\Controller\Backend;

use Bolt\Controller\Backend\Records;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Storage\ContentRequest\ListingOptions;
use Bolt\Translation\Translator as Trans;

/**
 * The controller for Drop Bear routes.
 *
 * @author Kenny Koala <kenny@dropbear.com.au>
 */
class DistrictListingController extends Records
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', [$this, 'overview']);

        return $c;
    }

    /**
     * Content type overview page.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function overview(Request $request, $contenttypeslug = 'districts')
    {
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-privilege-view-page'));

            return $this->redirectToRoute('dashboard');
        }

        $taxonomy = null;
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomyKey) {
            if ($request->query->get('taxonomy-' . $taxonomyKey)) {
                $taxonomy[$taxonomyKey] = $request->query->get('taxonomy-' . $taxonomyKey);
            }
        }

        $options = (new ListingOptions())
            ->setOrder($request->query->get('order'))
            ->setPage($request->query->get('page_' . $contenttypeslug))
            ->setFilter($request->query->get('filter'))
            ->setStatus($request->query->get('status'))
            ->setTaxonomies($taxonomy)
            ->setGroupSort(true)
        ;

        $context = [
            'contenttype'     => $this->getContentType($contenttypeslug),
            'multiplecontent' => $this->recordListing()->action($contenttypeslug, $options),
            'filter'          => array_merge((array) $taxonomy, (array) $options->getFilter()),
            'permissions'     => $this->getContentTypeUserPermissions($contenttypeslug, $this->users()->getCurrentUser()),
        ];

        return $this->render('bolt/districts_overview.twig', $context);
    }
}