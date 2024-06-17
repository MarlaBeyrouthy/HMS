<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;

class FileUploadAction {

    public function upload(UploadedFile $file, $destinationPath)
    {
        // Generate a unique file name
        $fileName = time() . '_' . $file->getClientOriginalName();

        // Move the uploaded file to the destination path
        $file->move($destinationPath, $fileName);

        // Return the path to the uploaded file
        return $destinationPath . '/' . $fileName;
    }
}
