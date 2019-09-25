<?php

namespace ride\application\orm\model;

use ride\library\orm\model\GenericModel;
use ride\library\queue\exception\QueueException;
use ride\library\queue\job\QueueJob;
use ride\library\queue\QueueManager;

/**
 * ORM implementation of the queue
 */
class QueueJobStatusModel extends GenericModel implements QueueManager {

    /**
     * Gets the status of the queue's
     * @return array Array with the name of the queue as key and the number of
     * queued slots as value
     */
    public function getQueueStatus() {
        $status = array();

        $query = $this->createQuery();
        $query->addCondition('{status} = %1% AND ({dateScheduled} IS NULL OR {dateScheduled} <= %2%)', self::STATUS_WAITING, time());

        $rows = $query->query();
        foreach ($rows as $row) {
            if (!isset($status[$row->queue])) {
                $status[$row->queue] = 1;
            } else {
                $status[$row->queue]++;
            }
        }

        ksort($status);

        return $status;
    }

    /**
     * Gets the jobs for the provided queue
     * @param string $queue Name of the queue
     * @return array Array with the QueueJobStatus objects
     */
    public function getQueueJobStatuses($queue) {
        $query = $this->createQuery();
        $query->addCondition('{queue} = %1%', $queue);
        $query->addOrderBy('{id} ASC');

        $statuses = $query->query();
        $slots = count($statuses);
        $slot = 1;

        foreach ($statuses as $status) {
            $status->setSlot($slot);
            $status->setSlots($slots);

            $slot++;
        }

        return $statuses;
    }

    /**
     * Gets the status of a job in the queue
     * @param string $id Id of the job in the queue
     * @return null|QueueJobStatus Null if the job is finished, the status of
     * the job otherwise
     */
    public function getQueueJobStatus($id) {
        $entry = $this->getById($id);
        if (!$entry) {
            return null;
        }

        $query = $this->createQuery();
        $query->addCondition('{queue} = %1% AND {status} = %2% AND ({dateScheduled} IS NULL OR {dateScheduled} <= %3%)', $entry->getQueue(), self::STATUS_WAITING, time());
        $entry->setSlots($query->count());

        $query = $this->createQuery();
        $query->addCondition('{queue} = %1% AND {status} = %2% AND {id} < %3%', $entry->getQueue(), self::STATUS_WAITING, $id);
        $entry->setSlot($query->count() + 1);

        return $entry;
    }

    /**
     * Pushes a job to the queue
     * @param QueueJob $queueJob Instance of the queue job
     * @param integer $dateScheduled Timestamp from which the invokation is
     * possible (optional)
     * @return QueueJobStatus Status of the job
     */
    public function pushJobToQueue(QueueJob $queueJob, $dateScheduled = null) {
        if (!$queueJob->getQueue()) {
            throw new QueueException('Could not push the job to the queue: no queue set in the job');
        }

        $entry = $this->createEntry();
        $entry->setQueueJob(clone $queueJob);
        $entry->setStatus(self::STATUS_WAITING);
        $entry->setDateScheduled($dateScheduled);
        $entry->setPriority($queueJob->getPriority());

        $this->save($entry);

        $queueJob->setJobId($entry->getId());

        return $entry;
    }

    /**
     * Pops a job from the queue (FIFO) and marks it as in progress
     * @param string $queue Name of the queue
     * @return QueueJobStatus|null Status of the first job in the provided queue
     * or null if the queue is empty
     */
    public function popJobFromQueue($queue) {
        $query = $this->createQuery();
        $query->addCondition('{queue} = %1% AND {status} = %2% AND ({dateScheduled} IS NULL OR {dateScheduled} <= %3%)', $queue, self::STATUS_WAITING, time());
        $query->addOrderBy('{priority} ASC, {id} ASC');

        $entry = $query->queryFirst();
        if (!$entry) {
            return null;
        }

        $entry->setStatus(self::STATUS_PROGRESS);

        $this->save($entry);

        return $entry;
    }

    /**
     * Updates the status of a job
     * @param integer $id Id of the job status
     * @param string $destription Description of the progress
     * @param string $status Status code
     * @return null
     * @throws \ride\library\queue\exception\QueueException
     */
    public function updateStatus($id, $description, $status = null) {
        if ($status !== null && $status !== self::STATUS_ERROR) {
            throw new QueueException('Could not update the job status: only error status is allowed');
        }

        $entry = $this->createProxy($id);
        $entry->setDescription($description);

        if ($status) {
            $entry->setStatus($status);
        }

        $this->save($entry);
    }

    /**
     * Reschedule a existing job
     * @param \ride\library\queue\QueueJob $queueJob Instance of the queue job
     * @param integer $dateScheduled Timestamp from which the invokation is
     * possible
     * @return null
     */
    public function rescheduleJob(QueueJob $queueJob, $dateScheduled) {
        $entry = $this->createProxy($queueJob->getJobId());

        $canReschedule = false;
        if ($queueJob->getMaxSchedules() === true || $queueJob->getMaxSchedules() > $entry->getNumSchedules()) {
            $canReschedule = true;
        }

        if (!$canReschedule) {
            throw new QueueException('Cannot reschedule job: max schedules reached');
        }

        $entry->setStatus(self::STATUS_WAITING);
        $entry->setDateScheduled($dateScheduled);
        $entry->setNumSchedules($entry->getNumSchedules() + 1);

        $this->save($entry);
    }

    /**
     * Finishes a job
     * @param \ride\library\queue\job\QueueJob $queueJob Instance of the job
     * @return null
     */
    public function finishJob(QueueJob $queueJob) {
        $queueJobStatus = $this->createProxy($queueJob->getJobId());

        $this->delete($queueJobStatus);
    }

}
