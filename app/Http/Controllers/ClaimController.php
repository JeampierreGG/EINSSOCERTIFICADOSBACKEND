<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Http\Requests\StoreClaimRequest;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClaimRequest $request)
    {
        $claim = Claim::create($request->validated());

        // Aquí se podría enviar un correo electrónico al usuario con su código de ticket
        // Mail::to($claim->email)->send(new ClaimReceived($claim));

        return response()->json([
            'message' => 'Reclamo registrado correctamente',
            'ticket_code' => $claim->ticket_code,
            'data' => $claim
        ], 201);
    }

    /**
     * Display the specified resource by ticket_code.
     */
    public function show($ticket_code)
    {
        $claim = Claim::where('ticket_code', $ticket_code)->firstOrFail();
        
        return response()->json([
            'data' => $claim
        ]);
    }
}
