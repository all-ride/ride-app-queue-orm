<?php

namespace ride\application\queue;

use ride\application\orm\entry\QueueJobStatusEntry;

use ride\library\queue\job\QueueJob;
use ride\library\queue\QueueJobStatus;

/**
 * ORM Entry for the status of a queue job
 */
class OrmQueueJobStatus extends QueueJobStatusEntry implements QueueJobStatus {

    /**
     * Instance of the queue job
     * \ride\library\queue\QueueJob
     */
    protected $queueJob;

    /**
     * Slot of the holding job
     * @var integer
     */
    protected $slot;

    /**
     * Total number of slots
     * @var integer
     */
    protected $slots;

    /**
     * Sets the queue job
     * @param \ride\library\queue\QueueJob $queueJob
     * @return null
     */
    public function setQueueJob(QueueJob $queueJob) {
        $this->setQueue($queueJob->getQueue());
        $this->setClassName(get_class($queueJob));
        $this->setData(serialize($queueJob));

        $this->queueJob = $queueJob;
    }

    /**
     * Gets the queue job
     * @return \ride\library\queue\QueueJob
     */
    public function getQueueJob() {
        if ($this->queueJob) {
            return $this->queueJob;
        }

        $this->queueJob = unserialize($this->getData());
        $this->queueJob->setJobId($this->getId());

        return $this->queueJob;
    }

    /**
     * Sets the slot number
     * @return integer
     */
    public function setSlot($slot) {
        $this->slot = $slot;
    }

    /**
     * Gets the slot number
     * @return integer
     */
    public function getSlot() {
        return $this->slot;
    }

    /**
     * Sets the total number of slots
     * @param integer $slots
     * @return null
     */
    public function setSlots($slots) {
        $this->slots = $slots;
    }

    /**
     * Gets the total number of slots
     * @return integer
     */
    public function getSlots() {
        return $this->slots;
    }

}
