<?php

namespace App\Controllers;

use App\Models\Laboratory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LaboratoryController
{
    // GET /api/laboratories
    public function list(Request $request, Response $response, $args)
    {
        $labs = Laboratory::all();
        $response->getBody()->write($labs->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/laboratories
    public function create(Request $request, Response $response, $args = [])
    {
        $data = $request->getParsedBody();
        if (empty($data['name']) || empty($data['location'])) {
            $response->getBody()->write(json_encode(['error' => '名称和位置不能为空']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $lab = new Laboratory();
        $lab->name = $data['name'];
        $lab->location = $data['location'];
        $lab->description = $data['description'] ?? '';
        $lab->save();
        $response->getBody()->write(json_encode(['success' => true, 'lab' => $lab]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /api/laboratories/{id}
    public function update(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $lab = Laboratory::find($id);
        if (!$lab) {
            $response->getBody()->write(json_encode(['error' => '实验室不存在']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        if (isset($data['name'])) $lab->name = $data['name'];
        if (isset($data['location'])) $lab->location = $data['location'];
        if (isset($data['description'])) $lab->description = $data['description'];
        $lab->save();
        $response->getBody()->write(json_encode(['success' => true, 'lab' => $lab]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // DELETE /api/laboratories/{id}
    public function delete(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $lab = Laboratory::find($id);
        if (!$lab) {
            $response->getBody()->write(json_encode(['error' => '实验室不存在']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $lab->delete();
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 