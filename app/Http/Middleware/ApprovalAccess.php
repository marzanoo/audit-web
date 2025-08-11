<?php

namespace App\Http\Middleware;

use App\Models\AuditAnswer;
use App\Models\Karyawan;
use App\Models\PicArea;
use Closure;
use Illuminate\Http\Request;

class ApprovalAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $auditId = $request->route('id');
        $auditAnswer = AuditAnswer::find($auditId);

        if (!$auditAnswer) {
            abort(404);
        }

        $pic = $auditAnswer->pic_area;
        $empId = PicArea::where('id', $pic)->first()->pic_id;
        $karyawan = Karyawan::where('emp_id', $empId)->first();

        if (!$karyawan) {
            abort(403);
        }

        $dept = $karyawan->dept;

        if ($dept == 'MKT') {
            preg_match('/AUDITEE MKT (\d)/', $karyawan->remarks, $matches);
            if (!empty($matches)) {
                $auditeeNumber = $matches[1];
                $manager = Karyawan::where('dept', 'MKT')
                    ->where('remarks', 'LIKE', "%MGR MKT {$auditeeNumber}%")
                    ->first();

                if ($manager && $manager->email === $request->email) {
                    return $next($request);
                }
            }
        } else {
            $manager = Karyawan::where('dept', $dept)
                ->where('remarks', 'LIKE', '%MGR ' . $dept . '%')
                ->where('emp_name', '!=', 'VACANT')
                ->first();

            if ($manager && $manager->email === $request->email) {
                return $next($request);
            }
        }

        abort(403);
    }
}
