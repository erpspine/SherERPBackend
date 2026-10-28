<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RemindExpiredShortTermContracts extends Command
{
    protected $signature = 'contracts:remind-expired-short-term';

    protected $description = 'Notify Madikizela when a driver Short Term contract has expired';

    public function handle(SmsService $sms): int
    {
        $recipient = User::query()
            ->where('email', 'madikizela@sher.co.tz')
            ->first();

        if (! $recipient) {
            $this->error('Reminder recipient madikizela@sher.co.tz was not found.');
            return self::FAILURE;
        }

        $expiredDrivers = User::query()
            ->role('Driver')
            ->where('contract_type', 'Short Term')
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '<', today())
            ->whereNull('contract_expiry_notified_at')
            ->get();

        foreach ($expiredDrivers as $driver) {
            $subject = 'Expired Short Term Driver Contract: ' . $driver->name;
            $message = implode("\n", [
                'Driver contract expiry reminder',
                '',
                'Driver: ' . $driver->name,
                'Email: ' . $driver->email,
                'Phone: ' . ($driver->phone ?: '-'),
                'Contract type: Short Term',
                'Contract from: ' . optional($driver->contract_start_date)->format('d M Y'),
                'Contract to: ' . optional($driver->contract_end_date)->format('d M Y'),
                '',
                'Please review this driver contract in SHER ERP.',
            ]);

            $emailSent = false;
            try {
                Mail::raw($message, function ($mail) use ($recipient, $subject): void {
                    $mail->to($recipient->email)->subject($subject);
                });
                $emailSent = true;
            } catch (\Throwable $exception) {
                report($exception);
            }

            $smsSent = $recipient->phone
                ? $sms->send($recipient->phone, $subject . '. Driver: ' . $driver->name . '. Contract expired on ' . optional($driver->contract_end_date)->format('d M Y') . '.')
                : false;

            if ($emailSent || $smsSent) {
                $driver->forceFill(['contract_expiry_notified_at' => now()])->save();
                $this->info('Reminder sent for ' . $driver->name . '.');
            } else {
                $this->warn('No reminder channel succeeded for ' . $driver->name . '.');
            }
        }

        return self::SUCCESS;
    }
}
