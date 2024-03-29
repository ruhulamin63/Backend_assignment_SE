<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Rules\NIDValidation;
use App\Models\LatrineStatusLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\BeneficiaryWorkType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Rules\BangladeshiPhoneNumber;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class UPCApprovedBeneficiaryImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, WithMultipleSheets
{
    use Importable;

    protected $districtId;
    protected $upazilaId;
    protected $unionId;
    protected $excelId;

    public function __construct($districtId, $upazilaId, $unionId, $excelId)
    {
        $this->districtId = $districtId;
        $this->upazilaId = $upazilaId;
        $this->unionId = $unionId;
        $this->excelId = $excelId;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
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
        $excelId = $this->excelId;

        $errors = [];

        $rows = $rows->filter(function ($value, $key) {
            return $value->filter()->isNotEmpty();
        });

        DB::beginTransaction();
        try {
            foreach ($rows as $key => $row) :

                $beneficiary = Beneficiary::where('is_twin_pit', 1)
                    ->where('nid', $row['nid'])
                    ->where('district_id', $districtId)
                    ->where('upazila_id', $upazilaId)
                    ->where('union_id', $unionId)
                    ->withTrashed()
                    ->first();


                if ($beneficiary == null) {

                    // check this nid related data exist or not if yes then this is another district/upazila/unioin data
                    $nid = Beneficiary::where('nid', $row['nid'])
                        ->withTrashed()
                        ->first();
                    // if nid is null, then it's new data
                    if ($nid == null) {
                        $beneficiaryId = Beneficiary::create([
                            'sl' => $row['sl'],
                            'name' => $row['name'],
                            'bangla_name' => $row['bangla_name'],
                            'father_or_husband_name' => $row['father_or_husband_name'],
                            'phone' => $row['phone'],
                            'password' => Hash::make('password'),
                            'nid' => $row['nid'],
                            'is_twin_pit' => 1,
                            'district_id' => $districtId,
                            'upazila_id' => $upazilaId,
                            'union_id' => $unionId,
                            'ward_no' => $row['ward_no'],
                            'house_name' => $row['house_location'],
                            'total_land' => $row['total_land'],
                            'occupation' => $row['occupation'],
                            'monthly_income' => $row['monthly_income'],
                            'family_head' => $row['family_head_type'],
                            'is_family_under_safety_net_scheme' => $row['is_family_under_safety_net_scheme'],
                            'safety_net_scheme_name' => $row['safety_net_scheme_name'],
                            'male_member_in_family' => $row['male_member_in_family'],
                            'female_member_in_family' => $row['female_member_in_family'],
                            'is_any_pregnant_women' => $row['is_any_pregnant_women'],
                            'number_of_child_below_5_year' => $row['number_of_child_below_5_year'],
                            'present_latrine_type' => $row['present_latrine_type'],
                            'is_only_residence' => $row['is_only_residence'],
                            'upc_excel_id' => $excelId,
                            'status_id' => 18,
                            'status_date' => now(),
                            'created_by' => auth()->user()->id,
                        ])->id;

                        BeneficiaryWorkType::create([
                            'beneficiary_id' => $beneficiaryId,
                            'work_type_id' => 1,
                        ]);

                        LatrineStatusLog::create([
                            'beneficiary_id' => $beneficiaryId,
                            'status_id' => 18,
                            'created_by' => auth()->user()->id,
                        ]);
                    } else {
                        if ($nid->deleted_at != null) {
                            $error = 'Row '.($key+2).': NID(' . $row['nid'] . ') deleted data found';
                            $errors[] = $error;
                        } else if ($nid->is_twin_pit == 1) {
                            $error = 'Row '.($key+2).': NID(' . $row['nid'] . ') data found with other District/Upazila/Union';
                            $errors[] = $error;
                        } else {
                            Beneficiary::find($nid->id)
                                ->update([
                                    'sl'                                => $row['sl'],
                                    'house_name'                        => $row['house_location'],
                                    'total_land'                        => $row['total_land'],
                                    'occupation'                        => $row['occupation'],
                                    'monthly_income'                    => $row['monthly_income'],
                                    'family_head'                       => $row['family_head_type'],
                                    'is_family_under_safety_net_scheme' => $row['is_family_under_safety_net_scheme'],
                                    'safety_net_scheme_name'            => $row['safety_net_scheme_name'],
                                    'male_member_in_family'             => $row['male_member_in_family'],
                                    'female_member_in_family'           => $row['female_member_in_family'],
                                    'is_any_pregnant_women'             => $row['is_any_pregnant_women'],
                                    'number_of_child_below_5_year'      => $row['number_of_child_below_5_year'],
                                    'present_latrine_type'              => $row['present_latrine_type'],
                                    'is_only_residence'                 => $row['is_only_residence'],
                                    'is_twin_pit'                       => 1,
                                    'upc_excel_id'                      => $excelId,
                                    'status_id'                         => 18,
                                    'status_date'                       => now(),
                                ]);

                            // Insert status log data
                            LatrineStatusLog::create([
                                'beneficiary_id' => $nid->id,
                                'status_id' => 18,
                                'created_by' => auth()->user()->id,
                            ]);

                            BeneficiaryWorkType::create([
                                'beneficiary_id' => $nid->id,
                                'work_type_id' => 1,
                            ]);
                        }
                    }
                } else {

                    if ($beneficiary->status_id === 3) {
                        $error = 'Row '.($key+2).': HCP HHs with (rejected data) found for NID(' . $row['nid']. ')';
                        $errors[] = $error;
                    } else {
                        if ($beneficiary->deleted_at != null) {
                            $error = 'Row '.($key+2).': NID(' . $row['nid'] . ') couldn\'t process. (deleted found!)';
                            $errors[] = $error;
                        } else if ($beneficiary->status_id == 1 || $beneficiary->status_id == 2) {
                            Beneficiary::find($beneficiary->id)
                                ->update([
                                    'upc_excel_id' => $excelId,
                                    'status_id' => 18,
                                    'status_date' => now(),
                                ]);

                            // Insert status log data
                            LatrineStatusLog::create([
                                'beneficiary_id' => $beneficiary->id,
                                'status_id' => 18,
                                'created_by' => auth()->user()->id,
                            ]);
                        } else {
                            $error = 'Row '.($key+2).': NID(' . $row['nid'] . ') couldn\'t process. (Already exist)';
                            $errors[] = $error;
                        }
                    }
                }

            endforeach;
            if (!empty($errors)) {
                DB::rollBack();
                // Store the errors in the session (even if there are errors)
                Session::flash('import_errors', $errors);
            }else{
                DB::commit();
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('server_errors', $e->getMessage());
        }
    }

    public function rules(): array
    {
        return [
            '*.sl' => 'numeric',
            '*.name' => 'required|string',
            '*.bangla_name' => 'required|string',
            '*.father_or_husband_name' => 'required|string',
            '*.phone' => [
                'required',
                new BangladeshiPhoneNumber,
            ],
            '*.nid' => [
                'required',
                new NIDValidation,
            ],
            '*.ward_no' => 'required|numeric',
            '*.monthly_income' => 'required|numeric',
            '*.is_family_under_safety_net_scheme' => 'required|string',
            '*.male_member_in_family' => 'required|numeric',
            '*.female_member_in_family' => 'required|numeric',
            '*.is_any_pregnant_women' => 'required|string',
            '*.number_of_child_below_5_year' => 'required|numeric',
            '*.present_latrine_type' => 'required|string',
            '*.is_only_residence' => 'required|string',

            '*.house_location' => 'nullable|string',
            '*.total_land' => 'nullable',
            '*.occupation' => 'nullable|string',
            '*.family_head_type' => 'nullable|string',
            '*.safety_net_scheme_name' => 'nullable|string',

        ];
    }
}
