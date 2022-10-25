<?php


namespace App\Base;


use App\Error\ApiRequestErrors;
use App\Error\ErrorList;
use App\Error\InternalException;
use App\Error\RetryException;
use App\Lib\Platforms;
use Exception;
use Clearjunction\Logger\Log\CJLogger;
use Sepa\Logic\Gate\Postback\Entity\Request\HookOptions;
use Sepa\Logic\Gate\Postback\Entity\Request\HookPostBack;
use Sepa\Logic\Gate\Postback\Entity\Request\PostBackBody;
use Sepa\Logic\Gate\Postback\GatePostback;
use Throwable;

/**
 * Class AppException
 * @package App\Base
 */
abstract class AppException extends Exception
{
    protected $errorList;

    protected $details = '';

    protected $http_code = 200;

    /**
     * AppException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 500, Throwable $previous = null) {
        if (!empty($message)) {
            CJLogger::log($message);
        }
        $this->errorList = new ErrorList();

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param ErrorList $errors
     *
     * @return $this
     */
    public function addErrors(ErrorList $errors) {
        $this->errorList = $errors;

        return $this;
    }

    /**
     * @param string $details
     *
     * @return $this
     */
    public function setDetails(string $details) {
        $this->details = $details;

        return $this;
    }

    /**
     * @return string
     */
    public function getDetails(): string {
        return $this->details;
    }


    public function setHttpCode(int $http_code) {
        $this->http_code = $http_code;

        return $this;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int {
        return $this->http_code;
    }

    /**
     * @return array
     */
    public function getErrors() {
        $errors = $this->errorList->getErrors();
        if (!empty($this->getMessage()) || !empty($this->getCode())) {
            array_unshift(
                $errors,
                [
                    ApiRequestErrors::C => $this->getCode(),
                    ApiRequestErrors::M => $this->getMessage(),
                    ApiRequestErrors::D => $this->details,
                ]
            );
        }

        return $errors;
    }

    /**
     * @param string $event
     * @param string $subject
     *
     * @return $this
     */
    public function sendHook(string $event, string $subject) {
        $postback                   = new HookPostBack();
        $postback->context          = [
            'event'      => $event,
            'message'    => $this->getMessage(),
            'stackTrace' => $this->getTraceAsString(),
        ];
        $postback->options          = new HookOptions();
        $postback->options->subject = $subject;

        $platformConfig = Platforms::getPlatformConfig();

        try {
            (new GatePostback())->setType(InternalException::class)
                ->prepare(PostBackBody::CODE_SUCCESS)
                ->setAdditionalData($postback)
                ->request(
                    [
                        'headers' => [
                            'X-API-KEY'      => $platformConfig->default_x_api_key,
                            'CJ-CLIENT-UUID' => $platformConfig->default_client_uuid,
                        ],
                    ]
                );
        }
        catch (RetryException | InternalException $e) {
            // TODO: change to async jobs
        }

        return $this;
    }

    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

}
