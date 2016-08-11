<?php

namespace ride\application\queue;

use ride\application\system\System;

use ride\library\orm\OrmManager;
use ride\library\queue\job\QueueJob;
use ride\library\queue\QueueManager;

/**
 * A queue manager with the ORM as backend
 */
class OrmQueueManager implements QueueManager {

    /**
     * Instance of the system
     * @var \ride\library\system\System
     */
    protected $system;

    /**
     * Instance of the ORM manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Instance of the queue job status model
     * @var \ride\library\orm\model\Model
     */
    protected $model;

    /**
     * Constructs a new queue manager
     * @param \ride\application\system\System $system
     * @param \ride\library\orm\OrmManager $orm
     * @return null
     */
    public function __construct(System $system, OrmManager $orm) {
        $this->system = $system;
        $this->orm = $orm;
        $this->model = $orm->getQueueJobStatusModel();
    }

    /**
     * Gets the system
     * @return \ride\application\system\System
     */
    public function getSystem() {
        return $this->system;
    }

    /**
     * Gets the ORM manager
     * @return \ride\library\orm\OrmManager
     */
    public function getOrmManager() {
        return $this->orm;
    }

    /**
     * Gets the status of the queue's
     * @return array Array with the name of the queue as key and the number of
     * queued slots as value
     */
    public function getQueueStatus() {
        return $this->model->getQueueStatus();
    }

    /**
     * Gets the jobs for the provided queue
     * @param string $queue Name of the queue
     * @return array Array with the QueueJobStatus objects
     */
    public function getQueueJobStatuses($queue) {
        return $this->model->getQueueJobStatuses($queue);
    }

    /**
     * Gets the status of a job in the queue
     * @param string $id Id of the job in the queue
     * @return null|QueueJobStatus Null if the job is finished, the status of
     * the job otherwise
     */
    public function getQueueJobStatus($id) {
        return $this->model->getQueueJobStatus($id);
    }

    /**
     * Pushes a job to the queue
     * @param \ride\library\queue\job\QueueJob $queueJob Instance of the job
     * @param integer $dateScheduled Timestamp from which the invokation is
     * possible (optional)
     * @return QueueJobStatus Status of the job
     */
    public function pushJobToQueue(QueueJob $queueJob, $dateScheduled = null) {
        return $this->model->pushJobToQueue($queueJob, $dateScheduled);
    }

    /**
     * Pops a job from the queue (FIFO) and marks it as in progress
     * @param string $queue Name of the queue
     * @return QueueJobStatus|null Status of the first job in the provided queue
     * or null if the queue is empty
     */
    public function popJobFromQueue($queue) {
        return $this->model->popJobFromQueue($queue);
    }

    /**
     * Updates the status of a job
     * @param integer $id Id of the job status
     * @param string $destription Description of the progress
     * @param string $status Status code
     * @throws \ride\library\queue\exception\QueueException
     */
    public function updateStatus($id, $description, $status = null) {
        return $this->model->updateStatus($id, $description, $status);
    }

    /**
     * Reschedule a existing job
     * @param \ride\library\queue\job\QueueJob $queueJob Instance of the job
     * @param integer $dateScheduled Timestamp from which the invokation is
     * possible
     * @return null
     */
    public function rescheduleJob(QueueJob $queueJob, $dateScheduled) {
        return $this->model->rescheduleJob($queueJob, $dateScheduled);
    }

    /**
     * Finishes a job
     * @param \ride\library\queue\job\QueueJob $queueJob Instance of the job
     * @return null
     */
    public function finishJob(QueueJob $queueJob) {
        return $this->model->finishJob($queueJob);
    }

}
