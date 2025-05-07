<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header)) {
            throw new Exception('No token provided', 401);
        }
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $token = $matches[1];
        } else {
            throw new Exception('Invalid token format', 401);
        }
        
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
            // Add user data to request attributes
            $request = $request->withAttribute('user', $decoded);
            
            return $handler->handle($request);
        } catch (Exception $e) {
            throw new Exception('Invalid token', 401);
        }
    }
} 