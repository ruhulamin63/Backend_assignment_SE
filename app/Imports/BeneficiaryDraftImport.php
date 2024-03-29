<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Rules\NIDValidation;
use Illuminate\Validation\Rule;
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

class BeneficiaryDraftImport implements ToCollection,  WithValidation, WithChunkReading, WithHeadingRow, WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
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
        $excelId = $this->excelId;

        $errors = [];

        $rows = $rows->filter(function ($value, $key) {
            return $value->filter()->isNotEmpty();
        });

        DB::beginTransaction();
        try {
            foreach ($rows as $key => $row) :
                //check nid exists
                $beneficiary = Beneficiary::where('nid', $row['nid'])
                    ->withTrashed()
                    ->first();

                if ($beneficiary == null) {
                    $beneficiary = new Beneficiary;
                    $beneficiary->sl = $row['sl'];
                    $beneficiary->name = $row['name'];
                    $beneficiary->bangla_name = $row['bangla_name'];
                    $beneficiary->father_or_husband_name = $row['father_or_husband_name'];
                    $beneficiary->phone = $row['phone'];
                    $beneficiary->password = Hash::make('password');
                    $beneficiary->nid = $row['nid'];
                    $beneficiary->is_twin_pit = 1;
                    $beneficiary->district_id = $districtId;
                    $beneficiary->upazila_id = $upazilaId;
                    $beneficiary->union_id = $unionId;
                    $beneficiary->ward_no = $row['ward_no'];
                    $beneficiary->total_land = $row['total_land'];
                    $beneficiary->house_name = $row['house_location'];
                    $beneficiary->occupation = $row['occupation'];
                    $beneficiary->monthly_income = $row['monthly_income'];
                    $beneficiary->family_head = $row['family_head_type'];
                    $beneficiary->is_family_under_safety_net_scheme = $row['is_family_under_safety_net_scheme'];
                    $beneficiary->safety_net_scheme_name = $row['safety_net_scheme_name'];
                    $beneficiary->male_member_in_family = $row['male_member_in_family'];
                    $beneficiary->female_member_in_family = $row['female_member_in_family'];
                    $beneficiary->is_any_pregnant_women = $row['is_any_pregnant_women'];
                    $beneficiary->number_of_child_below_5_year = $row['number_of_child_below_5_year'];
                    $beneficiary->present_latrine_type = $row['present_latrine_type'];
                    $beneficiary->is_only_residence = $row['is_only_residence'];
                    $beneficiary->status_id = 1;
                    $beneficiary->draft_excel_id = $excelId; //track with excel
                    $beneficiary->created_by = auth()->user()->id;
                    $beneficiary->save();

                    BeneficiaryWorkType::create([
                        'beneficiary_id' => $beneficiary->id,
                        'work_type_id' => 1,
                    ]);

                    LatrineStatusLog::create([
                        'beneficiary_id' => $beneficiary->id,
                        'status_id' => 1,
                        'created_by' => auth()->user()->id,
                    ]);
                } else {
                    if ($beneficiary->deleted_at != null || $beneficiary->is_twin_pit == 1) {
                        $error = 'Row '.($key+2).': NID(' . $row['nid'] . ') couldn\'t process. (Already exist)';
                        $errors[] = $error;
                    } else {
                        Beneficiary::find($beneficiary->id)
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
                                'draft_excel_id'                    => $excelId,
                                'status_id'                         => 1,
                                'status_date'                       => now(),
                            ]);

                        // Insert status log data
                        LatrineStatusLog::create([
                            'beneficiary_id' => $beneficiary->id,
                            'status_id' => 1,
                            'created_by' => auth()->user()->id,
                        ]);

                        BeneficiaryWorkType::create([
                            'beneficiary_id' => $beneficiary->id,
                            'work_type_id' => 1,
                        ]);
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
