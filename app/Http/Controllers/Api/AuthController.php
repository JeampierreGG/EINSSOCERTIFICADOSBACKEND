<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetCodeMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Envía un código de 6 dígitos al correo para recuperar contraseña.
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.exists' => 'Este correo electrónico no está registrado.',
        ]);

        $email = strtolower(trim($request->email));
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar o actualizar código
        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $email],
            [
                'code' => $code,
                'created_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]
        );

        // Enviar correo
        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code));
            return response()->json(['message' => 'Código de verificación enviado a su correo.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar el correo.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Valida el código y restablece la contraseña.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'code.required' => 'El código de verificación es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $email = $request->email;
        $code = $request->code;

        $record = DB::table('password_reset_codes')
            ->where('email', $email)
            ->where('code', $code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'El código de verificación es incorrecto.'], 422);
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            return response()->json(['message' => 'El código ha expirado. Por favor, solicite uno nuevo.'], 422);
        }

        // Actualizar contraseña
        $user = User::where('email', $email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Limpiar código usado
        DB::table('password_reset_codes')->where('email', $email)->delete();

        return response()->json(['message' => 'Su contraseña ha sido restablecida correctamente.']);
    }

    public function register(Request $request)
    {
        // Validación con seguridad robusta
        $validated = $request->validate([
            'email' => 'required|string|email:rfc,dns|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'nombres' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'DNI_CARNET' => 'nullable|string|max:20|unique:user_profiles,dni_ce',
            'phone' => 'required|string|max:50',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'nombres.required' => 'El nombre es obligatorio.',
            'nombres.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',
            'DNI_CARNET.unique' => 'Este DNI/Carnet ya está registrado.',
            'phone.required' => 'El número de teléfono es obligatorio.',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // Obtener rol de estudiante
                $role = Role::where('name', 'Estudiante')->first();
                
                if (!$role) {
                    throw new \Exception('El rol de Estudiante no existe. Contacte al administrador.');
                }
                
                
                // 1. Crear usuario
                $apellidos = trim($validated['apellidos']);
                $fullName = trim($validated['nombres'] . ' ' . $apellidos);
                
                $user = User::create([
                    'name' => $fullName,
                    'email' => strtolower(trim($validated['email'])),
                    'password' => Hash::make($validated['password']),
                    'role_id' => $role->id,
                    'email_verified_at' => now(), // Auto-verificar o requerir verificación según tu política
                ]);

                // 2. Crear perfil de usuario
                UserProfile::create([
                    'user_id' => $user->id,
                    'dni_ce' => $validated['DNI_CARNET'] ?? null,
                    'nombres' => $validated['nombres'],
                    'apellidos' => $apellidos,
                    'phone' => $validated['phone'],
                ]);

                // 3. Autenticar automáticamente al usuario para sesión de navegador
                Auth::login($user);
                
                // Regenerar sesión para prevenir session fixation (Para WEB)
                request()->session()->regenerate();

                // 4. Crear Token de Acceso para Aplicación Móvil (Flutter)
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Usuario registrado exitosamente',
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user->load('profile', 'role'),
                ], 201);
            });
        } catch (\Exception $e) {
            \Log::error('Error en registro de usuario: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user = Auth::user();
        
        // 1. Manejo de Sesión WEB
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        
        // 2. Generar Token para Móvil (Flutter)
        // Eliminamos tokens anteriores si queremos solo una sesión activa por dispositivo, 
        // o los mantenemos si permitimos múltiples. Aquí generamos uno nuevo.
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Load profile and role
        $user->load('profile', 'role');

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'profile_completed' => (bool) ($user->profile && $user->profile->phone),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // 1. Revocar el token actual si se usó autenticación por Token (Móvil)
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // 2. Cerrar sesión de navegador (Sesión WEB)
        Auth::guard('web')->logout();
        
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->load([
                'profile.user', // nested load if circular needed, or just 'profile'
                'role',
            ]);
            
            return response()->json([
                'user' => $user,
                'student' => $user->profile, // Frontend expects 'student' key for profile? 'profile?.student?.phone'
            ]);
        }
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    
    public function updateProfile(Request $request) 
    {
        $user = $request->user();
        
        // Validamos email y password también
        $data = $request->validate([
            'nombres' => 'required|string',
            'apellidos' => 'nullable|string',
            'phone' => 'required|string',
            'DNI_CARNET' => 'nullable|string',
            'country' => 'nullable|string',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);
        
        // Determinar apellidos
        $apellidosFinal = '';
        if (!empty($data['apellidos'])) {
            $apellidosFinal = trim($data['apellidos']);
        } else {
            // Fallback compatibilidad
            $apellidosFinal = trim(($request->input('apellido_paterno') ?? '') . ' ' . ($request->input('apellido_materno') ?? ''));
        }

        // Update User data
        $user->name = trim($data['nombres'] . ' ' . $apellidosFinal);
        $user->email = strtolower(trim($data['email']));
        
        // Update Password if provided
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        
        $user->save();
        
        // Update Profile
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $profile->nombres = $data['nombres'];
        $profile->apellidos = $apellidosFinal;
        $profile->phone = $data['phone'];
        $profile->country = $data['country'] ?? null;
        $profile->dni_ce = !empty($data['DNI_CARNET']) ? $data['DNI_CARNET'] : null;
        $profile->save();
        
        return response()->json([
            'user' => $user,
            'student' => $profile,
        ]);
    }
}
