<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlannerImport extends BaseImport
{

    public $service;
    public $chunkItem = 1;

    public function __construct($file, $model, $rule = null, $service = null, string $format = null, string $driver = null)
    {
        $this->service = $service;
        parent::__construct($file, $model, $rule, $format, $driver);
    }

    public function initialize()
    {
        parent::initialize();
    }

    public function model(array $row)
    {
        try {
            $data = [
                'last_name' => $row[0],
                'first_name' => $row[1],
                'last_name_kana' => $row[2],
                'first_name_kana' => $row[3],
                'email' => $row[4],
                'phone' => $row[5],
                'note' => $row[6],
                'status' => User::ACTIVATE,
            ];
            return $this->service->store($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array|string[]
     */
    public function customValidationAttributes()
    {
        return [
            '0' => '姓',
            '1' =>  '名',
            '2' =>  '姓 （カナ）',
            '3' =>  '名（カナ）',
            '4' =>  '連絡先（メール）',
            '5' =>  '連絡先（電話番号）',
            '6' =>  '備考',
        ];
    }

    public function previewValidate()
    {
        $records = $this->toArray($this->file, $this->driver, $this->format);
        $records = $records ? $records[0] : [];
        $validateRecords = [];
        foreach ($records as $record) {
            if ($this->isDuplicatePlanner($record)) {
                $record['is_exist'] = true;
            } else {
                $record['is_exist'] = false;
            }
            $validateRecords[] = $record;
        }
        return $validateRecords;
    }

    public function isDuplicatePlanner($row)
    {
        $eventMainer = DB::table('event_mainers')->where('email', $row[4])->first();
        $planner = $this->service->findBy(['email' => $row[4]]);
        if (!$planner && !$eventMainer) {
            return false;
        }
        return true;
    }
}

