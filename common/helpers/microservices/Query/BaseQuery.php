<?php

namespace common\helpers\microservices\Query;

/**
 * Class BaseQuery
 *
 * @package common\helpers\microservices\Query
 */
abstract class BaseQuery implements QueryInterface
{
    /**
     * @var array|mixed|null
     */
    protected ?array $with;

    /**
     * @var mixed
     */
    protected $where;

    /**
     * @var mixed
     */
    protected $andWhere;

    /**
     * @var string[]|mixed|null
     */
    protected ?array $orderBy;

    /**
     * @var int|null
     */
    protected ?int $page;

    /**
     * @var int|null
     */
    protected ?int $perPage;

    /**
     * @var bool|mixed|null
     */
    protected ?bool $allPage = false;

    /**
     * @var string[]|mixed|null
     */
    protected ?array $attributes;

    /**
     * @var string
     */
    protected string $mainClass;

    /**
     * @param array  $data
     * @param string $mainClass
     * @param null   $parameter
     *
     * @return static
     */
    public static function init(array $data, string $mainClass, $parameter = null): self
    {
        return (new static())
            ->configure($parameter)
            ->setMainClass($mainClass)
            ->setAttributes($data);
    }

    /**
     * @param $parameters
     *
     * @return $this
     */
    abstract protected function configure($parameters): self;

    /**
     * @param string $mainClass
     *
     * @return $this
     */
    public function setMainClass(string $mainClass)
    {
        $this->mainClass = $mainClass;

        return $this;
    }

    /**
     * @return string|null
     */
    abstract protected function getMainTableName(): ?string;

    /**
     * @param array $data
     *
     * @return $this
     */
    abstract protected function setAttributes(array $data): self;

    /**
     * @return JsonParserInterface
     */
    abstract protected function getJsonParser(): JsonParserInterface;

    /**
     * @return string|null
     */
    abstract protected function getMainClass(): ?string;
}
