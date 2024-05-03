<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;
use Illuminate\Http\Request;

class WishListController extends Controller
{
    use GeneralTrait;

    public function addToWishlist(Request $request)
    {
        try {
            $roomId = $request->input('roomId'); // Extracting roomId from the request body
            $user = auth()->user(); // Fetching the authenticated user
    
            // Check if the room is already in the user's wishlist
            if ($user->wishlistedBy()->where('room_id', $roomId)->exists()) {
                return $this->returnError(['message' => 'Room is already in the wishlist.'], 400);
            }
    
            // Attach the room to the wishlist
            $user->wishlistedBy()->attach($roomId);
    
            return $this->returnSuccessMessage(['message' => 'Room added to wishlist.'], 200);
    
        } catch (\Exception $e) {
            // Handle exceptions here
            return $this->returnError(['error' => 'An error occurred while adding room to wishlist.'], 500);
        }
    }

    public function removeFromWishlist(Request $request, $roomId)
    {
        try {
            $user = auth()->user();
            $user->wishlists()->detach( $roomId );

            return $this->returnSuccessMessage( [ 'message' => 'room removed from wishlist.' ], 200 );
        }
        catch (\Exception $e) {
            // Handle exceptions here
            return $this->returnError(['error' => 'An error occurred while removing room to wishlist.'], 500);
        }
    }

    public function getWishlist(Request $request)
    {
        try {
            $user     = auth()->user();
            $wishlist = $user->wishlists()->get()->makeHidden( [ 'pivot', 'updated_at', 'created_at' ] );;

            return $this->returnSuccessMessage( [ 'wishlist' => $wishlist ] , 200);
        }
        catch (\Exception $e) {
            // Handle exceptions here
            return $this->returnError(['error' => 'An error occurred while removing room to wishlist.'], 500);
        }
    }
}
