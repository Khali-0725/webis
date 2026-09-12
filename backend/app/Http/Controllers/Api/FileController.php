<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\ProviderPaymentMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves user-uploaded files from the private disk.
 *
 * Uploads are never written under public/ and never have a guessable public
 * URL. Every byte a client receives passes through a controller that decides
 * whether that caller is allowed to see it.
 */
class FileController extends Controller
{
    /**
     * GET /api/files/avatar/{user}
     *
     * Avatars are intentionally public: they appear on public provider
     * profiles and in search results. No other private file is public.
     */
    public function avatar(User $user): Response
    {
        $path = $user->avatar_path;
        $disk = Storage::disk(config('webis.uploads.disk'));

        abort_if($path === null || ! $disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * GET /api/files/payment-proof/{proof}
     *
     * Unlike the avatar, this file is never public - only the payment's
     * client, its provider, or an admin may see a proof-of-payment screenshot.
     */
    public function paymentProof(Request $request, PaymentProof $proof): Response
    {
        $this->authorize('view', $proof->payment);

        return $this->stream($proof->file_path);
    }

    /**
     * GET /api/files/payment-qr/{method}
     *
     * Gated by ProviderPaymentMethodPolicy::viewQr - the owning provider,
     * admin, or a client with an accepted booking paid through this method.
     */
    public function paymentQr(Request $request, ProviderPaymentMethod $method): Response
    {
        $this->authorize('viewQr', $method);

        abort_if($method->qr_image_path === null, 404);

        return $this->stream($method->qr_image_path);
    }

    private function stream(string $path): Response
    {
        $disk = Storage::disk(config('webis.uploads.disk'));

        abort_if(! $disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
