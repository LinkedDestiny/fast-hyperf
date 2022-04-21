<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Method;

class MethodParameter
{
    private bool $isRequestBody = false;

    private bool $isRequestFormData = false;

    private bool $isRequestQuery = false;

    private bool $isValid = false;

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    public function isRequestBody(): bool
    {
        return $this->isRequestBody;
    }

    public function setIsRequestBody(bool $isRequestBody): MethodParameter
    {
        $this->isRequestBody = $isRequestBody;
        return $this;
    }

    public function isRequestFormData(): bool
    {
        return $this->isRequestFormData;
    }

    public function setIsRequestFormData(bool $isRequestFormData): MethodParameter
    {
        $this->isRequestFormData = $isRequestFormData;
        return $this;
    }

    public function isRequestQuery(): bool
    {
        return $this->isRequestQuery;
    }

    public function setIsRequestQuery(bool $isRequestQuery): MethodParameter
    {
        $this->isRequestQuery = $isRequestQuery;
        return $this;
    }
}
