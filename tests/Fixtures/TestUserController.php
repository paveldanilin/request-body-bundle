<?php


namespace Pada\RequestBodyBundle\Tests\Fixtures;


use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Symfony\Component\HttpFoundation\Response;

class TestUserController
{
    /**
     * @RequestBody("user")
     *
     * @param User $user
     * @return Response
     */
    public function createUser(User $user): Response
    {
        return new Response($user->name);
    }

    /**
     * @RequestBody("user")
     *
     * @param User $u
     * @return Response
     */
    public function editUser(User $u): Response
    {
        // Will throw: Parameter `user` not found.
        return new Response();
    }

    /**
     * @RequestBody
     *
     * @param User $user
     * @return Response
     */
    public function noTypeHint($user): Response
    {
        // Will throw: Parameter `user` does not have type hint
        return new Response();
    }

    /**
     * @RequestBody
     *
     * @param User $user
     * @return Response
     */
    public function autoMap(User $user): Response
    {
        // Since the method has only one parameter we can try to map body to this parameter
        return new Response();
    }

    /**
     * @RequestBody
     *
     * @return Response
     */
    public function autoMapNoParams(): Response
    {
        // Will throw: Could not autodetect parameter for body mapping. The method does not have parameters.
        return new Response();
    }

    /**
     * @RequestBody
     *
     * @param int $a
     * @param int $b
     * @return Response
     */
    public function autoMapTooManyParams(int $a, int $b): Response
    {
        // Will throw: Could not autodetect parameter for body mapping. The method has too many parameters.
        return new Response();
    }
}
