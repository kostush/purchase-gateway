<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ReturnResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;

class ProcessPurchaseMgpgToNgService
{
    /**
     * @var ProcessPurchaseDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * ProcessPurchaseController constructor.
     * @param PostbackService $postbackService
     */
    public function __construct(
        PostbackService $postbackService
    )
    {
        $this->postbackService = $postbackService;
    }

    /**
     * @param GenericPurchaseProcess      $purchaseProcess
     * @param ProcessPurchaseDTOAssembler $dtoAssembler
     * @return mixed|void
     */
    public function translate(
        GenericPurchaseProcess $purchaseProcess,
        ProcessPurchaseDTOAssembler $dtoAssembler
    ): ProcessPurchaseHttpDTO
    {
        return $dtoAssembler->assemble($purchaseProcess);
    }

    /**
     * @param GenericPurchaseProcess $purchaseProcess
     * @param ProcessPurchaseHttpDTO $dto
     * @param string                 $postbackUrl
     */
    public function queuePostback(
        GenericPurchaseProcess $purchaseProcess,
        ProcessPurchaseHttpDTO $dto,
        string $postbackUrl
    )
    {
        $hasCompletedProcess = $dto instanceof ProcessPurchaseGeneralHttpDTO;
        if ($hasCompletedProcess) {
            $this->postbackService->queue(
                $this->buildPostbackDto($purchaseProcess, $dto),
                $postbackUrl
            );
        }
    }

    /**
     * @param GenericPurchaseProcess        $purchaseProcess
     * @param ProcessPurchaseGeneralHttpDTO $dto Process Purchase General Http DTO
     * @return PostbackResponseDto
     */
    public function buildPostbackDto(
        GenericPurchaseProcess $purchaseProcess,
        ProcessPurchaseGeneralHttpDTO $dto
    ): PostbackResponseDto
    {
        return PostbackResponseDto::createFromResponseData(
            $dto,
            $dto->tokenGenerator(),
            $purchaseProcess->publicKeyIndex(),
            $purchaseProcess->sessionId(),
            $purchaseProcess->retrieveMainPurchaseItem(),
            $purchaseProcess->retrieveProcessedCrossSales()
        );
    }

    /**
     * @param GenericPurchaseProcess        $purchaseProcess
     * @param ProcessPurchaseGeneralHttpDTO $dto Process Purchase General Http DTO
     * @return ReturnResponseDto
     */
    public function buildReturnDto(
        GenericPurchaseProcess $purchaseProcess,
        ProcessPurchaseGeneralHttpDTO $dto
    ): ReturnResponseDto
    {
        return ReturnResponseDto::createFromResponseData(
            $dto,
            $dto->tokenGenerator(),
            $purchaseProcess->publicKeyIndex(),
            $purchaseProcess->sessionId(),
            $purchaseProcess->retrieveMainPurchaseItem(),
            $purchaseProcess->retrieveProcessedCrossSales()
        );
    }
}
