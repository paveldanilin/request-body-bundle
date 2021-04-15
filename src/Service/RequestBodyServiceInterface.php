<?php


namespace paveldanilin\RequestBodyBundle\Service;

use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface RequestBodyServiceInterface
{
    /**
     * @param mixed $controller
     * @param string $method
     * @param ControllerEvent $controllerEvent
     */
    public function processEvent($controller, string $method, ControllerEvent $controllerEvent): void;

    public function isRequestSupported(Request $request): bool;

    /**
     * @param Request $request
     * @param RequestBody $requestBody
     * @return mixed
     */
    public function deserializeRequest(Request $request, RequestBody $requestBody);

    /**
     * @param mixed $target
     * @param RequestBody $requestBody
     */
    public function validateTarget($target, RequestBody $requestBody): void;
}
