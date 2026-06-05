<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Backwards-compatible single-recipient send.
     * Returns true on success, false otherwise.
     */
    public function send(?string $phoneNumber, string $message): bool
    {
        if ($phoneNumber === null || trim($phoneNumber) === '') {
            return false;
        }

        $result = $this->sendSingle($phoneNumber, $message);

        return (bool) ($result['success'] ?? false);
    }

    /**
     * Send SMS to a single phone number.
     */
    public function sendSingle(string $phoneNumber, string $message, ?string $reference = null): array
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);

        return $this->sendMultiple([$formatted], $message, $reference);
    }

    /**
     * Send SMS to multiple phone numbers.
     */
    public function sendMultiple(array $phoneNumbers, string $message, ?string $reference = null): array
    {
        if (! config('sms.enabled')) {
            return [
                'success' => false,
                'message' => 'SMS service is disabled',
                'data' => null,
            ];
        }

        $endpoint = (string) (config('sms.test_mode')
            ? config('sms.test_endpoint')
            : config('sms.endpoint'));
        $authorization = $this->buildAuthorizationHeader();
        $from = (string) config('sms.from');
        $timeout = (int) config('sms.timeout', 30);

        if ($endpoint === '' || $authorization === '') {
            Log::warning('[SMS] Skipping send — endpoint or credentials not configured.');

            return [
                'success' => false,
                'message' => 'SMS service is not configured',
                'data' => null,
            ];
        }

        $formattedNumbers = array_values(array_filter(array_map(
            [$this, 'formatPhoneNumber'],
            $phoneNumbers
        )));

        if (empty($formattedNumbers)) {
            return [
                'success' => false,
                'message' => 'No valid phone numbers provided',
                'data' => null,
            ];
        }

        if (! $reference) {
            $reference = 'SMS_' . Str::random(10) . '_' . time();
        }

        $payload = [
            'from' => $from,
            'to' => $formattedNumbers,
            'text' => $message,
            'reference' => $reference,
        ];

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => $timeout,
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (config('sms.log_requests')) {
                Log::info('SMS sent successfully', [
                    'phone_numbers' => $formattedNumbers,
                    'message_length' => strlen($message),
                    'reference' => $reference,
                    'response' => $responseData,
                ]);
            }

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => [
                    'reference' => $reference,
                    'phone_numbers' => $formattedNumbers,
                    'response' => $responseData,
                    'status_code' => $response->getStatusCode(),
                ],
            ];
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : null;
            $responseBody = $response ? $response->getBody()->getContents() : null;

            Log::error('SMS sending failed', [
                'phone_numbers' => $formattedNumbers,
                'message_length' => strlen($message),
                'reference' => $reference,
                'error' => $errorMessage,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $errorMessage,
                'data' => [
                    'reference' => $reference,
                    'phone_numbers' => $formattedNumbers,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('SMS sending failed (unexpected)', [
                'phone_numbers' => $formattedNumbers,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Format Tanzanian phone numbers (255XXXXXXXXX).
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber) ?? '';

        if ($cleaned === '') {
            return '';
        }

        if (str_starts_with($cleaned, '0')) {
            return '255' . substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '255')) {
            return $cleaned;
        }

        if (strlen($cleaned) === 9) {
            return '255' . $cleaned;
        }

        return $cleaned;
    }

    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);

        return (bool) preg_match('/^255[67]\d{8}$/', $formatted);
    }

    /**
     * Build the Authorization header per messaging-service.co.tz docs:
     * Basic <base64(username:password)> (RFC2045-MIME).
     * Falls back to a pre-encoded SMS_AUTH_HEADER if username/password aren't set.
     */
    protected function buildAuthorizationHeader(): string
    {
        $username = (string) config('sms.username');
        $password = (string) config('sms.password');

        if ($username !== '' && $password !== '') {
            return 'Basic ' . base64_encode($username . ':' . $password);
        }

        $raw = trim((string) config('sms.auth_header'));
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^(Basic|Bearer)\s+/i', $raw) === 1) {
            return $raw;
        }

        return 'Basic ' . $raw;
    }

    public function getStatus(): array
    {
        return [
            'enabled' => (bool) config('sms.enabled'),
            'driver' => config('sms.driver'),
            'endpoint' => config('sms.test_mode')
                ? config('sms.test_endpoint')
                : config('sms.endpoint'),
            'test_mode' => (bool) config('sms.test_mode'),
            'auth_mode' => (string) config('sms.username') !== ''
                && (string) config('sms.password') !== ''
                ? 'username_password'
                : ((string) config('sms.auth_header') !== '' ? 'auth_header' : 'none'),
            'from' => config('sms.from'),
            'timeout' => (int) config('sms.timeout', 30),
            'log_requests' => (bool) config('sms.log_requests'),
        ];
    }
}
