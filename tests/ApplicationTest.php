<?php

use SamuelMwangiW\Africastalking\Domain\Application;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\AccountDTO;

it('resolves the application class')
    ->expect(fn () => Africastalking::application())
    ->toBeInstanceOf(Application::class);

it('can fetch the application balance')
    ->expect(fn () => Africastalking::application()->balance())
    ->toBeInstanceOf(AccountDTO::class)
    ->currency->toBe('KES');
