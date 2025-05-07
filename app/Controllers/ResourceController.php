<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Resource;
use App\Models\ResourceAllocation;
use App\Models\Notification;

class ResourceController
{
    public function list(Request $request, Response $response): Response
    {
        $resources = Resource::with('laboratory')->get();
        
        $response->getBody()->write(json_encode([
            'resources' => $resources
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['name']) || !isset($data['type']) || !isset($data['laboratory_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Name, type, and laboratory_id are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Create resource
        $resource = new Resource();
        $resource->name = $data['name'];
        $resource->type = $data['type'];
        $resource->laboratory_id = $data['laboratory_id'];
        $resource->status = 'available';
        $resource->description = $data['description'] ?? null;
        $resource->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Resource created successfully',
            'resource' => $resource
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $resource = Resource::find($args['id']);
        
        if (!$resource) {
            $response->getBody()->write(json_encode([
                'error' => 'Resource not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Update resource
        if (isset($data['name'])) $resource->name = $data['name'];
        if (isset($data['type'])) $resource->type = $data['type'];
        if (isset($data['status'])) $resource->status = $data['status'];
        if (isset($data['description'])) $resource->description = $data['description'];
        if (isset($data['laboratory_id'])) $resource->laboratory_id = $data['laboratory_id'];
        
        $resource->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Resource updated successfully',
            'resource' => $resource
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function delete(Request $request, Response $response, array $args): Response
    {
        $resource = Resource::find($args['id']);
        
        if (!$resource) {
            $response->getBody()->write(json_encode([
                'error' => 'Resource not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if resource is currently allocated
        $activeAllocation = ResourceAllocation::where('resource_id', $resource->id)
            ->where('status', 'active')
            ->exists();
            
        if ($activeAllocation) {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot delete resource that is currently allocated'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $resource->delete();
        
        $response->getBody()->write(json_encode([
            'message' => 'Resource deleted successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function allocate(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        // Validate input
        if (!isset($data['resource_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Resource ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $resource = Resource::find($data['resource_id']);
        
        if (!$resource) {
            $response->getBody()->write(json_encode([
                'error' => 'Resource not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($resource->status !== 'available') {
            $response->getBody()->write(json_encode([
                'error' => 'Resource is not available for allocation'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Create allocation
        $allocation = new ResourceAllocation();
        $allocation->resource_id = $resource->id;
        $allocation->user_id = $user->user_id;
        $allocation->allocation_date = date('Y-m-d H:i:s');
        $allocation->status = 'active';
        $allocation->notes = $data['notes'] ?? null;
        $allocation->save();
        
        // Update resource status
        $resource->status = 'in_use';
        $resource->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Resource allocated successfully',
            'allocation' => $allocation
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function return(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        // Validate input
        if (!isset($data['allocation_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Allocation ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $allocation = ResourceAllocation::find($data['allocation_id']);
        
        if (!$allocation) {
            $response->getBody()->write(json_encode([
                'error' => 'Allocation not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($allocation->user_id !== $user->user_id) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized to return this resource'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Update allocation
        $allocation->return_date = date('Y-m-d H:i:s');
        $allocation->status = 'returned';
        $allocation->save();
        
        // Update resource status
        $resource = Resource::find($allocation->resource_id);
        $resource->status = 'available';
        $resource->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Resource returned successfully',
            'allocation' => $allocation
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
} 