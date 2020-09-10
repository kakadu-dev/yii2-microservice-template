<?php

namespace common\helpers\microservices\Query;

/**
 * Class QueryInterface
 *
 * @package common\helpers\microservices\Query
 */
interface QueryInterface
{
    /**
     * @return bool
     */
    public function getAllPage(): bool;

    /**
     * @return array|string[]
     */
    public function getAttributes(): array;

    /**
     * @return array
     */
    public function getWhere(): array;

    /**
     * @return array
     */
    public function getWith(): array;

    /**
     * @return array
     */
    public function getOrderBy(): array;

    /**
     * @return int|null
     */
    public function getPerPage(): ?int;

    /**
     * @return int|null
     */
    public function getPage(): ?int;
}
