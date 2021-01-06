<?php


namespace paveldanilin\RequestBodyBundle\Controller\Annotation;

use Doctrine\Common\Annotations\Annotation;


/**
 * RequestBody("myParam"):
 * format = Request(Content-Type) -> error
 * input = Argument->getType()
 * validationGroups = []
 * deserializerContext =[]
 *
 * RequestBody("myParam", format="json"):
 * input = Argument->getType()
 * validationGroups = []
 * deserializerContext =[]
 *
 * RequestBody("myParam", format="json", type="my\DTO\class"):
 * validationGroups = []
 * deserializerContext =[]
 *
 * @Annotation
 * @Annotation\Target({"METHOD"})
 */
class RequestBody
{
    public const APPLICATION_JSON = 'application/json';
    public const APPLICATION_XML = 'application/xml';

    public const REQUEST_ATTRIBUTE = 'request_body_bundle.annotation.request.request_body';

    /**
     * Method argument name
     * Mandatory
     * @var string
     */
    public $param;

    /**
     * Consumes media type
     * If null -> Request(Content-Type) ->  Request(Accept) -> error
     * @var string
     */
    public $consumes;

    /**
     * Input DTO class
     * If NULL Argument->getType() will be taken
     * If input !== Argument->getType() then mapper will be called
     * @var string
     */
    public $type;

    /**
     * Symfony validator validation groups
     * validationGroups = {"all"} - validate all assertions
     * @var array<string>
     */
    public $validationGroups = ['all'];

    /**
     * The custom validation error
     * @var string|null
     */
    public $validationError;

    /**
     * Symfony serializer.deserialize context
     * @var array<mixed>
     */
    public $deserializationContext = [];

    /**
     * The custom deserialization error message
     * @var string|null
     */
    public $deserializationError;


    /**
     * @param array{value:string, param:string, consumes:string, type:string, validationGroups:array} $data
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->param = $data['value'];
            unset($data['value'], $data['param']);
        }

        if (isset($data['param'])) {
            $this->param = $data['param'];
            unset($data['param']);
        }

        if (isset($data['consumes'])) {
            $this->consumes = $data['consumes'];
            unset($data['consumes']);
        }

        if (isset($data['type'])) {
            $this->type = $data['type'];
            unset($data['type']);
        }

        if (isset($data['validationGroups'])) {
            $this->validationGroups = $data['validationGroups'];
            unset($data['validationGroups']);
        }

        if (isset($data['validationError'])) {
            $this->validationError = $data['validationError'];
            unset($data['validationError']);
        }

        if (isset($data['deserializationContext'])) {
            $this->deserializationContext = $data['deserializationContext'];
            unset($data['deserializationContext']);
        }

        if (isset($data['deserializationError'])) {
            $this->deserializationError = $data['deserializationError'];
            unset($data['deserializationError']);
        }
    }

    public function getSerializationFormat(): string
    {
        $mediaType = $this->consumes;

        if (false === static::supports($mediaType)) {
            throw new \InvalidArgumentException("Unknown media type `$mediaType`");
        }

        if (false !== \strpos($mediaType, 'json')) {
            return 'json';
        }

        if (false !== \strpos($mediaType, 'xml')) {
            return 'xml';
        }

        throw new \OutOfRangeException("Could not extract serialization format from media type `$mediaType`");
    }

    public static function supports(string $mediaType): bool
    {
        return \in_array($mediaType, self::getSupportedMediaTypes(), true);
    }

    public static function getSupportedMediaTypes(): array
    {
        return [self::APPLICATION_JSON, self::APPLICATION_XML];
    }
}
