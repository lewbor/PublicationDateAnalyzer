<?php


namespace App\Lib;


use App\Entity\QueueItem;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class QueueManager
{
    public const ASKNOWLEDGE_MODE_DELETE = 1;
    public const ASKNOWLEDGE_MODE_UPDATE = 2;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param $queueName
     * @return QueueItem[]|iterable
     * @throws Exception
     */
    public function singleIterator($queueName)
    {
        while (($queueItem = $this->take($queueName)) !== null) {
            yield $queueItem;
        }
    }

    public function take($queueName)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $query = $this->em->createQueryBuilder()
                ->select('entity')
                ->from(QueueItem::class, 'entity')
                ->where('entity.queueName = :queueName')
                ->andWhere('entity.status = :status')
                ->setParameter('queueName', $queueName)
                ->setParameter('status', QueueItem::PENDING)
                ->getQuery();
            $query
                ->setMaxResults(1)
                ->setLockMode(LockMode::PESSIMISTIC_WRITE);

            /** @var QueueItem $queueItem */
            $queueItem = $query
                ->getOneOrNullResult();

            if ($queueItem !== null) {
                $queueItem->setStatus(QueueItem::IN_PROGRESS);
                $this->em->persist($queueItem);
                $this->em->flush();
            }

            $this->em->getConnection()->commit();
            return $queueItem;
        } catch (Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    public function offer(string $queueName, array $data)
    {
        $queueItem = new QueueItem();
        $queueItem
            ->setQueueName($queueName)
            ->setStatus(QueueItem::PENDING)
            ->setData($data);

        $this->em->persist($queueItem);
        $this->em->flush();

        return $queueItem;
    }

    public function acknowledge(QueueItem $item, int $asknowledgeMode = self::ASKNOWLEDGE_MODE_DELETE): void
    {
        switch ($asknowledgeMode) {
            case self::ASKNOWLEDGE_MODE_DELETE:
                $this->em->remove($item);
                $this->em->flush();
                break;
            case self::ASKNOWLEDGE_MODE_UPDATE:
                $item->setStatus(QueueItem::FINISHED);
                $this->em->persist($item);
                $this->em->flush();
                break;
            default:
                throw new \LogicException();
        }

    }

    public function cancel(QueueItem $item): void
    {
        $item->setStatus(QueueItem::PENDING);
        $this->em->persist($item);
        $this->em->flush();
    }

    public function purge(string $queueName = null): int
    {
        $qb = $this->em->createQueryBuilder()
            ->delete(QueueItem::class, 'entity')
            ->andWhere('entity.status = :status')
            ->setParameter('status', QueueItem::FINISHED);
        if ($queueName !== null) {
            $qb->andWhere('entity.queueName = :queueName')
                ->setParameter('queueName', $queueName);
        }

        $recordsRemoved = $qb
            ->getQuery()
            ->execute();
        return $recordsRemoved;
    }

    public function truncate(string $queueName = null): int
    {
        $qb = $this->em->createQueryBuilder()
            ->delete(QueueItem::class, 'entity');
        if ($queueName !== null) {
            $qb->andWhere('entity.queueName = :queueName')
                ->setParameter('queueName', $queueName);
        }

        $recordsRemoved = $qb
            ->getQuery()
            ->execute();
        return $recordsRemoved;
    }


    public function taskCount(string $queueName, $status = []): int
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(QueueItem::class, 'entity')
            ->where('entity.queueName = :name')
            ->setParameter('name', $queueName);
        if (!empty($status)) {
            $qb->andWhere('entity.status IN (:status)')
                ->setParameter('status', $status);
        }
        return (int)$qb->getQuery()
            ->getSingleScalarResult();
    }

    public function remindingTasks(string $queueName): int
    {
        return $this->taskCount($queueName, [QueueItem::PENDING]);
    }
}