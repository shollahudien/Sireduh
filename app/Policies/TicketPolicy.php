<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Hanya petugas Check-in (role checkin) yang boleh MEMINTA validasi tiket.
     * Keberadaan tiket sendiri sudah diverifikasi dari hash SHA-256 di database;
     * policy ini menolak pengguna non-checkin sebelum logika bisnis jalan.
     */
    public function validate(User $user, Ticket $ticket): bool
    {
        return $user->role === 'checkin';
    }
}
