<?php


namespace paveldanilin\RequestBodyBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;


/**
 * RequestBody("myParam"):
 * format = Request(Content-Type) ->  Request(Accept) -> error
 * input = Argument->getType()
 * validationGroups = []
 * deserializerContext =[]
 *
 * RequestBody("myParam", format="json"):
 * input = Argument->getType()
 * validationGroups = []
 * deserializerContext =[]
 *
 * RequestBody("myParam", format="json", input="my\DTO\class"):
 * validationGroups = []
 * deserializerContext =[]
 *
 * @Annotation
 * @Annotation\Target({"METHOD"})
 */
class RequestBody
{
    public const APPLICATION_JSON = 'application/json';
    public const REQUEST_ATTRIBUTE = 'app.annotation.request.request_body';

    /**
     * Method argument name
     * Mandatory
     * @var string
     */
    public $param;

    /**
     * Consumes media type
     * If null -> Configuration(api.mediatype) -> Request(Content-Type) ->  Request(Accept) -> error
     * @var string
     */
    public $consumes;

    /**
     * Input DTO class
     * If NULL Argument->getType() will be taken
     * If input !== Argument->getType() then mapper will be called
     * @var string
     */
    public $input;

    /**
     * Symfony validator validation groups
     * validationGroups = {"all"} - validate all assertions
     * @var array<string>
     */
    public $validationGroups = [];

    /**
     * Error message for an invalid DTO exception
     * Macro:
     *       - {{validation_errors}}
     * @var string|null
     */
    public $validationError;

    /**
     * Detail error template
     * Macro:
     *       - {{property}}
     *       - {{message}}
     *       - {{code}}
     * @var string
     */
    public $validationDetailTemplate;

    /**
     * Symfony serializer.deserialize context
     * @var array<mixed>
     */
    public $deserializationContext = [];

    /**
     * @var string|null
     */
    public $deserializationError;

    /**
     * RequestBody constructor.
     * @param array{value:string, param:string, consumes:string, input:string, validationGroups:array} $data
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

        if (empty($this->param)) {
            throw new \InvalidArgumentException('Not defined `param` attribute at @RequestBody');
        }

        if (isset($data['consumes'])) {
            $this->consumes = $data['consumes'];
            unset($data['consumes']);
        }

        if (isset($data['input'])) {
            $this->input = $data['input'];
            unset($data['input']);
        }

        if (isset($data['validationGroups'])) {
            $this->validationGroups = $data['validationGroups'];
            unset($data['validationGroups']);
        }

        if (isset($data['validationError'])) {
            $this->validationError = $data['validationError'];
            unset($data['validationError']);
        }

        if (isset($data['validationDetailTemplate'])) {
            $this->validationDetailTemplate = $data['validationDetailTemplate'];
            unset($data['validationDetailTemplate']);
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

        throw new \OutOfRangeException("Could not extract format from media type `$mediaType`");
    }

    public static function supports(string $mediaType): bool
    {
        return $mediaType === static::APPLICATION_JSON;
    }
}
