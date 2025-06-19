<?php

namespace App\Controllers;

use App\Models\MaintenanceTask;
use App\Models\Equipment;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MaintenanceController
{
    // GET /api/maintenance
    public function list(Request $request, Response $response, $args = [])
    {
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;

        $query = MaintenanceTask::query();
        // 设备名筛选
        if (!empty($params['equipment_name'])) {
            $query->whereHas('faultReport.equipment', function($q) use ($params) {
                $q->where('name', 'like', '%' . $params['equipment_name'] . '%');
            });
        }
        // 状态筛选
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        // 优先级筛选
        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        $total = $query->count();
        $tasks = $query->with(['faultReport.equipment', 'assignedUser'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // 格式化返回
        $data = $tasks->map(function($task) {
            return [
                'id' => $task->id,
                'equipment_name' => optional(optional($task->faultReport)->equipment)->name,
                'status' => $task->status,
                'priority' => $task->priority,
                'assigned_to' => optional($task->assignedUser)->username,
                'start_date' => $task->start_date,
                'expected_completion_date' => $task->expected_completion_date,
                'completion_date' => $task->completion_date,
                'notes' => $task->notes,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at
            ];
        });

        $response->getBody()->write(json_encode([
            'data' => $data,
            'total' => $total
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/maintenance
    public function create(Request $request, Response $response, $args = [])
    {
        $data = $request->getParsedBody();

        $task = new MaintenanceTask();
        $task->fault_report_id = $data['fault_report_id'] ?? null;
        $task->status = $data['status'] ?? 'pending';
        $task->priority = $data['priority'] ?? 'normal';
        $task->assigned_to = $data['assigned_to'] ?? null;
        $task->start_date = $data['start_date'] ?? date('Y-m-d');
        $task->expected_completion_date = $data['expected_completion_date'] ?? null;
        $task->notes = $data['notes'] ?? null;
        $task->save();

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $task
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /api/maintenance/{id}
    public function update(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $task = MaintenanceTask::find($id);
        if (!$task) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '维护任务不存在']));
        }
        $task->fill($data);
        if (isset($data['expected_completion_date'])) {
            $task->expected_completion_date = $data['expected_completion_date'];
        }
        $task->save();
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $task
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/maintenance/{id}/complete
    public function complete(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $task = MaintenanceTask::find($id);
        if (!$task) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '维护任务不存在']));
        }
        $task->status = 'completed';
        $task->completion_date = $data['completion_date'] ?? date('Y-m-d');
        $task->notes = $data['result'] ?? $task->notes;
        $task->save();
        // 新增：同步更新 fault_report 和 equipment 状态
        if ($task->fault_report_id) {
            $faultReport = \App\Models\FaultReport::find($task->fault_report_id);
            if ($faultReport) {
                $faultReport->status = 'resolved';
                $faultReport->save();
                if ($faultReport->equipment_id) {
                    $equipment = \App\Models\Equipment::find($faultReport->equipment_id);
                    if ($equipment) {
                        $equipment->status = 'operational';
                        $equipment->save();
                    }
                }
            }
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $task
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /api/maintenance/{id}/cancel
    public function cancel(Request $request, Response $response, $args = [])
    {
        $id = $args['id'] ?? null;
        $task = MaintenanceTask::find($id);
        if (!$task) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => '维护任务不存在']));
        }
        $task->status = 'cancelled';
        $task->save();
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $task
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // 获取故障报告列表（可加筛选条件）
    public function faultReportList(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = \App\Models\FaultReport::with('equipment');

        // 可选：按状态筛选
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $reports = $query->get()->map(function($report) {
            return [
                'id' => $report->id,
                'equipment_name' => optional($report->equipment)->name,
                'description' => $report->description,
                'status' => $report->status,
                'created_at' => $report->created_at,
            ];
        });

        $response->getBody()->write(json_encode([
            'data' => $reports
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 