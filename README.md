# africastalking-laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/samuelmwangiw/africastalking-laravel.svg?style=flat-square)](https://packagist.org/packages/samuelmwangiw/africastalking-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/samuelmwangiw/africastalking-laravel/run-tests?label=tests)](https://github.com/samuelmwangiw/africastalking-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan](https://github.com/SamuelMwangiW/africastalking-laravel/actions/workflows/phpstan.yml/badge.svg)](https://github.com/SamuelMwangiW/africastalking-laravel/actions/workflows/phpstan.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/samuelmwangiw/africastalking-laravel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/samuelmwangiw/africastalking-laravel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/samuelmwangiw/africastalking-laravel.svg?style=flat-square)](https://packagist.org/packages/samuelmwangiw/africastalking-laravel)

This is an unofficial Laravel SDK for interacting with [Africastalking](https://developers.africastalking.com/docs/sms/overview) APIs that takes advantage of native Laravel components such as 
- [HTTP Client](https://laravel.com/docs/9.x/http-client#main-content) in place of Guzzle client
- [Service Container](https://laravel.com/docs/9.x/container#main-content) for a great dev experience
- [Notifications](https://laravel.com/docs/9.x/notifications) to allow you route notifications via Africastalking
- [Config](https://laravel.com/docs/9.x/configuration#main-content)
- [Collections](https://laravel.com/docs/9.x/collections#main-content)
- Among many others

Note: This package is a work in progress and might take a while to complete. If you are in need of a stable sdk, kindly check out the [official PHP SDK](https://github.com/africastalkingltd/africastalking-php) maintained by the Africastalking team

## Installation

You can install the package via composer:

```bash
composer require samuelmwangiw/africastalking-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="africastalking-config"
```

This is the contents of the published config file:

```php
return [
    'username' => env('AFRICASTALKING_USERNAME','sandbox'),
    'api-key' => env('AFRICASTALKING_API_KEY'),
    'from' => env('AFRICASTALKING_FROM'),
];
```

You should configure the package by setting the `env` variables in your `.env` file.

## Usage
### Application Balance

```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

/** @var \SamuelMwangiW\Africastalking\ValueObjects\Account $account */
$account = Africastalking::application()->balance();
```
### Bulk Messages
The most basic example to send out a message is
```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

$response = Africastalking::sms('Hello mom!')
        ->to('+254712345678')
        ->send();
```
Other valid examples are
```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

$response = Africastalking::sms('It is quality rather than quantity that matters. - Lucius Annaeus Seneca')
        ->as('MyBIZ') // optional: When the senderId is different from `config('africastalking.from')`
        ->to(['+254712345678','+256706123567'])
        ->bulk() // optional: Messages are bulk by default
        ->enqueue() //used for Bulk SMS clients that would like to deliver as many messages to the API before waiting for an acknowledgement from the Telcos
        ->send()
```

The response is Collection of `\SamuelMwangiW\Africastalking\ValueObjects\RecipientsApiResponse` objects
### Premium Messages
```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

$response = Africastalking::sms('It is quality rather than quantity that matters. - Lucius Annaeus Seneca')
        ->as('90012') // optional: When the senderId is different from `config('africastalking.from')`
        ->to(['+254712345678','+256706123567'])
        ->premium() // Required to designate messages as bulk
        ->bulkMode(false) // True to send premium messages in bulkMode and false to send as premium
        ->retry(2) //specifies the number of hours your subscription message should be retried in case it’s not delivered to the subscriber.
        ->keyword('keyword') // optional:
        ->linkId('message-link-id') // optional:
        ->send()

```
The response is Collection of `\SamuelMwangiW\Africastalking\ValueObjects\RecipientsApiResponse` objects
### Airtime
The most basic example to disburse airtime is
```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;

$response = Africastalking::airtime()
        ->to('+254712345678','KES',100)
        ->send();
```

You may also pass an instance of `AirtimeTransaction`

```php
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\ValueObjects\AirtimeTransaction;
use SamuelMwangiW\Africastalking\ValueObjects\PhoneNumber;
use SamuelMwangiW\Africastalking\Enum\Currency;

$transaction = new AirtimeTransaction(PhoneNumber::make('+256769000000'),Currency::UGANDA,1000)

$response = Africastalking::airtime()
        ->to($transaction)
        ->send();
```

The Airtime class provides an `add()` that's basically an alias to the `to()` and since either of these methods can be fluently chained, it unlocks capabilities such as adding the recipients in a loop and sending once at the end

```php
use App\Models\Clients;
use SamuelMwangiW\Africastalking\Facades\Africastalking;

$airtime = Africastalking::airtime();

Clients::query()->chunk(1000, function ($clients) use($airtime) {
    foreach ($clients as $client) {
        $airtime->add($client->phone,'TZS',3000);
    }
});
$results = $airtime->send();
```

### Payments (Pending)
WIP
### Voice (Pending)
WIP
### IOT (Pending)
WIP

## HTTP Requests
The package ships with the following [Laravel Requests](https://laravel.com/docs/9.x/validation#creating-form-requests) that you can inject into your application controllers:

```php
\SamuelMwangiW\Africastalking\Http\Requests\AirtimeStatusRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\AirtimeValidationRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\BulkSmsOptOutRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\IncomingMessageRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\MessageDeliveryRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\SubscriptionRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\UssdEventRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\UssdSessionRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\VoiceCallRequest::class;
\SamuelMwangiW\Africastalking\Http\Requests\VoiceEventRequest::class;
```

Example for a Message Delivery callback action Controller

```php
<?php

namespace App\Http\Controllers\Messaging;

use App\Models\Message;
use SamuelMwangiW\Africastalking\Http\Requests\MessageDeliveryRequest;

class MessageDeliveredController{
    public function __invoke(MessageDeliveryRequest $request)
    {
        $message = Message::query()
                            ->where(['transaction_id'=>$request->id()])
                            ->firstOrFail();
                            
        $message->markAsDelivered();
        
        return response('OK');
    }
}

```

## Notification

The package ships with a Channel to allow for easily routing of notifications via Africastalking SMS.

To route a notification via Africastalking, return `SamuelMwangiW\Africastalking\Notifications\AfricastalkingChannel` in your notifications `via` method and the text message to be sent in the `toAfricastalking` method

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use SamuelMwangiW\Africastalking\Facades\Africastalking;
use SamuelMwangiW\Africastalking\Notifications\AfricastalkingChannel;

class ExampleNotification extends Notification
{
    public function via($notifiable)
    {
        return [AfricastalkingChannel::class];
    }

    public function toAfricastalking($notifiable)
    {
        return 'Basic Notification message.';
    }
}

```

Also ensure that the notifiable model implements `SamuelMwangiW\Africastalking\Contracts\ReceivesSmsMessages` and that the model's `routeNotificationForAfricastalking()` returns the phone number to receive the message

```php
<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use SamuelMwangiW\Africastalking\Contracts\ReceivesSmsMessages;

class User implements ReceivesSmsMessages
{
    protected $guarded = [];

    public function routeNotificationForAfricastalking(Notification $notification): string
    {
        return $this->phone;
    }
}

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Samuel Mwangi](https://github.com/SamuelMwangiW)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
