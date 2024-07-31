<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function setReview(Room $room, Request $request) {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        // Check if the user has made a booking for the room and the check-out date has passed
        $booking = Booking::where('user_id', Auth::id())
                          ->where('room_id', $room->id)
                          ->where('check_out_date', '<', now())
                          ->first();

        if (!$booking) {
            return response()->json(['error' => 'You must have stayed in this room and checked out to leave a review.'], 403);
        }

        // Check if the user has already left a review for this room
        $existing_review = Review::where('user_id', Auth::id())
                                 ->where('room_id', $room->id)
                                 ->first();

        if ($existing_review) {
            // Update the existing review
            $existing_review->rating = $request->rating;
            $existing_review->comment = $request->comment;
            $existing_review->save();
        } else {
            // Create a new review
            $room->reviews()->create([
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
        }

        // Update the room's average rating
        $averageRating = $room->calculateAverageRating();
        $room->average_rating = $averageRating;
        $room->save();

        return response()->json(['message' => 'Rating saved successfully.']);
    }

    public function showRoomReviews($room_id)
    {
        // Retrieve the product by its ID
        $room = Room::findOrFail($room_id);

        // Retrieve all the reviews associated with the product, and select only the comment  data
        $reviews = $room->reviews()->select('comment','rating','user_id')->get();

        // Return the reviews data as a JSON response
        return response()->json($reviews);
    }
}
