<?php

namespace Mosparo\Message;

class UpdateMessage
{
    const STATUS_UNDEFINED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_ERROR = 3;

    /**
     * @var string
     */
    protected string $context;

    /**
     * @var int
     */
    protected int $status = self::STATUS_UNDEFINED;

    /**
     * @var string
     */
    protected string $message;

    /**
     * @var \DateTime
     */
    protected \DateTime $dateTime;

    /**
     * Constructs the object
     *
     * @param string $context
     * @param int $status
     * @param string $message
     */
    public function __construct(string $context, int $status, string $message)
    {
        $this->context = $context;
        $this->status = $status;
        $this->message = $message;
        $this->dateTime = new \DateTime();
    }

    /**
     * Returns the context of the message
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Returns the status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Returns true if the status is in progress
     * @return bool
     */
    public function isInProgress(): bool
    {
        return ($this->status === self::STATUS_IN_PROGRESS);
    }

    /**
     * Returns true if the status is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return ($this->status === self::STATUS_COMPLETED);
    }

    /**
     * Returns true if this is an error message
     *
     * @return bool
     */
    public function isError(): bool
    {
        return ($this->status === self::STATUS_ERROR);
    }

    /**
     * Returns the message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns the date and time of the message
     *
     * @return \DateTime
     */
    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }
}