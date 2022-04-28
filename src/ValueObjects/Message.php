<?php

namespace SamuelMwangiW\Africastalking\ValueObjects;

use Illuminate\Support\Collection;
use SamuelMwangiW\Africastalking\Contracts\DTOContract;
use SamuelMwangiW\Africastalking\Exceptions\AfricastalkingException;
use SamuelMwangiW\Africastalking\Transporter\Requests\Messaging\BulkSmsRequest;
use SamuelMwangiW\Africastalking\Transporter\Requests\Messaging\PremiumSmsRequest;

class Message implements DTOContract
{
    public int $bulkSMSMode = 1;
    public bool $isBulk = true;
    public int $enqueue = 1;
    public string|null $keyword = null;
    public string|null $linkId = null;
    public int|null $retryDurationInHours = null;

    /**
     * @param string $message
     * @param Collection<int,PhoneNumber>|null $to
     * @param string|null $from
     */
    public function __construct(
        public string|null     $message = null,
        public Collection|null $to = null,
        public string|null     $from = null,
    ) {
    }

    public function enqueue(bool $value = true): static
    {
        $this->enqueue = $value ? 1 : 0;

        return $this;
    }

    public function as(string|null $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function text(string|null $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function message(string|null $message): static
    {
        return $this->text($message);
    }

    /**
     * @param Collection<int,PhoneNumber>|string|array $recipients
     * @return $this
     */
    public function to(Collection|string|array $recipients): static
    {
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        if (is_array($recipients)) {
            $recipients = collect($recipients)->map(fn ($phone) => PhoneNumber::make($phone));
        }

        $this->to = $recipients;

        return $this;
    }

    public function bulk(): static
    {
        $this->isBulk = true;

        return $this;
    }

    public function premium(): static
    {
        $this->isBulk = false;
        $this->bulkSMSMode = 0;

        return $this;
    }

    public function bulkMode(int $value = 1): static
    {
        $this->bulkSMSMode = $value;

        return $this;
    }

    public function keyword(string|null $value): static
    {
        $this->keyword = $value;

        return $this;
    }

    public function linkId(string|null $value): static
    {
        $this->linkId = $value;

        return $this;
    }

    public function retry(int $value): static
    {
        $this->retryDurationInHours = $value;

        return $this;
    }

    /**
     * @return Collection<int,RecipientsApiResponse>
     * @throws \Illuminate\Http\Client\RequestException
     * @throws AfricastalkingException
     */
    public function send(): Collection
    {
        $response = $this
            ->request()
            ->asForm()
            ->withData($this->data())
            ->fetch();

        if (! data_get($response, 'SMSMessageData.Recipients')) {
            throw AfricastalkingException::messageSendingFailed(
                message: data_get($response, 'SMSMessageData.Message')
            );
        }

        /** @phpstan-ignore-next-line */
        return collect(data_get($response, 'SMSMessageData.Recipients'))
            ->map(fn (array $recipient) => RecipientsApiResponse::make($recipient));
    }

    protected function from(): ?string
    {
        $from = $this->from ?? config('africastalking.from');

        return blank($from) ? null : $from;
    }

    private function request(): BulkSmsRequest|PremiumSmsRequest
    {
        return $this->isBulk ? BulkSmsRequest::build() : PremiumSmsRequest::build();
    }

    public function __toString(): string
    {
        return strval(json_encode($this));
    }

    public function __toArray(): array
    {
        return [
            'bulkSMSMode' => $this->bulkSMSMode,
            'enqueue' => $this->enqueue,
            'keyword' => $this->keyword,
            'linkId' => $this->linkId,
            'retryDurationInHours' => $this->retryDurationInHours,
            'message' => $this->message,
            'to' => $this->to?->toArray(),
            'from' => $this->from(),
            'isBulk' => $this->isBulk,
            'isPremium' => ! $this->isBulk,
        ];
    }

    private function data(): array
    {
        $data = [
            'enqueue' => $this->enqueue,
            'keyword' => $this->keyword,
            'linkId' => $this->linkId,
            'retryDurationInHours' => $this->retryDurationInHours,
            'message' => $this->message,
            'to' => $this->to
                ?->filter(fn (PhoneNumber $number) => $number->isValid())
                ->map(fn (PhoneNumber $number) => $number->number)
                ->implode(','),
        ];

        return array_merge(
            array_filter($data),
            ['from' => $this->from(), 'bulkSMSMode' => $this->bulkSMSMode]
        );
    }
}
