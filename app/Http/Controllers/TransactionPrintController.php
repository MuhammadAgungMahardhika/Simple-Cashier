<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionPrintController extends Controller
{
    /**
     * Print receipt with default format (thermal)
     */
    public function print($id)
    {
        $transaction = Transaction::with(['customer', 'transactionDetails.service', 'discount'])
            ->findOrFail($id);

        // Default ke thermal printer format
        return view('print.transaction-receipt', compact('transaction'));
    }

    /**
     * Print receipt with specific format
     */
    public function printWithFormat($id, $format = 'thermal')
    {
        $transaction = Transaction::with(['customer', 'transactionDetails.service', 'discount'])
            ->findOrFail($id);

        $viewName = match ($format) {
            'a4' => 'print.transaction-receipt-a4',
            'thermal' => 'print.transaction-receipt-thermal',
            default => 'print.transaction-receipt-thermal',
        };

        return view($viewName, compact('transaction'));
    }
}
