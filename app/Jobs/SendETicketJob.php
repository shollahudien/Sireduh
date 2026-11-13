<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\ETicketWhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendETicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $reservationId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reservationId)
    {
        $this->reservationId = $reservationId;
    }

    /**
     * Execute the job: generate the SHA-256 QR and send it over WhatsApp.
     */
    public function handle(ETicketWhatsappService $service): void
    {
        $reservation = Reservation::query()
            ->with(['visitor', 'unit', 'addons.product', 'ticket'])
            ->find($this->reservationId);

        if (! $reservation) {
            Log::error("Reservation #{$this->reservationId} not found when sending E-Ticket.");

            return;
        }

        try {
            $service->send($reservation);
        } catch (RuntimeException $e) {
            // Konfigurasi/validasi tidak tersedia — jangan retry selamanya.
            Log::warning('E-Ticket WhatsApp not sent', [
                'reservation_id' => $reservation->id,
                'reason' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::error('E-Ticket WhatsApp failed', [
                'reservation_id' => $reservation->id,
                'exception' => $e,
            ]);

            throw $e; // error tak terduga — biarkan queue retry
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'eticket',
            'reservation-'.$this->reservationId,
        ];
    }
}
