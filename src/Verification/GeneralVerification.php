<?php

namespace Mosparo\Verification;

class GeneralVerification
{
    const MINIMUM_TIME = 'minimumTime';
    const EQUAL_SUBMISSIONS = 'equalSubmissions';
    const PROOF_OF_WORK = 'proofOfWork';

    /**
     * @var string
     */
    protected string $key;

    /**
     * @var boolean
     */
    protected bool $valid;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * Constructs the object
     *
     * @param $key
     * @param $valid
     * @param $data
     */
    public function __construct($key, $valid, $data)
    {
        $this->key = $key;
        $this->valid = $valid;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

}