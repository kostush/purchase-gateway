<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

class RestartProcess extends NextAction
{
    public const TYPE = 'restartProcess';

    /**
     * @var string|null
     */
    private $error;

    /**
     * RestartProcess constructor.
     * @param string|null $error Error message.
     */
    private function __construct(?string $error)
    {
        $this->error = $error;
    }

    /**
     * @param string|null $error Error message.
     * @return RestartProcess
     */
    public static function create(?string $error = null): self
    {
        return new static($error);
    }

    /**
     * @return string|null
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = ['type' => $this->type()];

        if ($this->error()) {
            $result['error'] = $this->error();
        }

        return $result;
    }
}
