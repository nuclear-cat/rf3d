<?php

namespace Bundle\CMS\Controller;

use Bolt\Controller\Backend\BackendBase;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Translation\Translator as Trans;
use Bundle\CMS\Form\ContentEditType;
use Silex\ControllerCollection;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentController extends BackendBase
{
    public function addRoutes(ControllerCollection $c)
    {
        $c->method('GET|POST');
        $c->match('/{contenttypeslug}/{id}', [$this, 'edit']);

        return $c;
    }

    /**
     * Edit a record, or create a new one.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param int     $id              The content ID
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request, $contenttypeslug, $id)
    {
        $contentTypeKey = $contenttypeslug;
        $duplicate = $request->query->getBoolean('duplicate');
        $new = $duplicate ? false : empty($id);

        // Override return redirect for singletons
        $isSingleton = $this->getOption('contenttypes/' . $contentTypeKey . '/singleton');
        $deleteRoute = $isSingleton ? 'editcontent' : 'overview';

        // Test the access control
        if ($response = $this->checkEditAccess($contentTypeKey, $id)) {
            return $response;
        }

        // Get the ContentType object
        $contentType = $this->getContentType($contentTypeKey);

        $data = null;
        $options = [
            'contenttype_name' => $contentType['singular_name'],
            'csrf_protection' => false,
        ];

        /** @var Form $form */
        $form = $this->createFormBuilder(ContentEditType::class, $data, $options)
            ->getForm()
        ;
        $form->handleRequest($request);
        $button = $form->getClickedButton();

        if ($form->isSubmitted() && $form->isValid() && $button !== null) {
            // Save the POSTed record
            $formValues = $request->request->all();
            $returnTo = $this->getReturnTo($request, $button);
            $editReferrer = $request->get('editreferrer');

            if ($button->getName() === 'delete') {
                $this->app['storage.request.modify']->action($contentTypeKey, [$id => ['delete' => true]]);

                return $this->redirectToRoute($deleteRoute, ['contenttypeslug' => $contentTypeKey]);
            } else {
                $response = $this->recordSave()->action($formValues, $contentType, $id, $new || $duplicate, $returnTo, $editReferrer);
            }
            if ($response instanceof Response) {
                return $response;
            }
        }

        // If the form is not valid, we normally show it again to the user.
        // In case of an Ajaxy Request we can't, so we return a JSON error
        // response.
        if ($form->isSubmitted() && !$form->isValid() && $request->isXmlHttpRequest()) {
            $response = ['error' => ['message' => (string) $form->getErrors()]];

            return new JsonResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Get the record
            $repo = $this->getRepository($contentTypeKey);
        } catch (InvalidRepositoryException $e) {
            $this->flashes()->error(Trans::__('contenttypes.generic.not-existing', ['%contenttype%' => $contentTypeKey]));

            return $this->redirectToRoute('dashboard');
        }

        if ($new) {
            $content = $repo->create(['contenttype' => $contentTypeKey, 'status' => $contentType['default_status']]);
        } elseif ($duplicate) {
            $source = $request->query->getInt('source');
            $content = $repo->find($source);
        } else {
            $content = $repo->find($id);
        }
        if ($content === false) {
            // Record not found, advise and redirect to the dashboard
            $this->flashes()->error(Trans::__('contenttypes.generic.not-existing', ['%contenttype%' => $contentTypeKey]));

            return $this->redirectToRoute('dashboard');
        }

        // Ensure custom entities have the legacy ContentType set
        $this->app['storage.legacy_service']->setupContenttype($content);

        $context = $this->recordEdit()->action($content, $content->getContenttype(), $duplicate);
        $context['file_matcher'] = $this->app['filesystem.matcher'];
        $context['form'] = $form->createView();

        // Get the editreferrer
        $referrer = $this->getEditReferrer($request);
        if ($referrer) {
            $content['editreferrer'] = $referrer;
        }

        return $this->render('@bolt/editcontent/editcontent.twig', $context);
    }

    /**
     * Check that the user is allowed to edit the record.
     *
     * @param string $contenttypeslug
     * @param int    $id
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkEditAccess($contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id) ?: false;

        /*
         * Check the user is allowed to create/edit this record, based on:
         *     contenttype-all:
         *     contenttype-default:
         *     contenttypes:
         *         edit: []
         *         create: []
         */
        $perm = $new ? "contenttype:$contenttypeslug:create" : "contenttype:$contenttypeslug:edit:$id";
        if (!$this->isAllowed($perm)) {
            $action = $new ? 'create' : 'edit';
            $this->flashes()->error(Trans::__("You do not have the right privileges to $action that record."));

            return $this->redirectToRoute('dashboard');
        }

        return false;
    }

    /**
     * Calculate the parameter used to determine response.
     *
     * @internal to be removed when forms cut-over is complete
     *
     * @param Request $request
     * @param Button  $button
     *
     * @return string
     */
    private function getReturnTo(Request $request, Button $button)
    {
        $name = $button->getName();
        $isAjax = $request->isXmlHttpRequest();
        if ($request->attributes->has('_test')) {
            return 'test';
        }
        if ($name === 'save') {
            return $isAjax ? 'ajax' : $name;
        }

        return $name;
    }


    /**
     * @return \Bolt\Storage\ContentRequest\Save
     */
    protected function recordSave()
    {
        return $this->app['storage.request.save'];
    }

    /**
     * Set the editreferrer in twig if it was not set yet.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getEditReferrer(Request $request)
    {
        $tmp = parse_url($request->server->get('HTTP_REFERER'));

        $referrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $referrer .= '?' . $tmp['query'];
        }

        if (strpos($referrer, '/overview/') !== false || ($referrer === $this->generateUrl('dashboard') . '/')) {
            if ($this->getOption('general/compatibility/twig_globals', true)) {
                $this->app['twig']->addGlobal('editreferrer', $referrer);
            }

            return $referrer;
        }

        return null;
    }

    /**
     * @return \Bolt\Storage\ContentRequest\Edit
     */
    protected function recordEdit()
    {
        return $this->app['storage.request.edit'];
    }
}