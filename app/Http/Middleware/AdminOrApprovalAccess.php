<?php

namespace App\Http\Middleware;

use App\Models\AuditAnswer;
use App\Models\Karyawan;
use App\Models\PicArea;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrApprovalAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Cek 1: Apakah user adalah admin yang sudah login (role 1)
        if (Auth::check() && Auth::user()->role == 1) {
            return $next($request);
        }

        // Cek 2: Apakah menggunakan approval access (dari email)
        if ($this->hasApprovalAccess($request)) {
            return $next($request);
        }

        // Jika kedua kondisi tidak terpenuhi
        abort(403);
    }

    /**
     * Check if request has approval access (logic dari middleware ApprovalAccess)
     */
    private function hasApprovalAccess(Request $request)
    {
        $auditId = $request->route('id');
        $auditAnswer = AuditAnswer::find($auditId);

        if (!$auditAnswer) {
            return false; // Akan di-handle oleh middleware utama dengan abort(404)
        }

        $pic = $auditAnswer->pic_area;
        $picArea = PicArea::where('id', $pic)->first();

        if (!$picArea) {
            return false;
        }

        $empId = $picArea->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();

        if (!$karyawan) {
            return false;
        }

        $dept = $karyawan->dept;

        // Cek email dari request parameter (biasanya dari link email)
        $requestEmail = $request->get('email') ?: $request->header('X-Email');

        if (!$requestEmail) {
            return false;
        }

        if ($dept == 'MKT') {
            preg_match('/AUDITEE MKT (\d)/', $karyawan->remarks, $matches);
            if (!empty($matches)) {
                $auditeeNumber = $matches[1];
                $manager = Karyawan::where('dept', 'MKT')
                    ->where('remarks', 'LIKE', "%MGR MKT {$auditeeNumber}%")
                    ->first();

                if ($manager && $manager->email === $requestEmail) {
                    return true;
                }
            }
        } else {
            $manager = Karyawan::where('dept', $dept)
                ->where('remarks', 'LIKE', '%MGR ' . $dept . '%')
                ->where('emp_name', '!=', 'VACANT')
                ->first();

            if ($manager && $manager->email === $requestEmail) {
                return true;
            }
        }

        return false;
    }
}
