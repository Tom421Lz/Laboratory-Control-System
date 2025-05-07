<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Models\User;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['username']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Username and password are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Find user
        $user = User::where('username', $data['username'])->first();
        
        if (!$user || !password_verify($data['password'], $user->password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid credentials'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Generate JWT token
        $token = JWT::encode([
            'user_id' => $user->id,
            'role' => $user->role,
            'exp' => time() + $_ENV['JWT_EXPIRATION']
        ], $_ENV['JWT_SECRET'], 'HS256');
        
        $response->getBody()->write(json_encode([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email']) || !isset($data['role'])) {
            $response->getBody()->write(json_encode([
                'error' => 'All fields are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if username or email already exists
        if (User::where('username', $data['username'])->exists() || 
            User::where('email', $data['email'])->exists()) {
            $response->getBody()->write(json_encode([
                'error' => 'Username or email already exists'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Create new user
        $user = new User();
        $user->username = $data['username'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
} 