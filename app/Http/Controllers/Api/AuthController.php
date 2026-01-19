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

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validación con seguridad robusta
        $validated = $request->validate([
            'email' => 'required|string|email:rfc,dns|max:255|unique:users,email',
            // Contraseña fuerte: min 8 caracteres, debe contener mayúsculas, minúsculas, números y símbolos
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',           // al menos una minúscula
                'regex:/[A-Z]/',           // al menos una mayúscula  
                'regex:/[0-9]/',           // al menos un número
                'regex:/[^a-zA-Z0-9]/',    // al menos un símbolo especial (cualquier carácter que no sea letra o número)
            ],
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
            'nombres.regex' => 'El nombre solo puede contener letras.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'DNI_CARNET.unique' => 'Este DNI/Carnet ya está registrado.',
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
        // Aceptamos 'apellidos' unificado. Tambien mantenemos compatibilidad si por error envían separados, pero idealmente usamos 'apellidos'
        $data = $request->validate([
            'nombres' => 'required|string',
            'apellidos' => 'nullable|string', // Nuevo campo unificado
            // Mantenemos paterno/materno opcionales por si front viejo los envia, pero priorizamos 'apellidos'
            'apellido_paterno' => 'nullable|string',
            'apellido_materno' => 'nullable|string',
            
            'phone' => 'required|string',
            'DNI_CARNET' => 'nullable|string',
            'country' => 'nullable|string',
        ]);
        
        // Determinar apellidos
        $apellidosFinal = '';
        if (!empty($data['apellidos'])) {
            $apellidosFinal = trim($data['apellidos']);
        } else {
            // Fallback compatibilidad
            $apellidosFinal = trim(($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''));
        }

        // Update User name
        $user->name = trim($data['nombres'] . ' ' . $apellidosFinal);
        $user->save();
        
        // Update Profile
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $profile->nombres = $data['nombres'];
        $profile->apellidos = $apellidosFinal;
        $profile->phone = $data['phone'];
        $profile->country = $data['country'] ?? null;
        if (!empty($data['DNI_CARNET'])) {
            $profile->dni_ce = $data['DNI_CARNET'];
        }
        $profile->save();
        
        return response()->json([
            'user' => $user,
            'student' => $profile,
        ]);
    }
}
