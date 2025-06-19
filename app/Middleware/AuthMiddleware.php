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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $header = $request->getHeaderLine('Authorization');



        // error_log(print_r(getallheaders(), true));
        // error_log('Header: ' . $header);
        
        // error_log('Secret: ' . $_ENV['JWT_SECRET']);
        // if (empty($header)) {
        //     throw new Exception('No token provided', 401);
        // }
        return $handler->handle($request);
        // try{
            
        // }catch (Exception $e)
        // {

        // }
        
        
        
    //     try {
    //         $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
    //         // Add user data to request attributes
    //         $request = $request->withAttribute('user', $decoded);
            
    //         return $handler->handle($request);
    //     } catch (Exception $e) {
    //         throw new Exception('Invalid token', 401);
    //         error_log('JWT Exception: ' . $e->getMessage());
    //         throw new Exception('Invalid token', 401);
    //     }


    // try {
    //     $decoded = JWT::decode(
    //         $token,
    //         $_ENV['JWT_SECRET'],
    //         ['HS256']
    //     );
        
    //     // 验证解码后的数据
    //     if (!isset($decoded->user_id) || !isset($decoded->role) || !isset($decoded->exp)) {
    //         throw new InvalidTokenException("Invalid token format");
    //     }
        
    //     // 验证过期时间
    //     if ($decoded->exp < time()) {
    //         throw new ExpiredException("Token has expired");
    //     }
    
    //     } catch (ExpiredException $e) {
    //         // 处理过期令牌
    //         echo "Token has expired: " . $e->getMessage();
    //     } catch (SignatureInvalidException $e) {
    //         // 处理无效签名
    //         echo "Invalid token signature: " . $e->getMessage();
    //     } catch (BeforeValidException $e) {
    //         // 处理尚未生效的令牌
    //         echo "Token not yet valid: " . $e->getMessage();
    //     } catch (InvalidTokenException $e) {
    //         // 处理无效令牌
    //         echo "Invalid token: " . $e->getMessage();
    //     } catch (Exception $e) {
    //         // 处理其他异常
    //         echo "Error: " . $e->getMessage();
    //     }
    }
} 