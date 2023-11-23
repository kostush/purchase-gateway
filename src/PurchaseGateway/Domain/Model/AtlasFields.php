<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class AtlasFields
{
    /**
     * @var string
     */
    private $atlasCode;

    /**
     * @var string
     */
    private $atlasData;

    /**
     * AtlasFields constructor.
     * @param string|null $atlasCode Atlas Data
     * @param string|null $atlasData Atlas Code
     */
    private function __construct(?string $atlasCode = null, ?string $atlasData = null)
    {
        $this->atlasCode = $atlasCode;
        $this->atlasData = $atlasData;
    }

    /**
     * AtlasFields create.
     * @param string|null $atlasCode Atlas Data
     * @param string|null $atlasData Atlas Code
     * @return AtlasFields
     */
    public static function create(?string $atlasCode = null, ?string $atlasData = null): self
    {
        return new static($atlasCode, $atlasData);
    }

    /**
     * @return string|null
     */
    public function atlasCode(): ?string
    {
        return $this->atlasCode;
    }

    /**
     * @return string|null
     */
    public function atlasData(): ?string
    {
        return $this->atlasData;
    }

    /**
     * if decoded will return an array
     * if error while decoding will return the original string into array
     * if no value given will return null
     *
     * @return array|null
     */
    public function atlasCodeDecoded(): ?array
    {
        if(!$this->atlasCode)
            return null;

        try{
            $decoded = json_decode(base64_decode($this->atlasCode), true);

            return $decoded ?? [$this->atlasCode];
        } catch(\Exception $exception){
            return [$this->atlasCode];
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'atlasCode' => $this->atlasCode(),
            'atlasData' => $this->atlasData()
        ];
    }
}
