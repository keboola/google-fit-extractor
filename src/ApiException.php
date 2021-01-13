<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit;

use Keboola\Component\UserException;
use Throwable;

class ApiException extends UserException
{

    /**
     * @var mixed
     */
    private $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, $data)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }
}
