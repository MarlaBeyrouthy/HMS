<?php
namespace App\Http\Traits;
trait GeneralTrait{
    public function returnError($errNum,$msg){

        return response()->json([
            'status'->false,
            '$errNum'->$errNum,
            
            ])
    }
}


