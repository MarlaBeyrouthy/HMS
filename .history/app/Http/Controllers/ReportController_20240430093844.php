<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;
use App\Models\report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use GeneralTrait;

    public function create_report(Request $request)
    {
        try {
            $valid_Titles = [ 'user', 'room', 'reservation', 'technical' ];

            // Validate the request data
            $validatedData            = $request->validate( [
                'title' => 'required|in:' . implode( ',', $valid_Titles ),
                'text_description' => 'nullable',
            ] );
            $report                   = new report;
            $report->user_id          = auth()->id();
            $report->title            = $validatedData['title'];
            $report->text_description = $validatedData['text_description'];
            $report->save();

            return $this->returnSuccessMessage( [
                'message' => 'report created successfully',
                'report'  => $report
            ], 201 );
        }
        catch (\Exception $e) {
            // Handle exceptions here
            return $this->returnError(['error' => 'An error occurred while adding room to wishlist.'], 500);
        }
    }

    public function my_reports()
    {
        try {
            $reports = report::where( 'user_id', auth()->id() )->get();

            return $this->returnSuccessMessage( [ 'reports' => $reports ], 200 );
        }
        catch (\Exception $e) {
            // Handle exceptions here
            return $this->returnError(['error' => 'An error occurred while adding room to wishlist.'], 500);
        }
    }
}
