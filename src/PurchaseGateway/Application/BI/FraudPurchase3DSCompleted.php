<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Illuminate\Support\Carbon;
use ProBillerNG\BI\Event\BaseEvent;

/**
 * Despite is under BI folder, this event is used only by event ingestion system
 * It was decided to leave here, because it uses same BaseEvent and have similar
 * structure as BI Events.
 *
 * Class FraudPurchase3DSCompleted
 * @package ProBillerNG\PurchaseGateway\Application\BI
 */
class FraudPurchase3DSCompleted extends BaseEvent
{
    const TYPE = 'Fraud_Purchase_3DS_Completed';

    const LATEST_VERSION = 1;

    const SCENARIO = '3DSCompleted';

    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string
     */
    private $businessGroup;

    /**
     * @var string
     */
    private $scenario;

    /**
     * @var string
     */
    private $threeDsStatus;

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var string|null
     */
    private $id;

    /**
     * FraudPurchase3DSCompleted constructor.
     *
     * @param string      $siteId        Site id.
     * @param string      $businessGroup Business Group.
     * @param string      $status        Status.
     * @param string|null $id            Email: On EIS library the id is the email.
     */
    public function __construct(
        string $siteId,
        string $businessGroup,
        string $status,
        ?string $id
    ) {
        parent::__construct(self::TYPE);

        $this->siteId        = $siteId;
        $this->businessGroup = $businessGroup;
        $this->threeDsStatus = $status;
        $this->id            = $id;
        $this->scenario      = self::SCENARIO;
        $this->timestamp     = Carbon::now()->toISOString();

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $fraudPurchased3dsCompletedArray = [
            'type'            => $this->getType(),
            'siteId'          => $this->siteId,
            'businessGroupId' => $this->businessGroup,
            'scenario'        => $this->scenario,
            '3dsStatus'       => $this->threeDsStatus
        ];
        // On EIS library the id is the email.
        // On the EIS library there is a method that takes the email from this memberInfo array
        if(!empty($this->id)){
            $fraudPurchased3dsCompletedArray['memberInfo']['email'] = $this->id;
        }

        return $fraudPurchased3dsCompletedArray;
    }
}
