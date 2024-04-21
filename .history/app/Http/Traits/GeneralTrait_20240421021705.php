<?php
namespace App\Http\Traits;

use Illuminate\Http\Response;

trait GeneralTrait

{
    public function getCurrentLang(){
        return app()->getLocale();
    }
    public function returnError($error,$msg,$code = Response::HTTP_BAD_REQUEST){
        return response()->json([
            'status'=>'false',
            'error'=>$error,
            'msg'=>$msg, 
        ], $code)->header('Status', $code.' Bad Request');
    }
    public function returnSuccessMessage($msg="",$errNum="S000"){
        return [
            'status'=>true,
            'errNum'=>$errNum,
            'msg'=>$msg,
        ];
    }
    public function returnData($msg="",$data,$code = Response::HTTP_OK){
        return response()->json([
            'status'=>true,
            'data'=>$data,
            'msg'=>$msg,
           // $key=>$value,
        ],$code)->header('Status', $code.' OK');
    }
}