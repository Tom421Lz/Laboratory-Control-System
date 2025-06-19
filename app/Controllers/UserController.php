<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    // GET /api/users
    public function list(Request $request, Response $response, $args = [])
    {
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $query = User::query();
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }
        if (!empty($params['role'])) {
            $query->where('role', $params['role']);
        }
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        $total = $query->count();
        $users = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        $data = $users->map(function($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'last_login' => $user->last_login ?? '',
            ];
        });
        $response->getBody()->write(json_encode([
            'data' => $data,
            'total' => $total
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /api/users/{id}
    public function update(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $user = User::find($id);
        if (!$user) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '用户不存在']));
        }
        if (isset($data['username'])) $user->username = $data['username'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['role'])) $user->role = $data['role'];
        if (isset($data['status'])) $user->status = $data['status'];
        $user->save();
        $response->getBody()->write(json_encode(['success' => true, 'user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/users/{id}/reset-password
    public function resetPassword(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $user = User::find($id);
        if (!$user) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '用户不存在']));
        }
        if (empty($data['password'])) {
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '密码不能为空']));
        }
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/users/{id}/toggle-status
    public function toggleStatus(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $user = User::find($id);
        if (!$user) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '用户不存在']));
        }
        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();
        $response->getBody()->write(json_encode(['success' => true, 'status' => $user->status]));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 