<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ETicketWhatsappService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestETicketController extends Controller
{
    public function __construct(private ETicketWhatsappService $qrService) {}

    /**
     * Sajikan gambar QR e-tiket berdasarkan hash SHA-256 tiket.
     * Hash adalah rahasia tiket — tanpa hash yang valid, gambar tidak bisa diakses.
     */
    public function __invoke(Request $request, string $hash): Response
    {
        $ticket = Ticket::query()
            ->where('unique_hash_sha256', $hash)
            ->first();

        if ($ticket === null) {
            abort(404);
        }

        $png = $this->qrService->generateQrPng($hash);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="e-ticket-siresteduh.png"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
