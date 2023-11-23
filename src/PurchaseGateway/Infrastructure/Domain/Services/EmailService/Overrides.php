<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Application\Services\Validators;

class Overrides
{
    use Validators;

    /** @var string */
    private $subject;

    /** @var string */
    private $friendlyName;

    /** @var string */
    private $from;

    /**
     * Overrides constructor.
     * @param string|null $subject      Subject
     * @param string|null $friendlyName FromName
     * @param string|null $from
     */
    private function __construct(
        string $subject = null,
        string $friendlyName = null,
        string $from = null
    ) {
        $this->subject      = $subject;
        $this->friendlyName = $friendlyName;
        $this->from         = $from;
    }

    /**
     * @param string|null $subject      Subject
     * @param string|null $friendlyName FromName
     * @param string|null $from
     * @return Overrides
     */
    public static function create(
        string $subject = null,
        string $friendlyName = null,
        string $from = null
    ): self {
        return new self($subject, $friendlyName, $from);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        if (!is_null($this->subject)) {
            $array['subject'] = $this->subject;
        }
        if (!is_null($this->friendlyName)) {
            $array['friendlyName'] = $this->friendlyName;
        }
        if (!is_null($this->from)) {
            $array['from'] = $this->from;
        }
        return $array;
    }
}
