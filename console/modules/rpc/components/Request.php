<?php
/**
 * Created by mikhail.
 * Date: 5/21/20
 * Time: 17:39
 */

namespace console\modules\rpc\components;

use yii\base\Component;
use yii\web\HeaderCollection;

/**
 * Class    Request
 *
 * @package console\modules\rpc\components
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * @property HeaderCollection $headers The header collection. This property is read-only.
 */
class Request extends Component
{
    /**
     * @var array
     */
    private array $_queryParams = [];

    /**
     * @var string|null
     */
    private ?string $_userIP = null;

    /**
     * @var HeaderCollection|null
     */
    private ?HeaderCollection $_headers = null;

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->_queryParams;
    }

    /**
     * Set query params
     *
     * @param array $values
     */
    public function setQueryParams(array $values): void
    {
        $this->_queryParams = $values;
    }

    /**
     * @return array
     */
    public function getBodyParams(): array
    {
        return $this->_queryParams;
    }

    /**
     * Returns POST parameter with a given name. If name isn't specified, returns an array of all POST parameters.
     *
     * @param string $name         the parameter name
     * @param mixed  $defaultValue the default parameter value if the parameter does not exist.
     *
     * @return array|mixed
     */
    public function post($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->getBodyParams();
        }

        return $this->getBodyParam($name, $defaultValue);
    }

    /**
     * Returns the named request body parameter value.
     * If the parameter does not exist, the second parameter passed to this method will be returned.
     *
     * @param string $name         the parameter name
     * @param mixed  $defaultValue the default parameter value if the parameter does not exist.
     *
     * @return mixed the parameter value
     * @see getBodyParams()
     * @see setBodyParams()
     */
    public function getBodyParam($name, $defaultValue = null)
    {
        $params = $this->getBodyParams();

        if (is_object($params)) {
            // unable to use `ArrayHelper::getValue()` due to different dots in key logic and lack of exception handling
            try {
                return $params->{$name};
            } catch (\Exception $e) {
                return $defaultValue;
            }
        }

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * Set body params
     *
     * @param array $params
     *
     * @return void
     */
    public function setBodyParams(array $params): void
    {
        $payload = $this->_queryParams['payload'] ?? null;

        $this->_queryParams = array_merge($params, ['payload' => $payload]);
    }

    /**
     * Returns request parameter
     *
     * @param string $name         the parameter name
     * @param mixed  $defaultValue the default parameter value if the parameter does not exist.
     *
     * @return array|mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        return $this->getQueryParam($name, $defaultValue);
    }

    /**
     * Returns the named GET parameter value.
     * If the GET parameter does not exist, the second parameter passed to this method will be returned.
     *
     * @param string $name         the GET parameter name.
     * @param mixed  $defaultValue the default parameter value if the GET parameter does not exist.
     *
     * @return mixed the GET parameter value
     *
     * @see getBodyParam()
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }


    /**
     * Returns whether this is a HEAD request.
     *
     * @return bool whether this is a HEAD request.
     */
    public function getIsHead(): bool
    {
        return false;
    }

    /**
     * Returns the header collection.
     * The header collection contains the currently registered HTTP headers.
     *
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection();
        }

        return $this->_headers;
    }

    /**
     * Get user ip address
     *
     * @return string|null
     */
    public function getUserIP(): ?string
    {
        return $this->_userIP;
    }

    /**
     * Set request data
     *
     * @param array $params
     */
    public function setRequestData(array $params): void
    {
        $requestInfo = $params['payload']['requestInfo'] ?? [];

        $this->_userIP      = $requestInfo['ipAddress'] ?? null;
        $this->_queryParams = is_array($params) ? $params : [];

        $this->_headers = null;
        $this->headers->set('ClientDevice', $requestInfo['clientDevice'] ?? null);
        $this->headers->set('Client', $requestInfo['clientType'] ?? null);
    }

    /**
     * Get action method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'POST';
    }
}
