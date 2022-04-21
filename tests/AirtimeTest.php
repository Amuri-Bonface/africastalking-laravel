<?php

use Illuminate\Support\Collection;
use function Pest\Faker\faker;
use SamuelMwangiW\Africastalking\Domain\Airtime;
use SamuelMwangiW\Africastalking\Enum\Currency;
use SamuelMwangiW\Africastalking\Exceptions\AfricastalkingException;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\AirtimeTransaction;
use SamuelMwangiW\Africastalking\ValueObjects\PhoneNumber;

it('resolves the application class')
    ->expect(fn () => Africastalking::airtime())
    ->toBeInstanceOf(Airtime::class);

it('can add a recipient', function (string $phone, string $currency) {
    $service = Africastalking::airtime()
        ->to(
            phoneNumber: $phone,
            currencyCode: $currency,
            amount: faker()->numberBetween(100, 500)
        );

    expect($service)
        ->recipients->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->recipients->first()->phoneNumber->toBeInstanceOf(PhoneNumber::class)
        ->recipients->first()->currencyCode->toBeInstanceOf(Currency::class);
})->with('phone-numbers', 'currencies');

it('can add a recipient from a transaction object', function (AirtimeTransaction $transaction) {
    $service = Africastalking::airtime()->to($transaction);

    expect($service)
        ->recipients->toHaveCount(1)
        ->recipients->each(
            fn ($recipient) => $recipient
                ->phoneNumber->toBe($transaction->phoneNumber)
                ->currencyCode->toBe($transaction->currencyCode)
                ->amount->toBeInt()
        );
})->with('airtime-transactions');

it('can add multiple recipients', function (string $phone, string $currency) {
    $service = Africastalking::airtime()
        ->add(
            phoneNumber: $phone,
            currencyCode: $currency,
            amount: faker()->numberBetween(100, 500)
        )
        ->add(
            phoneNumber: faker()->e164PhoneNumber(),
            currencyCode: $currency,
            amount: faker()->numberBetween(100, 500)
        );

    expect($service)
        ->recipients->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->recipients->each(
            fn ($recipient) => $recipient
                ->phoneNumber->toBeInstanceOf(PhoneNumber::class)
                ->currencyCode->toBeInstanceOf(Currency::class)
                ->amount->toBeInt()
        );
})->with('phone-numbers', 'currencies');

it('throws an exception for invalid currency', function (string $phone) {
    Africastalking::airtime()
        ->to(
            phoneNumber: $phone,
            currencyCode: 'KPW',
            amount: faker()->numberBetween(100, 500)
        );
})->with('phone-numbers')->throws(AfricastalkingException::class);

it('throws an exception for amounts less than 5', function (string $phone) {
    Africastalking::airtime()
        ->to(
            phoneNumber: $phone,
            amount: 1
        );
})->with('phone-numbers')->throws(AfricastalkingException::class);

it('sends airtime to a single recipient', function (AirtimeTransaction $transaction) {
    $result = Africastalking::airtime()->to($transaction)->send();

    expect($result)
        ->toBeArray()
        ->toHaveKeys([
            'errorMessage',
            'numSent',
            'totalAmount',
            'totalDiscount',
            'responses',
        ]);

    expect($result['responses'])
        ->toBeArray()
        ->toHaveCount(1);

    expect($result['responses'][0])->toHaveKeys(['phoneNumber','errorMessage','requestId','discount']);

    expect(data_get($result, 'numSent'))->toBe(1);
})->with('airtime-transactions');

it('sends airtime to multiple recipients', function (AirtimeTransaction $transaction) {
    $result = Africastalking::airtime()
        ->to($transaction)
        ->to(phoneNumber: '+254712345678', amount: 10)
        ->send();

    expect($result)
        ->toBeArray()
        ->toHaveKeys([
            'errorMessage',
            'numSent',
            'totalAmount',
            'totalDiscount',
            'responses',
        ]);

    expect($result['responses'])
        ->toBeArray()
        ->toHaveCount(2);

    expect($result['responses'][0])->toHaveKeys(['phoneNumber','errorMessage','requestId','discount']);

    expect(data_get($result, 'numSent'))->toBe(2);
})->with('airtime-transactions');
