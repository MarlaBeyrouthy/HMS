<!-- resources/views/invoices/pdf.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        /* Add your custom styles here */
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }
        .invoice {
            width: 100%;
            margin: 0 auto;
        }
        .invoice-header, .invoice-footer {
            text-align: center;
        }
        .invoice-details {
            width: 100%;
            margin: 20px 0;
        }
        .invoice-details th, .invoice-details td {
            text-align: left;
            padding: 8px;
        }
    </style>
</head>
<body>
<div class="invoice">
    <div class="invoice-header">
        <h1>Invoice</h1>
        <p>Date: {{ $invoice->invoice_date }}</p>
    </div>
    <div class="invoice-details">
        <table>
            <tr>
                <th>Customer Name:</th>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
            </tr>
            <tr>
                <th>Room Number:</th>
                <td>{{ $room->room_number }}</td>
            </tr>
            <tr>
                <th>Room Type:</th>
                <td>{{ $roomClass->class_name }}</td>
            </tr>
            <tr>
                <th>Number of Days:</th>
                <td>{{ $numDays }}</td>
            </tr>

\
            <tr>
                <th>Check-in Date:</th>
                <td>{{ $checkInDate }}</td>
            </tr>
            <tr>
                <th>Check-out Date:</th>
                <td>{{ $checkOutDate }}</td>
            </tr>

            <tr>
                <th>Total Amount:</th>
                <td>{{ $invoice->total_amount }}</td>
            </tr>
            <tr>
                <th>Paid Amount:</th>
                <td>{{ $invoice->paid_amount }}</td>
            </tr>
            <tr>
                <th>Remaining Amount:</th>
                <td>{{ $invoice->remaining_amount }}</td>
            </tr>
            @if ($invoice->taxes)
                <tr>
                    <th>Taxes:</th>
                    <td>{{ $invoice->taxes }}</td>
                </tr>
            @endif
            @if ($invoice->services)
                <tr>
                    <th>Services:</th>
                    <td>{{ implode(', ', json_decode($invoice->services)) }}</td>
                </tr>
            @endif
        </table>
    </div>
    <div class="invoice-footer">
        <p>Thank you for your business!</p>
    </div>
</div>
</body>
</html>
