<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use DateInterval;
use DatePeriod;

class AppointmentSlot extends Model
{
    use HasFactory;

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public static function getAvailableTimeSlots($date)
    {
        
        $startTime = new DateTime('9:00');
        $endTime = new DateTime('21:00');
        $interval = new DateInterval('PT1H30M');

        $bookedSlots = self::where('date', $date)->where('is_booked', true)->get();
        $bookedTimes = $bookedSlots->map(function ($slot) {
            return [
                new DateTime($slot->start_time),
                new DateTime($slot->end_time)
            ];
        });

        $freeTimes = [];
        $periods = new DatePeriod($startTime, $interval, $endTime);
        foreach ($periods as $period) {
            $available = true;
            foreach ($bookedTimes as [$bookedStart, $bookedEnd]) {
                if ($period >= $bookedStart && $period < $bookedEnd) {
                    $available = false;
                    break;
                }
            }
            if ($available) {
                $freeTimes[] = $period->format('H:i');
            }
        }

        return $freeTimes;
    }
}

