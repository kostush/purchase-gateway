<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataEnvironmentData
{
    /**
     * @var string
     */
    private $ndSessionId;

    /**
     * @var string|null
     */
    private $ndWidgetData;

    /**
     * @var string|null
     */
    private $remoteIp;

    /**
     * @var string|null
     */
    private $requestUrl;

    /**
     * @var string|null
     */
    private $userAgent;

    /**
     * @var string|null
     */
    private $xForwardedFor;

    /**
     * NuDataEnvironmentData constructor.
     * @param string      $ndSessionId   NuData Session Id
     * @param string|null $ndWidgetData  NuData Widget Data
     * @param string|null $remoteIp      Remote Ip
     * @param string|null $requestUrl    Request Url
     * @param string|null $userAgent     User Agent
     * @param string|null $xForwardedFor x-Forwarded-For
     */
    public function __construct(
        string $ndSessionId,
        ?string $ndWidgetData,
        ?string $remoteIp,
        ?string $requestUrl,
        ?string $userAgent,
        ?string $xForwardedFor
    ) {
        $this->ndSessionId   = $ndSessionId;
        $this->ndWidgetData  = $ndWidgetData;
        $this->remoteIp      = $remoteIp;
        $this->requestUrl    = $requestUrl;
        $this->userAgent     = $userAgent;
        $this->xForwardedFor = $xForwardedFor;
    }

    /**
     * @return string
     */
    public function ndSesssionId(): string
    {
        return $this->ndSessionId;
    }

    /**
     * @return string|null
     */
    public function ndWidgetData(): ?string
    {
        return $this->ndWidgetData;
    }

    /**
     * @return string|null
     */
    public function remoteIp(): ?string
    {
        return $this->remoteIp;
    }

    /**
     * @return string|null
     */
    public function requestUrl(): ?string
    {
        return $this->requestUrl;
    }

    /**
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @return string|null
     */
    public function xForwardedFor(): ?string
    {
        return $this->xForwardedFor;
    }
}
