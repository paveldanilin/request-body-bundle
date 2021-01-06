### RequestBody annotation (Symfony 4.4.* / 5.*)

RequestBody is a way to populate objects and inject them as controller method arguments.

The Request body converter makes it possible to deserialize the request body into an object.

Usage

```php
    /**
     * @RequestBody()
     *
     * @param User $user
     * @return Response
     */
    public function createUser(User $user): Response
    {
        return new Response();
    }

    /**
     * @RequestBody("user")
     *
     * @param int $userId
     * @param User $user
     * @return Response
     */
    public function editUser(int $userId, User $user): Response
    {
        return new Response();
    }
```

### Test
- `composer test`

