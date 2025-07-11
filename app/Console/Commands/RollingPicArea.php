<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Area;
use App\Models\Karyawan;
use App\Models\PicArea;

class RollingPicArea extends Command
{
    protected $signature = 'rolling:pic';
    protected $description = 'Rolling PIC Area pastikan pic baru tidak sama dengan pic lama';

    public function handle()
    {
        $this->info('Rolling PIC dimulai...');

        // 1. Ambil semua area
        $areas = Area::all();
        $areaCount = $areas->count();

        // 2. Filter karyawan yang bisa jadi PIC
        $excludedDepts = ['GSO', 'ASD'];
        $excludedEmpName = 'VACANT';

        $filteredKaryawans = Karyawan::whereNotIn('dept', $excludedDepts)
            ->where('emp_name', '!=', $excludedEmpName)
            ->where('remarks', 'NOT LIKE', '%MGR%')
            ->where('remarks', 'NOT LIKE', '%GM%')
            ->get();

        // 3. Ambil emp_id yang sudah jadi PIC sebelumnya
        $existingPics = PicArea::pluck('pic_id')->toArray();

        // 4. Kelompokkan karyawan berdasarkan departemen
        $karyawansByDept = $filteredKaryawans->groupBy('dept');

        // 5. Ambil quota dasar dan sisa
        $baseQuota = floor($areaCount / $karyawansByDept->count());
        $extra = $areaCount % $karyawansByDept->count();

        // 6. Siapkan array untuk assignment PIC
        $newAssignments = [];

        // 7. Loop melalui setiap departemen dan assign PIC
        foreach ($karyawansByDept as $dept => $karyawans) {
            // Filter emp_id yang belum pernah jadi PIC
            $available = $karyawans->filter(function ($k) use ($existingPics) {
                return !in_array($k->emp_id, $existingPics);
            })->shuffle();

            // Hitung jatah dept ini
            $quota = $baseQuota + ($extra > 0 ? 1 : 0);
            if ($extra > 0) $extra--;

            // Cek apakah cukup kandidat
            if ($available->count() < $quota) {
                $this->error("Dept $dept tidak punya cukup karyawan baru untuk jatah $quota PIC.");
                return 1;
            }

            // Ambil sejumlah quota
            for ($i = 0; $i < $quota; $i++) {
                $newAssignments[] = $available->get($i)->emp_id;
            }
        }

        // Final check
        if (count($newAssignments) < $areaCount) {
            $this->error("Tidak cukup emp_id unik untuk rolling semua area!");
            return 1;
        }

        // 8. Pastikan jumlah PIC sesuai dengan jumlah area
        if (count($newAssignments) < $areaCount) {
            $this->error("Jumlah karyawan yang tersedia untuk menjadi PIC kurang dari jumlah area. Rolling gagal.");
            return 1;
        }

        // 9. Assign PIC tanpa mengikat ke area (update, bukan buat baru)
        $newAssignments = collect($newAssignments)->shuffle();  // Shuffle final list of assignments
        $existingPicAreas = PicArea::all();

        foreach ($existingPicAreas as $index => $picArea) {
            $newPic = $newAssignments->pop();
            $picArea->pic_id = $newPic;
            $picArea->save();
            $this->info("PIC dengan emp_id {$newPic} berhasil di-rolling.");
        }

        $this->info("Rolling PIC selesai sukses!");
        return 0;
    }
}
