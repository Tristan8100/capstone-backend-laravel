<?php

namespace App\Imports;

use App\Models\AlumniList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class AlumniImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header row

            $validator = Validator::make($row->toArray(), [
                '0' => 'required|unique:alumni_list,student_id',
                '1' => 'required|string',
                '2' => 'nullable|string',
                '3' => 'required|string',
                '4' => 'required|string',
                '5' => 'required|integer|digits:4',
            ]);

            if ($validator->fails()) {
                // handle validator fails
                continue;
            }

            AlumniList::create([
                'student_id'  => strtoupper($row[0]),
                'first_name'  => strtoupper($row[1]),
                'middle_name' => strtoupper($row[2]),
                'last_name'   => strtoupper($row[3]),
                'course'      => strtoupper($row[4]),
                'batch'       => $row[5],
            ]);
        }
    }
}
