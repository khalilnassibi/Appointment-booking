<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use DateTime;
use DateInterval;

class BookAppointmentController extends Controller
{
    //


    public function getAvailableSlots(Request $request)
    {
        //\Log::info(json_encode($request->all())); 
        $availableSlots = AppointmentSlot::getAvailableTimeSlots($request->date);
    
        return response()->json([
            'availableSlots' => $availableSlots
        ]);
    }

    public function store(Request $request)
    {
        // get the selected date and time slot
        $selectedDate = $request->input('date');
        $selectedSlot = $request->input('time');
        $selectedSlotEndTime = strtotime($selectedSlot) + 60*60;

        $selectedSlotend_time = date('H:i', $selectedSlotEndTime);


        // check if the selected time slot for the selected date is still available
        $slot = AppointmentSlot::where('start_time', $selectedSlot)
                    ->where('date', $selectedDate)
                    ->where('is_booked', false)
                    ->first();

        // If the time slot is not available, create a new one
        if (!$slot) {
            $slot = new AppointmentSlot();
            $slot->start_time = $selectedSlot;
            $slot->end_time = $selectedSlotend_time;
            $slot->date = $selectedDate;
            $slot->is_booked = false;
            $slot->save();
        }

        // lock the selected time slot to prevent race conditions
        $slot->lockForUpdate();

        // create a new appointment and associate it with the selected slot
        $appointment = new Appointment();
        $appointment->name = $request->input('name');
        $appointment->email = $request->input('email');
        $appointment->appointmentSlot()->associate($slot);
        $appointment->save();

        // mark the selected time slot as booked
        $slot->is_booked = true;
        $slot->save();

        // return a success response
        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully.',
        ]);
    }
}
