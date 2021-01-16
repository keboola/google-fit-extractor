<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit;

use Keboola\Component\BaseComponent;
use Keboola\Csv\CsvWriter;
use Keboola\Extractor\GoogleFit\GoogleFitApi\Client;
use Keboola\Extractor\GoogleFit\GoogleFitApi\Client as GoogleFitApiClient;
use Keboola\Google\ClientBundle\Google\RestApi;

/**
 * @method \Keboola\Extractor\GoogleFit\Config getConfig()
 */
class Component extends BaseComponent
{

    protected function handleSourcesAction()
    {
        $sources = $this->getClient()->getDataSources();
        return $sources['dataSource'];
    }

    protected function run(): void
    {

        $dataSourceId = $this->getConfig()->getDataSourceId();
        $dataTypeName = $this->getConfig()->getDataType();
        $res = $this->getClient()
            ->getHourlySteps($dataSourceId, $dataTypeName);
        $data = $res['bucket'];
        $data = array_filter($data, fn($item) => count($item['dataset'][0]['point']));
        $outTablesDir = $this->getDataDir() . '/out/tables/';
        if (!@mkdir($outTablesDir, 0777, true) && !is_dir($outTablesDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $outTablesDir));
        }
        $csvFilePath = $outTablesDir . md5($dataSourceId . '::' . $dataTypeName) . '.csv';
        $csv = new CsvWriter($csvFilePath);
        $csv->writeRow(
            [
                'data_source_id',
                'data_type',
                'formated_date',
                'start_date',
                'end_date',
                'datapoint',
            ]
        );
        array_walk(
            $data,
            function ($day) use ($dataTypeName, $dataSourceId, $csv) {
                $datapoint = $day['dataset'][0]['point'];
                if (count($datapoint) === 0) {
                    return null;
                }
                if (count($datapoint) !== 1) {
                    throw new ApiException('Unexpected amount of datapoints: %s', count($datapoint), null, $datapoint);
                }
                $startDate = Client::convertNanosToDatetime((int) $datapoint[0]['startTimeNanos']);
                $endDate = Client::convertNanosToDatetime((int) $datapoint[0]['endTimeNanos']);
                $formatedDate = $startDate->setTime((int) $startDate->format('H'), 0);
                $csv->writeRow(
                    [
                        $dataSourceId,
                        $dataTypeName,
                        $formatedDate->format(\DATE_ATOM),
                        $startDate->format(\DATE_ATOM),
                        $endDate->format(\DATE_ATOM),
                        $datapoint[0]['value'][0]['intVal'],
                    ]
                );
            }
        );
    }

    protected function getSyncActions(): array
    {
        return ['sources' => 'handleSourcesAction'];
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    private function getGoogleRestApi(): RestApi
    {
        $tokenData = json_decode($this->getConfig()->getOAuthApiData(), true);

        $restApi = new GoogleFitApi\RestApi(
            $this->getConfig()->getOAuthApiAppKey(),
            $this->getConfig()->getOAuthApiAppSecret(),
            $tokenData['access_token'],
            $tokenData['refresh_token']
        );
        return $restApi;
    }

    protected function getClient(): GoogleFitApiClient
    {
        return new GoogleFitApiClient($this->getGoogleRestApi());
    }
}
