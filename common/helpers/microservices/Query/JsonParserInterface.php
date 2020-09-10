<?php

namespace common\helpers\microservices\Query;

/**
 * Interface JsonParserInterface
 *
 * @package common\helpers\microservices\Query
 */
interface JsonParserInterface
{
    /**
     * @param array $condition
     *
     * @return array
     */
    public function parseJson(array $condition): array;

    /**
     * @param string $modelName
     *
     * @return $this
     */
    public function setModelName(string $modelName): self;
}