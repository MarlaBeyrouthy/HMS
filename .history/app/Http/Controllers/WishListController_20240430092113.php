<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;
use Illuminate\Http\Request;

class WishListController extends Controller
{
    use GeneralTrait;

   

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
