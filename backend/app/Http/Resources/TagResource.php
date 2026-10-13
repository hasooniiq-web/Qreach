<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Never expose `id` or `uuid` — `public_token` is the only tag identifier
 * that ever reaches a client (section 10/30).
 */
class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->public_token,
            'serial_number' => $this->serial_number,
            'type' => $this->type,
            'status' => $this->status,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'public_url' => url('/t/'.$this->public_token),
            // Which of the two ways of opening the code are switched on.
            'qr_enabled' => (bool) $this->qr_enabled,
            'nfc_enabled' => (bool) $this->nfc_enabled,
        ];
    }
}
