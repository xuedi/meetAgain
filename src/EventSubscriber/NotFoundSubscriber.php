<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\CmsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

readonly class NotFoundSubscriber implements EventSubscriberInterface
{
    public function __construct(private CmsService $cms, private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 32],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            $path = $event->getRequest()->getPathInfo();
            dump($path);

            $context = new RequestContext();
            $context->setParameter('_locale', 'en');
            $this->router->setContext($context); // language not set on event subscriber yet

            $event->setResponse($this->cms->createNotFoundPage());
            $event->stopPropagation();
        }
    }
}