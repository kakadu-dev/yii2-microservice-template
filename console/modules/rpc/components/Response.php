<?php
/**
 * Created by mikhail.
 * Date: 5/21/20
 * Time: 18:15
 */

namespace console\modules\rpc\components;

use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\web\HeaderCollection;

/**
 * Class    Response
 *
 * @package console\modules\rpc\components
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * @property HeaderCollection $headers The header collection. This property is read-only.
 */
class Response extends Component
{
    /**
     * @var string
     */
    public string $statusText = 'OK';
    /**
     * @var HeaderCollection|null
     */
    private ?HeaderCollection $_headers = null;
    /**
     * @var int
     */
    private int $_statusCode = 200;

    /**
     * @return array
     */
    public static function httpStatuses(): array
    {
        return \yii\web\Response::$httpStatuses;
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
     * @return bool whether this response has a valid [[statusCode]].
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * @return int the HTTP status code to send with the response.
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Set response status code
     *
     * @param      $value
     * @param null $text
     *
     * @return $this
     */
    public function setStatusCode($value, $text = null)
    {
        if ($value === null) {
            $value = 200;
        }
        $this->_statusCode = (int) $value;
        if ($this->getIsInvalid()) {
            throw new InvalidArgumentException("The HTTP status code is invalid: $value");
        }
        if ($text === null) {
            $this->statusText = isset(self::httpStatuses()[$this->_statusCode]) ?
                self::httpStatuses()[$this->_statusCode] : '';
        } else {
            $this->statusText = $text;
        }

        return $this;
    }
}
