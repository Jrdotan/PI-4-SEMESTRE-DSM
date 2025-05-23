<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Registrar um novo usuário
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clientes',
            'senha' => 'required|string|min:6',
            'cpf' => 'required|string|max:14|unique:clientes',
            'telefone' => 'required|string|max:15',
            'data_nascimento' => 'required|date|before:today',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'required|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'isProdutor' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $cliente = Cliente::create($request->all());
        $token = $cliente->createToken('auth_token')->plainTextToken;

        // Determinar URL de redirecionamento com base no tipo de usuário
        $redirectUrl = $cliente->isProdutor ? '/fornecedor/dashboard' : '/';

        return response()->json([
            'message' => 'Cliente registrado com sucesso',
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome_completo,
                'email' => $cliente->email,
                'tipo' => $cliente->isProdutor ? 'fornecedor' : 'cliente',
                'logado' => true
            ],
            'token' => $token,
            'redirect' => $redirectUrl
        ], 201);
    }

    /**
     * Login de usuário
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'senha' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        // Encontrar o cliente pelo email
        $cliente = Cliente::where('email', $request->email)->first();
        
        if (!$cliente) {
            return response()->json([
                'message' => 'Credenciais inválidas',
                'error' => 'Email não encontrado'
            ], 401);
        }
        
        // Verificar a senha
        if (!password_verify($request->senha, $cliente->senha)) {
            return response()->json([
                'message' => 'Credenciais inválidas',
                'error' => 'Senha incorreta'
            ], 401);
        }

        // Gerar token para autenticação via API
        $token = $cliente->createToken('auth_token')->plainTextToken;
        
        // Determinar URL de redirecionamento com base no tipo de usuário
        $redirectUrl = $cliente->isProdutor ? '/fornecedor/dashboard' : '/';

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome_completo,
                'email' => $cliente->email,
                'tipo' => $cliente->isProdutor ? 'fornecedor' : 'cliente',
                'logado' => true
            ],
            'token' => $token,
            'redirect' => $redirectUrl
        ]);
    }

    /**
     * Obter dados do usuário autenticado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function me(Request $request)
    {
        $cliente = $request->user();
        
        return response()->json([
            'id' => $cliente->id,
            'nome' => $cliente->nome_completo,
            'email' => $cliente->email,
            'tipo' => $cliente->isProdutor ? 'fornecedor' : 'cliente',
            'logado' => true
        ]);
    }

    /**
     * Logout de usuário
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }
} 