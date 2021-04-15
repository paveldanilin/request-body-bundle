<?php


namespace paveldanilin\RequestBodyBundle\EventListener;

use paveldanilin\RequestBodyBundle\Service\RequestBodyServiceInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;


class RequestBodyListener
{
    private RequestBodyServiceInterface $requestBodyService;

    public function __construct(RequestBodyServiceInterface $requestBodyService)
    {
        $this->requestBodyService = $requestBodyService;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->requestBodyService->isRequestSupported($event->getRequest())) {
            return;
        }

        $controllerMeta = $event->getController();

        if (false === \is_array($controllerMeta)) {
            return;
        }

        [$controller, $method] = $controllerMeta;

        $this->requestBodyService->processEvent($controller, $method, $event);
    }
}
