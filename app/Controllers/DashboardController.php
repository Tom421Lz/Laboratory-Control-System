<?php

namespace App\Controllers;

use App\Models\Equipment;
use App\Models\MaintenanceTask;
use App\Models\Resource;
use App\Models\FaultReport;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController
{
    public function index(Request $request, Response $response, $args = [])
    {
        // 统计数据
        $equipmentCount = Equipment::count();
        $faultyEquipmentCount = Equipment::where('status', 'faulty')->count();
        $pendingMaintenanceCount = MaintenanceTask::where('status', 'pending')->count();
        $resourceCount = Resource::count();

        // 最近5条故障报告
        $recentFaults = FaultReport::with('equipment')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'equipment_name' => optional($item->equipment)->name,
                    'severity' => $item->severity ?? '',
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d') : '',
                ];
            });

        // 最近5条维护任务
        $recentMaintenance = MaintenanceTask::with(['faultReport.equipment', 'assignedUser'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'equipment_name' => optional(optional($item->faultReport)->equipment)->name,
                    'status' => $item->status,
                    'assigned_to' => optional($item->assignedUser)->username,
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d') : '',
                ];
            });

        // 最近30天每日故障报告数和维护完成数
        $days = Collection::make(range(0, 29))->map(function($i) {
            return Carbon::now()->subDays(29 - $i)->format('Y-m-d');
        });
        $faultsPerDay = FaultReport::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date');
        $maintenancePerDay = MaintenanceTask::selectRaw('DATE(completion_date) as date, COUNT(*) as count')
            ->whereNotNull('completion_date')
            ->where('completion_date', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date');
        $recentStats = $days->map(function($date) use ($faultsPerDay, $maintenancePerDay) {
            return [
                'date' => $date,
                'fault_count' => (int)($faultsPerDay[$date] ?? 0),
                'maintenance_count' => (int)($maintenancePerDay[$date] ?? 0),
            ];
        });

        $data = [
            'stats' => [
                'equipmentCount' => $equipmentCount,
                'faultyEquipmentCount' => $faultyEquipmentCount,
                'pendingMaintenanceCount' => $pendingMaintenanceCount,
                'resourceCount' => $resourceCount,
            ],
            'recentFaults' => $recentFaults,
            'recentMaintenance' => $recentMaintenance,
            'recentStats' => $recentStats,
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 