### @RequestBody annotation

RequestBody is a way to populate objects and inject them as controller method arguments.

The Request body converter makes it possible to deserialize the request body into an object.

#### Install

`composer require paveldanilin/request-body-bundle`

#### Usage

By default, RequestBody is trying to populate the single defined parameter.

```php
/**
 * @Route("/users", methods={"POST"})
 *
 * @RequestBody
 *
 * @param User $user
 * @return Response
 */
public function createUser(User $user): Response
{
    return new Response();
}
```

If a method has several parameters we should explicitly define the parameter for populating.

```php
/**
 * @Route("/users", methods={"PATCH"})
 *
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

#### Deserialization

We can specify a deserialization context.

More about the object deserialization you can find [here](https://symfony.com/doc/4.4/components/serializer.html#deserializing-an-object)

```php
/**
 * @Route("/users", methods={"PATCH"})
 *
 * @RequestBody("user", deserializationContext={"someAttribute"="value"})
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

Also, it is possible to replace the deserialization error message with a custom message.

```php
/**
 * @Route("/users", methods={"PATCH"})
 *
 * @RequestBody("user", deserializationError="Bad DTO")
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

#### Validation

By default, validation will be performed for each assertion which is defined per DTO.

For the following DTO will be performed two assertions after a deserialization process.

```php
class User
{
    /**
     * @Assert\NotBlank(allowNull=false)
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $name;
}
```

We can avoid a validation process by defining the `validationGroups` attribute as an empty array.

```php
/**
 * @Route("/users", methods={"PATCH"})
 *
 * @RequestBody("user", validationGroups={})
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

Or we can explicitly define validation groups by means of `validationGroups` attribute.

```php
/**
 * @Route("/users", methods={"PATCH"})
 *
 * @RequestBody("user", validationGroups={"edit"})
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

You can read [more](https://symfony.com/doc/4.4/validation.html) about a validation process.

#### Debug

The bundle comes with a handy console command which shows all controllers that use the @RequestBody annotation

```
$ php bin/console request-body:debug


+--------------------+----------------------+------------+--------------------+
| Class              | Method               | Bind Param | Validation Context |
+--------------------+----------------------+------------+--------------------+
+--------------------+----------------------+------------+--------------------+
| TestUserController | createUser           | user       | all                |
| TestUserController | editUser             | user       | all                |
| TestUserController | noTypeHint           |            | all                |
| TestUserController | autoMap              |            | all                |
| TestUserController | autoMapNoParams      |            | all                |
| TestUserController | autoMapTooManyParams |            | all                |
+--------------------+----------------------+------------+--------------------+
```

#### Test
- `composer test`

