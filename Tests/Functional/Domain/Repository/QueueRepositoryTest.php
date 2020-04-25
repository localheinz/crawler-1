<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class QueryRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class QueueRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var QueueRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_crawler_queue.xml');
        $this->subject = $objectManager->get(QueueRepository::class);
    }

    /**
     * @test
     *
     * @dataProvider getFirstOrLastObjectByProcessDataProvider
     */
    public function getFirstOrLastObjectByProcess(string $processId, string $orderBy, string $orderDirection, array $expected): void
    {
        $process = new Process();
        $process->setProcessId($processId);

        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, ['dummy'], [], '', false);
        $result = $mockedRepository->_call('getFirstOrLastObjectByProcess', $process, $orderBy, $orderDirection);

        self::assertEquals(
            $expected,
            $result
        );
    }

    /**
     * @test
     */
    public function findYoungestEntryForProcessReturnsQueueEntry(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertEquals(
            [
                'qid' => '2',
                'page_id' => '0',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '10',
                'set_id' => '0',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1002',
                'process_id_completed' => 'qwerty',
                'configuration' => 'ThirdConfiguration',
            ],
            $this->subject->findYoungestEntryForProcess($process)
        );
    }

    /**
     * @test
     */
    public function findOldestEntryForProcessReturnsQueueEntry(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertEquals(
            [
                'qid' => '3',
                'page_id' => '0',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '20',
                'set_id' => '0',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1003',
                'process_id_completed' => 'qwerty',
                'configuration' => 'FirstConfiguration',
            ],
            $this->subject->findOldestEntryForProcess($process)
        );
    }

    /**
     * @test
     */
    public function countExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertSame(
            2,
            intval($this->subject->countExecutedItemsByProcess($process))
        );
    }

    /**
     * @test
     */
    public function countNonExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('1007');

        self::assertEquals(
            2,
            $this->subject->countNonExecutedItemsByProcess($process)
        );
    }

    /**
     * @test
     */
    public function countUnprocessedItems(): void
    {
        self::assertEquals(
            7,
            $this->subject->countUnprocessedItems()
        );
    }

    /**
     * @test
     */
    public function countAllPendingItems(): void
    {
        self::assertEquals(
            7,
            $this->subject->countAllPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllAssignedPendingItems(): void
    {
        self::assertEquals(
            3,
            $this->subject->countAllAssignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllUnassignedPendingItems(): void
    {
        self::assertEquals(
            4,
            $this->subject->countAllUnassignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countPendingItemsGroupedByConfigurationKey(): void
    {
        $expectedArray = [
            0 => [
                'configuration' => 'FirstConfiguration',
                'unprocessed' => 2,
                'assignedButUnprocessed' => 0,
            ],
            1 => [
                'configuration' => 'SecondConfiguration',
                'unprocessed' => 3,
                'assignedButUnprocessed' => 1,
            ],
            2 => [
                'configuration' => 'ThirdConfiguration',
                'unprocessed' => 2,
                'assignedButUnprocessed' => 2,
            ],
        ];

        self::assertEquals(
            $expectedArray,
            $this->subject->countPendingItemsGroupedByConfigurationKey()
        );
    }

    /**
     * @test
     */
    public function getSetIdWithUnprocessedEntries(): void
    {
        $expectedArray = [
            0 => 0,
            1 => 123,
            2 => 456,
            3 => 789,
        ];

        self::assertSame(
            $expectedArray,
            $this->subject->getSetIdWithUnprocessedEntries()
        );
    }

    /**
     * @test
     */
    public function getTotalQueueEntriesByConfiguration(): void
    {
        $setIds = [123, 789];

        $expected = [
            'ThirdConfiguration' => 1,
            'SecondConfiguration' => 2,
        ];

        self::assertEquals(
            $expected,
            $this->subject->getTotalQueueEntriesByConfiguration($setIds)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntriesTimestamps(): void
    {
        $expectedArray = [
            '0' => 20,
            '1' => 20,
            '2' => 18,
        ];

        self::assertEquals(
            $expectedArray,
            $this->subject->getLastProcessedEntriesTimestamps(3)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntries(): void
    {
        $expectedArray = [3, 17];

        $processedEntries = $this->subject->getLastProcessedEntries(2);
        $actually = [];
        foreach ($processedEntries as $processedEntry) {
            $actually[] = $processedEntry['qid'];
        }

        self::assertSame(
            $expectedArray,
            $actually
        );
    }

    /**
     * @test
     */
    public function getPerformanceData(): void
    {
        $expected = [
            'asdfgh' => [
                'process_id_completed' => 'asdfgh',
                'start' => 10,
                'end' => 18,
                'urlcount' => 3,
            ],
            'qwerty' => [
                'process_id_completed' => 'qwerty',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
            ],
            'dvorak' => [
                'process_id_completed' => 'dvorak',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
            ],
        ];

        self::assertEquals(
            $expected,
            $this->subject->getPerformanceData(9, 21)
        );
    }

    /**
     * @test
     */
    public function countAll(): void
    {
        self::assertEquals(
            14,
            $this->subject->countAll()
        );
    }

    /**
     * @test
     */
    public function isPageInQueueThrowInvalidArgumentException(): void
    {
        $this->expectExceptionMessage('Argument 1 passed to AOE\Crawler\Domain\Repository\QueueRepository::isPageInQueue() must be of the type int');
        $this->subject->isPageInQueue('Cannot be interpreted as integer');
    }

    /**
     * @test
     *
     * @dataProvider isPageInQueueDataProvider
     */
    public function isPageInQueue(int $uid, bool $unprocessed_only, bool $timed_only, int $timestamp, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp)
        );
    }

    /**
     * @test
     */
    public function isPageInQueueTimed(): void
    {
        self::assertTrue($this->subject->isPageInQueueTimed(15));
    }

    /**
     * @test
     */
    public function getAvailableSets(): void
    {
        $availableSets = $this->subject->getAvailableSets();
        self::assertEquals(
            [
                'count_value' => 1,
                'set_id' => 0,
                'scheduled' => 4321,
            ],
            $availableSets[0]
        );
        self::assertCount(
            7,
            $availableSets
        );
    }

    /**
     * @test
     */
    public function findByQueueId(): void
    {
        $queueRecord = $this->subject->findByQueueId('15');
        self::assertEquals(
            12,
            $queueRecord['scheduled']
        );
    }

    /**
     * @test
     */
    public function cleanupQueue(): void
    {
        self::assertEquals(14, $this->subject->countAll());
        $this->subject->cleanupQueue();
        self::assertEquals(7, $this->subject->countAll());
    }

    /**
     * @test
     */
    public function fetchRecordsToBeCrawled(): void
    {
        $recordsToBeCrawledLimitHigherThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(10);
        self::assertCount(
            7,
            $recordsToBeCrawledLimitHigherThanRecordsCount
        );

        $recordsToBeCrawledLimitLowerThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(5);
        self::assertCount(
            5,
            $recordsToBeCrawledLimitLowerThanRecordsCount
        );
    }

    /**
     * @test
     */
    public function UpdateProcessIdAndSchedulerForQueueIds(): void
    {
        $qidToUpdate = [4, 8, 15, 18];
        $processId = md5('this-is-the-process-id');

        self::assertEquals(
            4,
            $this->subject->updateProcessIdAndSchedulerForQueueIds($qidToUpdate, $processId)
        );
    }

    /**
     * @test
     */
    public function unsetProcessScheduledAndProcessIdForQueueEntries(): void
    {
        $unprocessedEntriesBefore = $this->subject->countAllUnassignedPendingItems();
        self::assertEquals(
            4,
            $unprocessedEntriesBefore
        );
        $processIds = ['1007'];
        $this->subject->unsetProcessScheduledAndProcessIdForQueueEntries($processIds);

        $unprocessedEntriesAfter = $this->subject->countAllUnassignedPendingItems();
        self::assertEquals(
            6,
            $unprocessedEntriesAfter
        );
    }

    /**
     * @dataProvider noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist(int $uid, string $configurationHash, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid, $configurationHash)
        );
    }

    /**
     * @return array
     */
    public function isPageInQueueDataProvider()
    {
        return [
            'Unprocessed Only' => [
                'uid' => 15,
                'unprocessed_only' => true,
                'timed_only' => false,
                'timestamp' => 0,
                'expected' => true,
            ],
            'Timed Only' => [
                'uid' => 16,
                'unprocessed_only' => false,
                'timed_only' => true,
                'timestamp' => 0,
                'expected' => true,
            ],
            'Timestamp Only' => [
                'uid' => 17,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => 4321,
                'expected' => true,
            ],
            'Not existing page' => [
                'uid' => 40000,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => 0,
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFirstOrLastObjectByProcessDataProvider()
    {
        return [
            'Known process_id, get first' => [
                'processId' => 'qwerty',
                'orderBy' => 'process_id',
                'orderDirection' => 'ASC',
                'expected' => [
                    'qid' => '2',
                    'page_id' => '0',
                    'parameters' => '',
                    'parameters_hash' => '',
                    'configuration_hash' => '',
                    'scheduled' => '0',
                    'exec_time' => '10',
                    'set_id' => '0',
                    'result_data' => '',
                    'process_scheduled' => '0',
                    'process_id' => '1002',
                    'process_id_completed' => 'qwerty',
                    'configuration' => 'ThirdConfiguration',
                ],
            ],
            'Known process_id, get last' => [
                'processId' => 'qwerty',
                'orderBy' => 'process_id',
                'orderDirection' => 'DESC',
                'expected' => [
                    'qid' => '3',
                    'page_id' => '0',
                    'parameters' => '',
                    'parameters_hash' => '',
                    'configuration_hash' => '',
                    'scheduled' => '0',
                    'exec_time' => '20',
                    'set_id' => '0',
                    'result_data' => '',
                    'process_scheduled' => '0',
                    'process_id' => '1003',
                    'process_id_completed' => 'qwerty',
                    'configuration' => 'FirstConfiguration',
                ],
            ],
            'Unknown process_id' => [
                'processId' => 'unknown_id',
                'orderBy' => 'process_id',
                'orderDirection' => '',
                'expected' => [],
            ],
        ];
    }

    /**
     * @return array
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider()
    {
        return [
            'No record found, uid not present' => [
                'uid' => 3000,
                'configurationHash' => '7b6919e533f334550b6f19034dfd2f81',
                'expected' => true,
            ],
            'No record found, configurationHash not present' => [
                'uid' => 2001,
                'configurationHash' => 'invalidConfigurationHash',
                'expected' => true,
            ],
            'Record found - uid and configurationHash is present' => [
                'uid' => 2001,
                'configurationHash' => '7b6919e533f334550b6f19034dfd2f81',
                'expected' => false,
            ],
        ];
    }
}
