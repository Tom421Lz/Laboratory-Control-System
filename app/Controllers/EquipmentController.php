<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Equipment;
use App\Models\FaultReport;
use App\Models\MaintenanceTask;
use App\Models\Notification;
use App\Models\User;

class EquipmentController
{
    public function list(Request $request, Response $response): Response
    {
        $equipment = Equipment::with('laboratory')->get();
        
        $response->getBody()->write(json_encode([
            'equipment' => $equipment
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['name']) || !isset($data['laboratory_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Name and laboratory_id are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Create equipment
        $equipment = new Equipment();
        $equipment->name = $data['name'];
        $equipment->laboratory_id = $data['laboratory_id'];
        $equipment->serial_number = $data['serial_number'] ?? null;
        $equipment->status = 'operational';
        $equipment->purchase_date = $data['purchase_date'] ?? null;
        $equipment->warranty_expiry = $data['warranty_expiry'] ?? null;
        $equipment->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Equipment created successfully',
            'equipment' => $equipment
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $equipment = Equipment::find($args['id']);
        
        if (!$equipment) {
            $response->getBody()->write(json_encode([
                'error' => 'Equipment not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Update equipment
        if (isset($data['name'])) $equipment->name = $data['name'];
        if (isset($data['status'])) $equipment->status = $data['status'];
        if (isset($data['serial_number'])) $equipment->serial_number = $data['serial_number'];
        if (isset($data['purchase_date'])) $equipment->purchase_date = $data['purchase_date'];
        if (isset($data['warranty_expiry'])) $equipment->warranty_expiry = $data['warranty_expiry'];
        if (isset($data['laboratory_id'])) $equipment->laboratory_id = $data['laboratory_id'];
        
        $equipment->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Equipment updated successfully',
            'equipment' => $equipment
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function delete(Request $request, Response $response, array $args): Response
    {
        $equipment = Equipment::find($args['id']);
        
        if (!$equipment) {
            $response->getBody()->write(json_encode([
                'error' => 'Equipment not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if equipment has active maintenance tasks
        $activeMaintenance = MaintenanceTask::where('equipment_id', $equipment->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
            
        if ($activeMaintenance) {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot delete equipment with active maintenance tasks'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $equipment->delete();
        
        $response->getBody()->write(json_encode([
            'message' => 'Equipment deleted successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function reportFault(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        // Validate input
        if (!isset($data['equipment_id']) || !isset($data['severity']) || !isset($data['description'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Equipment ID, severity, and description are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $equipment = Equipment::find($data['equipment_id']);
        
        if (!$equipment) {
            $response->getBody()->write(json_encode([
                'error' => 'Equipment not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Create fault report
        $faultReport = new FaultReport();
        $faultReport->equipment_id = $equipment->id;
        $faultReport->reported_by = $user->user_id;
        $faultReport->severity = $data['severity'];
        $faultReport->description = $data['description'];
        $faultReport->status = 'pending';
        $faultReport->save();
        
        // Update equipment status
        $equipment->status = 'faulty';
        $equipment->save();
        
        // Create notification for admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $notification = new Notification();
            $notification->user_id = $admin->id;
            $notification->type = 'equipment_fault';
            $notification->message = "New fault report for equipment {$equipment->name}";
            $notification->save();
        }
        
        $response->getBody()->write(json_encode([
            'message' => 'Fault report created successfully',
            'fault_report' => $faultReport
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function createMaintenanceTask(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        // Check if user is admin
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'error' => 'Only admins can create maintenance tasks'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Validate input
        if (!isset($data['fault_report_id']) || !isset($data['assigned_to']) || !isset($data['priority'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Fault report ID, assigned_to, and priority are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $faultReport = FaultReport::find($data['fault_report_id']);
        
        if (!$faultReport) {
            $response->getBody()->write(json_encode([
                'error' => 'Fault report not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Create maintenance task
        $maintenanceTask = new MaintenanceTask();
        $maintenanceTask->fault_report_id = $faultReport->id;
        $maintenanceTask->assigned_to = $data['assigned_to'];
        $maintenanceTask->priority = $data['priority'];
        $maintenanceTask->status = 'pending';
        $maintenanceTask->start_date = date('Y-m-d');
        $maintenanceTask->notes = $data['notes'] ?? null;
        $maintenanceTask->save();
        
        // Update fault report status
        $faultReport->status = 'in_progress';
        $faultReport->save();
        
        // Update equipment status
        $equipment = Equipment::find($faultReport->equipment_id);
        $equipment->status = 'maintenance';
        $equipment->save();
        
        // Create notification for assigned user
        $notification = new Notification();
        $notification->user_id = $data['assigned_to'];
        $notification->type = 'maintenance_due';
        $notification->message = "New maintenance task assigned for equipment {$equipment->name}";
        $notification->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Maintenance task created successfully',
            'maintenance_task' => $maintenanceTask
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function updateMaintenanceTask(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        $maintenanceTask = MaintenanceTask::find($args['id']);
        
        if (!$maintenanceTask) {
            $response->getBody()->write(json_encode([
                'error' => 'Maintenance task not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if user is assigned to the task or is an admin
        if ($maintenanceTask->assigned_to !== $user->user_id && $user->role !== 'admin') {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized to update this maintenance task'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Update maintenance task
        if (isset($data['status'])) $maintenanceTask->status = $data['status'];
        if (isset($data['notes'])) $maintenanceTask->notes = $data['notes'];
        
        // If task is completed, update related records
        if ($data['status'] === 'completed') {
            $maintenanceTask->completion_date = date('Y-m-d');
            
            // Update fault report
            $faultReport = FaultReport::find($maintenanceTask->fault_report_id);
            $faultReport->status = 'resolved';
            $faultReport->save();
            
            // Update equipment status
            $equipment = Equipment::find($faultReport->equipment_id);
            $equipment->status = 'operational';
            $equipment->save();
            
            // Create notification for admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $notification = new Notification();
                $notification->user_id = $admin->id;
                $notification->type = 'system';
                $notification->message = "Maintenance task completed for equipment {$equipment->name}";
                $notification->save();
            }
        }
        
        $maintenanceTask->save();
        
        $response->getBody()->write(json_encode([
            'message' => 'Maintenance task updated successfully',
            'maintenance_task' => $maintenanceTask
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
} 