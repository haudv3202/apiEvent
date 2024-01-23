<?php

namespace App\Console\Commands;

use App\Mail\EmailApi;
use App\Models\event;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class calendarNotifitionEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calendar-notifition-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDateTime = \Illuminate\Support\Carbon::now();
        $dateCr = $currentDateTime->toDateTimeString();
        $fiveHoursAhead = $currentDateTime->addHours(12)->toDateTimeString();
        $events = event::where('start_time', '>=', $dateCr)
            ->with(['attendances.user', 'user','notifications'])
            ->where('start_time', '<', $fiveHoursAhead)
            ->where('status', 2)
            ->where('notification_sent', false)
            ->get();
        $notificationsToUpdateEvent = [];
        foreach ($events as $item) {
            if (!empty($item->attendances)) {
                foreach ($item->attendances as $userSend) {
                    $data = [
                        'title' => "EMAIL NHẮC NHỞ SỰ KIỆN " . $item->name,
                        'message' => $item->notifications->last()->content,
                    ];

                    Mail::to($userSend->user->email)->send(new EmailApi($data));
                    Log::info('Email sent successfully:' . $item->name);
                    $notificationsToUpdateEvent[] = $item->id;
//                    $item->update(['notification_sent' => true]);
                }
            }else {
                Log::info('Không có người tham gia sự kiện ' . $item->name . ' nên không gửi email' .
                    ' vào lúc ' . $dateCr . 'id sự kiện ' . $item->id);
            }
        }
        event::whereIn('id', $notificationsToUpdateEvent)->update(['notification_sent' => true]);

        return 0;

    }
}
