<?php

namespace App\Http\Controllers;

use App\Models\BookingRequest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    //
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    $request->validateWithBag('createReservationsRequest', array(
      'room_id' => ['required', 'integer', 'exists:rooms,id'],
      'recurrences' => ['required'],
      'recurrences.*' => ['array', 'size:2'],
      'recurrences.*.start_time' => ['required', 'date'],
      'recurrences.*.end_time' => ['required', 'date'],
    ));

    $request->validateWithBag('createReservationsRequest', array(
      'recurrences.*' => ['array', 'size:2',
        function ($attribute, $value, $fail) use ($request){
          $room = Room::query()->findOrFail($request->room_id);
          $room->verifyDatesAreWithinRoomRestrictionsValidation($value['start_time'], $fail);
          $room->verifyDatetimesAreWithinAvailabilitiesValidation($value['start_time'], $value['end_time'], $fail);

        }
      ]
    ));

//    $room = Room::query()->findOrFail($request->room_id);
//    $room->verifyDatetimesAreWithinAvailabilities($request->get('start_time'), $request->get('end_time'));
//    $room->verifyDatesAreWithinRoomRestrictions($request->get('start_time'), $request->get('end_time'));
//    //lazy for now
    $booking = BookingRequest::create([
      'user_id' => $request->user()->id,
      'status' => "review",
      'reference' => ["path" => '']//not sure what to do here tbh
    ]);

    foreach ($request->recurrences as $pair){
      $reservation = new Reservation();
      $reservation->room_id = $request->room_id;
      $reservation->booking_request_id = $booking->id;
      $reservation->start_time = $pair['start_time'];
      $reservation->end_time = $pair['end_time'];
      $reservation->save();
    }

    return back();
  }

  /**
   * Display the specified resource.
   *
   * @param Reservation $reservation
   * @return \Illuminate\Http\Response
   */
  public function show(Reservation $reservation)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param Reservation $reservation
   * @return \Illuminate\Http\Response
   */
  public function edit(Reservation $reservation)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param \Illuminate\Http\Request $request
   * @param Reservation $reservation
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, Reservation $reservation)
  {
    $request->validateWithBag('updateBookingRequest', array(
      'room_id' => ['bail', 'required', 'integer', 'exists:rooms,id'],
      'recurrences' => ['bail', 'required'],
      'recurrences.*.start_time' => ['bail', 'required', 'date'],
      'recurrences.*.end_time' => ['bail', 'required', 'date'],
      'recurrences.*' => ['array', 'size:2',
        function ($attribute, $value, $fail) use ($request) {

          $room = Room::query()->findOrFail($request->room_id);
          $room->verifyDatesAreWithinRoomRestrictionsValidation($value->start_time, $fail);

        },
      ],
    ));

    $room = Room::query()->findOrFail($request->room_id);
    $room->verifyDatetimesAreWithinAvailabilities($request->get('start_time'), $request->get('end_time'));
    $room->verifyDatesAreWithinRoomRestrictions($request->get('start_time'), $request->get('end_time'));
    $reservation->room_id = $request->room_id;
    $reservation->start_time = $request->start_time;
    $reservation->end_time = $request->end_time;
    $reservation->save();

    return back();
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param Reservation $reservation
   * @return RedirectResponse
   * @throws \Exception
   */
  public function destroy(Reservation $reservation): RedirectResponse
  {
    $booking = $reservation->bookingRequest()->first();
    $reservation->delete();
    if (!$booking->reservations()->exists()) {
      $booking->delete();
    }
    return back();
  }
}
