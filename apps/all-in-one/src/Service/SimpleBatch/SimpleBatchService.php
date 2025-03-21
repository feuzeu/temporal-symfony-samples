<?php

namespace App\Service\SimpleBatch;

use Exception;
use Psr\Log\LoggerInterface;

use function array_map;
use function random_int;
use function range;
use function usleep;

class SimpleBatchService
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(private LoggerInterface $logger)
    {}

    /**
     * @param int $batchId
     *
     * @return array<int,int>
     */
    private function getItemIds(int $batchId): array
    {
        return array_map(function(int $itemId) use($batchId) {
            return (($batchId % 100) * 1000) + $itemId;
        }, range(101, random_int(120, 150)));
    }

    /**
     * @param int $batchId
     *
     * @return array<int,string|int>
     */
    private function getOptions(int $batchId): array
    {
        return [];
    }

    /**
     * @param int $batchId
     *
     * @return array<array<int,string|int>>
     */
    public function getBatchItemIds(int $batchId): array
    {
        return [$this->getItemIds($batchId), $this->getOptions($batchId)];
    }

    /**
     * @param int $itemId
     * @param int $batchId
     * @param array<int,string|int> $options
     *
     * @return void
     */
    public function itemProcessingStarted(int $itemId, int $batchId, array $options): void
    {
        $this->logger->debug("Started processing of item $itemId of batch $batchId.", ['options' => $options]);
    }

    /**
     * @param int $itemId
     * @param int $batchId
     * @param array<int,string|int> $options
     *
     * @return int
     * @throws Exception
     */
    public function processItem(int $itemId, int $batchId, array $options): int
    {
        $this->logger->debug("Processing item $itemId of batch $batchId.", ['options' => $options]);

        $random = random_int(0, 90);
        // Wait for max 1 second.
        usleep($random % 10000);

        if($random > 30)
        {
            throw new Exception("Error while processing of item $itemId of batch $batchId.");
        }
        return $random;
    }

    /**
     * @param int $itemId
     * @param int $batchId
     * @param array<int,string|int> $options
     *
     * @return void
     */
    public function itemProcessingEnded(int $itemId, int $batchId, array $options): void
    {
        $this->logger->debug("Ended processing of item $itemId of batch $batchId.", ['options' => $options]);
    }
}
