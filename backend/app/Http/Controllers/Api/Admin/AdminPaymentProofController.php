<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPaymentProofController extends Controller
{
    /**
     * Streams a receipt image/PDF to an authenticated admin. Proofs live on
     * a private disk — no public URL is ever generated or stored.
     */
    public function show(PaymentProof $proof): StreamedResponse
    {
        return Storage::disk($proof->storage_disk)->response($proof->object_key, null, [
            'Content-Type' => $proof->mime_type,
        ]);
    }
}
