<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::where('is_active', true)->get()->map(function($method) {
            if ($method->qr_image_path) {
                // Generar URL a nuestro propio endpoint que sirve la imagen (proxy)
                $method->qr_url = url("/api/payment-methods/{$method->id}/qr");
            }
            return $method;
        });
        return response()->json($methods);
    }

    public function qrImage($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $path = $method->qr_image_path;

        if (!$path || !Storage::disk(config('filesystems.default'))->exists($path)) {
            return response()->noContent(404);
        }

        $disk = Storage::disk(config('filesystems.default'));
        $content = $disk->get($path);
        
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];
        $mime = $mimeMap[$extension] ?? 'application/octet-stream';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'amount' => 'required|numeric',
                'transaction_code' => 'required|string',
                'date_paid' => 'required|date',
                'items' => 'required|json', // Viene como string JSON desde FormData
                'proof_image' => 'required|image|max:5120', // 5MB
                'payer_first_name' => 'nullable|string',
                'payer_last_name' => 'nullable|string',
                'payer_email' => 'nullable|string',
            ]);

            $user = Auth::user();

            // Subir imagen
            $path = null;
            if ($request->hasFile('proof_image')) {
                // Usamos 's3' explícitamente porque así lo pidió el usuario, 
                // pero si falla, capturamos el error.
                $path = $request->file('proof_image')->store('payment-proofs', 's3');
            }

            $items = json_decode($request->items, true) ?? [];
            $courseId = $items['course_id'] ?? null;

            // Fix: Frontend sends slug as ID sometimes (due to API transformer). 
            // If courseId is not numeric, try to resolve it from slug.
            if ($courseId && !is_numeric($courseId)) {
                $resolvedId = Course::where('slug', $courseId)->value('id');
                if ($resolvedId) {
                    $courseId = $resolvedId;
                } else {
                    // If slug not found, set to null to avoid SQL BigInt error
                    $courseId = null;
                }
            }

            // Resolve Certification Block from Option if applicable
            $certificationBlockId = null;
            if (isset($items['type']) && $items['type'] === 'certificate' && isset($items['id'])) {
                $option = \App\Models\CourseCertificateOption::find($items['id']);
                if ($option) {
                    $certificationBlockId = $option->certification_block_id;
                }
            }

            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'payment_method_id' => $request->payment_method_id,
                'certification_block_id' => $certificationBlockId,
                'amount' => $request->amount,
                'transaction_code' => $request->transaction_code,
                'date_paid' => $request->date_paid,
                'proof_image_path' => $path,
                'items' => $items,
                'status' => 'pending',
                'payer_first_name' => $request->payer_first_name,
                'payer_last_name' => $request->payer_last_name,
                'payer_email' => $request->payer_email,
            ]);

            return response()->json(['message' => 'Pago registrado correctamente, espere validación.', 'payment' => $payment], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno al procesar pago: ' . $e->getMessage()], 500);
        }
    }
    public function myPayments()
    {
        $user = Auth::user();
        if (!$user) return response()->json(['data' => []]);

        $payments = Payment::where('user_id', $user->id)
            ->with(['paymentMethod', 'certificate', 'certificationBlock'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                $p->items = is_string($p->items) ? json_decode($p->items, true) : $p->items;
                return $p;
            });

        return response()->json(['data' => $payments]);
    }
}
