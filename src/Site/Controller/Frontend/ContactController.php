<?php
namespace Bundle\Site\Controller\Frontend;

use Bolt\Controller\Base;
use Bolt\Controller\ConfigurableBase;
use Bolt\Storage\Database\Schema\Table\ContentType;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Repository\ContentRepository;
use Bundle\Site\Entity\ContactMessage;
use Bundle\Site\Entity\Place;
use Bundle\Site\Enum\ContentStatuses;
use Doctrine\DBAL\Types\TextType;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Drop Bear routes.
 *
 * @author Kenny Koala <kenny@dropbear.com.au>
 */
class ContactController extends ConfigurableBase
{
    protected function getConfigurationRoutes()
    {
        return $this->app['config']->get('routing', []);
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', [$this, 'contact']);
        return $c;
    }

    public function contact(Request $request)
    {
        $formBuilder = $this->createFormBuilder();
        $formBuilder->add('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [ 'label' => 'Имя' ]);
        $formBuilder->add('city', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [ 'label' => 'Город' ]);
        $formBuilder->add('phone', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [ 'label' => 'Телефон' ]);
        $formBuilder->add('organizationName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [ 'label' => 'Название заведения' ]);
        $formBuilder->add('text', TextareaType::class, [ 'label' => 'Текст сообщения' ]);
        $formBuilder->add('recaptcha', HiddenType::class);
        $formBuilder->add('send', SubmitType::class, [ 'label' => 'Отправить' ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $emailSent = false;
            $isSpam = false;

            $recaptcha = new \ReCaptcha\ReCaptcha($this->app['config']->get('general')['recaptcha_secret']);
            $resp = $recaptcha->verify($form->get('recaptcha')->getData(), $_SERVER['REMOTE_ADDR']);

            if (!$resp->isSuccess()) {
                $isSpam = true;
                $form->addError(new FormError('Подтвердите, что вы не робот'));
                $this->app['logger.flash']->error('Подтвердите, что вы не робот');
            }

            $name = $form->get('name')->getData();
            $phone = $form->get('phone')->getData();
            $contactMessage = new ContactMessage([
                'title' => "Message from {$name}",
                'name' => $name,
                'phone' => $phone,
                'city' => $form->get('city')->getData(),
                'organization_name' => $form->get('organizationName')->getData(),
                'body' => $form->get('text')->getData(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'is_spam' => $isSpam ? 1 : 0,
            ]);

            /** @var ContentRepository $repo */
            $repo = $this->app['storage']->getRepository('contact_messages');
            $contactMessage->setStatus(ContentStatuses::STATUS_DRAFT);
            $repo->save($contactMessage);

            if ($form->isValid()) {
                $body = "<p>
                    <b>Имя:</b> {$name}<br />
                    <b>Город:</b> {$phone}<br />
                    <b>Телефон:</b> {$form->get('phone')->getData()}<br />
                    <b>Организация:</b> {$form->get('organizationName')->getData()}<br />
                    <b>Текст:</b> {$form->get('text')->getData()}<br />
                </p>";


                /** @var \Swift_Mailer $mailer */
                $mailer = $this->app['mailer'];
                $message = (new \Swift_Message("Сообщение с сайта {$_SERVER['SERVER_ADDR']}"))
                    ->setFrom($this->app['config']->get('general')['mailoptions']['senderMail'])
                    ->setTo( $this->app['config']->get('general')['mailoptions']['recipientMail'])
                    ->setBody($body)
                    ->setContentType('text/html')
                ;

                $emailSent = $mailer->send($message);
                $this->app['logger.flash']->success('Сообщение отправлено');
                return $this->redirectToRoute('_contact_');
            }
        }

        return $this->render('contact.twig', [], [
            'form' => $form->createView(),
            'recaptcha_key' => $this->app['config']->get('general')['recaptcha_key']
        ]);
    }
}
