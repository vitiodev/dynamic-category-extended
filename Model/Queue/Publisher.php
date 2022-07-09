<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Model\Queue;

use Magenmagic\CustomLogicForIndexDynamicCategories\Api\Data\CategoryDataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\MysqlMq\Model\ResourceModel\MessageCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\MysqlMq\Model\QueueManagement;

class Publisher
{
    public const TOPIC_NAME = 'dynamic.category';
    public const QUEUE_MASSAGE_STATUS_TABLE = 'queue_message_status';

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var MessageCollectionFactory
     */
    private MessageCollectionFactory $collectionFactory;

    /**
     * @var MessageEncoder
     */
    private MessageEncoder $messageEncoder;

    /**
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     * @param MessageCollectionFactory $collectionFactory
     * @param MessageEncoder $messageEncoder
     */
    public function __construct(
        PublisherInterface $publisher,
        LoggerInterface $logger,
        MessageCollectionFactory $collectionFactory,
        MessageEncoder $messageEncoder
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->messageEncoder = $messageEncoder;
    }

    /**
     * Publisher
     *
     * @param CategoryDataInterface $data
     * @return void
     */
    public function execute(CategoryDataInterface $data)
    {
        try {
            $this->publisher->publish(self::TOPIC_NAME, $data);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }

    /**
     * Check message and status
     *
     * @param CategoryDataInterface $data
     * @return void
     * @throws LocalizedException
     */
    public function checkMessageAndStatus(CategoryDataInterface $data)
    {
        $data = $this->messageEncoder->encode(self::TOPIC_NAME, $data);
        $collection = $this->collectionFactory->create();
        $collection->join(
            ['a' => $collection->getTable(self::QUEUE_MASSAGE_STATUS_TABLE)],
            '(`a`.`id` = `main_table`.`id`)',
            QueueManagement::MESSAGE_STATUS
        )->addFieldToFilter('body', $data)
            ->addFieldToFilter('status', ['in' => [
                QueueManagement::MESSAGE_STATUS_NEW,
                QueueManagement::MESSAGE_STATUS_IN_PROGRESS
            ]]);
        if ($collection->getSize()) {
            $message = $collection->getLastItem();
            if ($message->getStatus() == QueueManagement::MESSAGE_STATUS_NEW) {
                throw new LocalizedException(__('The grid generation is being processed. Please try again later.'));
            }
            if ($message->getStatus() == QueueManagement::MESSAGE_STATUS_IN_PROGRESS) {
                throw new LocalizedException(__('The grid generation is processing now. Please try again later.'));
            }
        }
    }
}
