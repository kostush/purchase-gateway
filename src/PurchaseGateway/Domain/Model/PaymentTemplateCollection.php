<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProbillerMGPG\SubsequentOperations\Init\Response\PaymentTemplateInfo;
use ProBillerNG\Base\Domain\Collection;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;

class PaymentTemplateCollection extends Collection
{
    /**
     * Validates the object
     *
     * @param mixed $object object
     *
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return ($object instanceof PaymentTemplate);
    }

    /**
     * @return PaymentTemplateCollection
     */
    public function sortByLastUsedDateDesc(): PaymentTemplateCollection
    {
        $data = [];

        foreach ($this->getValues() as $object) {
            $data[$object->templateId()] = $object;
        }

        uasort(
            $data,
            function ($itemOne, $itemTwo) {
                return $itemOne->lastUsedDate() > $itemTwo->lastUsedDate() ? -1 : 1;
            }
        );

        return $this->createFrom($data);
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnknownBillerNameException
     */
    public function removeThirdPartyBillerTemplates(): void
    {
        foreach ($this->getValues() as $key => $template) {
            $biller = BillerFactoryService::create($template->billerName());

            if ($biller->isThirdParty()) {
                $this->removeElement($template);
            }
        }
    }

    /**
     * @param string $billerName Biller name.
     * @return PaymentTemplate|null
     */
    public function getLastUsedBillerTemplate(string $billerName): ?PaymentTemplate
    {
        foreach ($this->getValues() as $template) {
            if ($template->billerName() !== $billerName) {
                $this->removeElement($template);
            }
        }

        $lastUsedTemplate = $this->sortByLastUsedDateDesc()->first();

        if ($lastUsedTemplate instanceof PaymentTemplate) {
            return $lastUsedTemplate;
        }

        return null;
    }

    /**
     * @return PaymentTemplateCollection
     */
    public function sortByCreatedAtDesc(): PaymentTemplateCollection
    {
        $data = [];

        foreach ($this->getValues() as $object) {
            $data[$object->templateId()] = $object;
        }

        uasort(
            $data,
            function ($itemOne, $itemTwo) {
                return $itemOne->createdAt() > $itemTwo->createdAt() ? -1 : 1;
            }
        );

        return $this->createFrom($data);
    }

    /**
     * @param string|null $billerName Biller name
     * @return array
     */
    public function filterByBiller(?string $billerName): array
    {
        $filteredPaymentTemplates = [];

        /** @var PaymentTemplate $paymentTemplate */
        foreach ($this->getValues() as $paymentTemplate) {
            if ($paymentTemplate->billerName() === $billerName) {
                $filteredPaymentTemplates[] = $paymentTemplate->toArray();
            }
        }

        return $filteredPaymentTemplates;
    }

    /**
     * @param bool $isSafe Is safe.
     * @return void
     */
    public function setAllSafeBins(bool $isSafe): void
    {
        /** @var PaymentTemplate $paymentTemplate */
        foreach ($this->getValues() as $paymentTemplate) {
            $paymentTemplate->setIsSafe($isSafe);
        }
    }

    /**
     * @return PaymentTemplate|bool
     */
    public function firstPaymentTemplate()
    {
        return $this->sortByCreatedAtDesc()->last();
    }

    /**
     * @param array|null $paymentTemplateInfo                Payment template info.
     * @param bool       $isPaymentTemplateValidationEnabled Is payment template validation enabled.
     * @return PaymentTemplateCollection
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function createFromRebillUpdateResponse(
        ?array $paymentTemplateInfo,
        bool $isPaymentTemplateValidationEnabled
    ): PaymentTemplateCollection {
        $collection = new PaymentTemplateCollection();

        if (empty($paymentTemplateInfo)) {
            Log::alert(
                'No payment template returned on MGPG rebill update init response.',
                ['paymentTemplateInfo' => $paymentTemplateInfo]
            );
            return $collection;
        }

        /** @var PaymentTemplateInfo $template */
        foreach ($paymentTemplateInfo as $template) {
            $template = PaymentTemplate::create(
                $template->templateId,
                $template->userFriendlyIdentifier->first6 ?? null,
                null,
                $template->userFriendlyIdentifier->expirationYear ?? null,
                $template->userFriendlyIdentifier->expirationMonth ?? null,
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                '',
                [],
                $template->userFriendlyIdentifier->label ?? null
            );
            $template->setIsSafe(!$isPaymentTemplateValidationEnabled);

            $collection->add($template);
        }

        return $collection;
    }

    /**
     * @return bool
     */
    public function isSafeSelectedTemplate(): bool
    {
        /** @var PaymentTemplate $paymentTemplate */
        foreach ($this->getValues() as $paymentTemplate) {
            if ($paymentTemplate->isSelected() && $paymentTemplate->isSafe()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->getValues() as $object) {
            $data[] = $object->toArray();
        }

        return $data;
    }
}
