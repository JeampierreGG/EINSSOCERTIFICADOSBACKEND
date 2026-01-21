<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|in:DNI,CE,Pasaporte',
            'numero_documento' => 'required|string|max:20',
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'domicilio' => 'required|string',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'padre_nombres' => 'nullable|string|max:255',
            'tipo_bien' => 'required|in:producto,servicio',
            'monto_reclamado' => 'nullable|numeric|min:0',
            'descripcion_bien' => 'required|string',
            'tipo_reclamacion' => 'required|in:reclamo,queja',
            'detalle' => 'required|string',
            'pedido' => 'required|string',
            'acepto_terminos' => 'required|boolean|accepted',
        ];
    }
}
