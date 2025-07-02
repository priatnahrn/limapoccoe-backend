<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class FonnteHelper
{
    /**
     * Kirim pesan WhatsApp menggunakan Fonnte
     *
     * @param string $target Nomor tujuan (misal: 628123456789)
     * @param string $message Pesan yang akan dikirim
     * @return bool
     */
    public static function sendWhatsAppMessage(string $target, string $message): bool
    {
        $token = config('services.fonnte.key'); // pastikan token disimpan di config/services.php
        $url   = 'https://api.fonnte.com/send';

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])
            ->withOptions(['verify' => false])
            ->asForm()
            ->post($url, [
                'target'  => $target,
                'message' => $message,
            ]);

        return $response->successful();
    }
}
