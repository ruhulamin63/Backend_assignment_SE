<?php

namespace App\Imports;

use App\Rules\BangladeshiPhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LeImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation
{

    protected $districtId;
    protected $upazilaId;
    protected $unionId;

    public function __construct($districtId, $upazilaId, $unionId)
    {
        $this->districtId = $districtId;
        $this->upazilaId = $upazilaId;
        $this->unionId = $unionId;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        $districtId = $this->districtId;
        $upazilaId = $this->upazilaId;
        $unionId = $this->unionId;

        Log::info('excel districtId', ['districtId' => $districtId]);

        $rows = $rows->filter(function ($value, $key) {
            return $value->filter()->isNotEmpty();
        });

        try {
            DB::beginTransaction();
            foreach ($rows as $key => $row) :

                $nid = DB::table('users')->whereType('le')->where('nid', $row['nid'])->first();
                // $district = DB::table('districts')->where('name', 'like', '%' . $row[6] . '%')
                //     ->orWhere('bn_name', 'like', '%' . $row[6] . '%')->first();
                // $upazila = DB::table('upazilas')->where('name', 'like', '%' . $row[7] . '%')
                //     ->orWhere('bn_name', 'like', '%' . $row[7] . '%')->first();

                if ($nid == null) {
                    DB::table('users')->insertGetId([
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'nid' => $row['nid'],
                        'trade_license_no' => $row['trade_license_no'],
                        'type' => 'le',
                        'district_id' => $districtId,
                        'upazila_id' => $upazilaId,
                        'status' => 1,
                        'password' => Hash::make('password'),
                    ]);
                }

            endforeach;
            DB::commit();
            return "All good";
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required',
            '*.phone' => ['required', new BangladeshiPhoneNumber],
            '*.nid' => 'required|numeric',
            '*.trade_license_no' => 'required',
        ];
    }
}
