<?php

namespace App\Services\Payroll;

use App\Enums\LeaveStatus;
use App\Exceptions\SchoolException;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Izin/sakit/cuti/dinas with kepala_sekolah approval (doc 05 §4).
 * Approval writes the attendance rows for the leave days — single source
 * of truth per employee/day.
 */
class LeaveService
{
    public function create(Employee $employee, array $data): Leave
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lessThan($start)) {
            throw new SchoolException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $overlaps = Leave::query()
            ->where('employee_id', $employee->getKey())
            ->whereIn('status', [LeaveStatus::Menunggu, LeaveStatus::Disetujui])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw new SchoolException('Sudah ada pengajuan izin/cuti yang beririsan pada tanggal tersebut.');
        }

        $leave = Leave::query()->create([
            'employee_id' => $employee->getKey(),
            'type' => $data['type'],
            'start_date' => $start,
            'end_date' => $end,
            'days' => max(1, (int) ($data['days'] ?? 1)),
            'reason' => $data['reason'],
            'status' => LeaveStatus::Menunggu,
        ]);

        return $leave;
    }

    /**
     * Edit a still-pending request (dates/type/reason) with the same
     * validation as create().
     */
    public function update(Leave $leave, array $data): Leave
    {
        if ($leave->status !== LeaveStatus::Menunggu) {
            throw new SchoolException('Pengajuan izin/cuti sudah diproses dan tidak dapat diubah.');
        }

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lessThan($start)) {
            throw new SchoolException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $overlaps = Leave::query()
            ->where('employee_id', $leave->employee_id)
            ->whereKeyNot($leave->getKey())
            ->whereIn('status', [LeaveStatus::Menunggu, LeaveStatus::Disetujui])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw new SchoolException('Sudah ada pengajuan izin/cuti yang beririsan pada tanggal tersebut.');
        }

        $leave->fill([
            'type' => $data['type'],
            'start_date' => $start,
            'end_date' => $end,
            'days' => max(1, (int) ($data['days'] ?? 1)),
            'reason' => $data['reason'],
        ])->save();

        return $leave->refresh();
    }

    /**
     * Approve: sets the approval fields and writes the attendance rows for
     * the leave days (Sundays skipped — no school on Sunday).
     */
    public function approve(Leave $leave, User $approver): Leave
    {
        if ($leave->status !== LeaveStatus::Menunggu) {
            throw new SchoolException('Pengajuan izin/cuti sudah diproses.');
        }

        return DB::transaction(function () use ($leave, $approver): Leave {
            $leave = Leave::query()->whereKey($leave->getKey())->lockForUpdate()->firstOrFail();

            if ($leave->status !== LeaveStatus::Menunggu) {
                throw new SchoolException('Pengajuan izin/cuti sudah diproses.');
            }

            $leave->forceFill([
                'status' => LeaveStatus::Disetujui,
                'approved_by' => $approver->getKey(),
                'approved_at' => now(),
            ])->save();

            $attendanceStatus = $leave->type->attendanceStatus();

            foreach ($leave->workingDates() as $date) {
                app(EmployeeAttendanceService::class)->record(
                    $leave->employee,
                    $date,
                    $attendanceStatus,
                    null,
                    null,
                    ucfirst($leave->type->value).' — '.$leave->reason,
                );
            }

            return $leave->refresh();
        });
    }

    public function reject(Leave $leave, User $approver): Leave
    {
        if ($leave->status !== LeaveStatus::Menunggu) {
            throw new SchoolException('Pengajuan izin/cuti sudah diproses.');
        }

        $leave->forceFill([
            'status' => LeaveStatus::Ditolak,
            'approved_by' => $approver->getKey(),
            'approved_at' => now(),
        ])->save();

        return $leave->refresh();
    }
}
