<?php
namespace TogglDaily;

use \AJT\Toggl\ReportsClient;

class TogglDaily extends BaseCLI
{
    private $results = [];
    private $togglReports;
    private $config;
    private $totalTime = 0;

    public function run()
    {
        $this->config = require_once 'config/config.php';

        $this->togglReports = ReportsClient::factory([
            'api_key'    => $this->config['togglApiKey'],
            'debug'      => false,
        ]);

        $response = $this->requestData();
        $totalCount = $response['total_count'];
        $perPage = $response['per_page'];

        $this->parseResponse($response['data'], $totalCount, $perPage);
        $this->outToCsv();

        var_dump($this->results);
        var_dump($this->totalTime); // in hours
    }

    private function outToCsv()
    {
        $fp = fopen(__DIR__ . '/../results/11_2011.csv', 'w');

        foreach ($this->results as $date => $resultByDate) {
            foreach ($resultByDate as $task => $duration) {
                fputcsv($fp, [
                    $date,
                    $task,
                    round($duration, 3),
                ]);
            }
        }

        fclose($fp);
    }

    private function parseResponse($data, $totalCount, $perPage, $pageNumber = 1)
    {
        foreach ($data as $entity) {
            $curDate = date('d', strtotime($entity['start']));

            if (empty($this->results[$curDate])) {
                $this->results[$curDate] = [];
            }

            $task = $entity['description'];

            if (empty($this->results[$curDate][$task])) {
                $this->results[$curDate][$task] = 0;
            }

            $this->results[$curDate][$task] += ($entity['dur'] / 1000 / 60 / 60);
            $this->totalTime += ($entity['dur'] / 1000 / 60 / 60);
        }

        if (($pageNumber * $perPage) < $totalCount) {
            $pageNumber++;
            $response = $this->requestData($pageNumber);
            $totalCount = $response['total_count'];
            $perPage = $response['per_page'];

            $this->parseResponse($response['data'], $totalCount, $perPage, $pageNumber);
        }
    }

    private function requestData($page = 1)
    {
        $userAgent = 'Toggl PHP Client';
        $response = $this->togglReports->details([
            'user_agent'  => $userAgent,
            'workspace_id' => $this->config['togglWorkspaceId'],
            'since' => '2021-11-01',
            'until' => '2021-11-30',
            'order_field' => 'date',
            'order_desc' => 'off',
            'page' => $page,
        ]);

        //var_dump($response['data']);

        if ($response['total_count'] <= 0) {
            throw new \Exception('total_count is 0');
        }

        return $response;
    }
}