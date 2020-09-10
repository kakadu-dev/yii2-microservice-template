<?php
/**
 * Created by mikhail.
 * Date: 7/9/20
 * Time: 15:02
 */

namespace common\helpers\microservices\Authorization;

/**
 * Class    Rules
 *
 * @package common\helpers\microservices\Authorization
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class Rules
{
    /**
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Get auth rules
     *
     * @return array
     */
    static public function rules(): array
    {
        return [
            'permissions' => [
                // Temporarily current microservice has own RBAC authorization
                ['sub' => 'guest', 'method' => 'base:rpc:.*:.*', 'effect' => 'allow'],
            ],
        ];
    }
}
