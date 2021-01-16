<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{

    public function getDataSourceId(): string
    {
        return $this->getValue(
            [
                'parameters',
                'dataSourceId',
            ]
        );
    }

    public function getDataType(): string
    {
        return $this->getValue(
            [
                'parameters',
                'dataType',
            ]
        );
    }
}
