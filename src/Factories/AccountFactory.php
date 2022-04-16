<?php

namespace SamuelMwangiW\Africastalking\Factories;

use Illuminate\Support\Str;
use SamuelMwangiW\Africastalking\Enum\Currency;
use SamuelMwangiW\Africastalking\ValueObjects\Account;

class AccountFactory implements \SamuelMwangiW\Africastalking\Contracts\FactoryContract
{
    public static function make(array $data): Account
    {
        $balance = Str::of(data_get($data, 'UserData.balance'));
        $currencyCode = $balance->before(' ')->toString();

        return new Account(
            balance: floatval($balance->after(' ')->toString()),
            currency: Currency::tryFrom($currencyCode) ?? Currency::INTERNATIONAL,
        );
    }
}
