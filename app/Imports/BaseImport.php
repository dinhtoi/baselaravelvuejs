<?php

namespace App\Imports;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class BaseImport implements ToModel, WithValidation, WithStartRow, WithBatchInserts, WithChunkReading, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public $validate = [];
    public $chunkItem = 100;
    public $startRow = 2;
    public $format;
    public $driver;
    public $file;
    public $model;
    public $rule;

    public function __construct($file, $model, $rule = null, string $format = null, string $driver = null)
    {
        $this->file = $file;
        $this->model = $model;
        $this->rule = $rule;
        $this->format = $format;
        $this->driver = $driver;
        $this->initialize();
    }

    /**
     * initialize without having to rewrite the constructor yourself
     */
    public function initialize() {}

    /**
     * handling Data before Import to Db
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[]|mixed|null
     */
    abstract public function model(array $row);

    /**
     * Define the start row to reading
     * @inheritDoc
     */
    public function startRow(): int
    {
        return $this->startRow;
    }

    /**
     * rulesImport need return a array define validate rules
     * https://docs.laravel-excel.com/3.1/imports/validation.html
     * @return array
     */
    public function rules(): array
    {
        return $this->rule->rulesImport();
    }

    /**
     * Define batchSize for insert to DB
     * @return int
     */
    public function batchSize(): int
    {
        return $this->chunkItem;
    }

    /**
     * Define chunkSize for reading file
     * @return int
     */
    public function chunkSize(): int
    {
        return $this->chunkItem;
    }

    /**
     * return file's data to array format
     * @return array|mixed
     */
    public function preview()
    {
        $result = $this->toArray($this->file, $this->driver, $this->format);
        return $result ? $result[0] : [];
    }

    /**
     * Import file conjunction validate and db exception
     * @return array
     * @throws \Exception
     */
    public function importToDb()
    {
        try {
            Log::channel('import')->info('Start Import:' . $this->file);
            DB::beginTransaction();
            $this->import($this->file, $this->driver, $this->format);
            $result = [
                'total' => count($this->preview()),
                'validate' => []
            ];
            foreach ($this->failures() as $failure) {
                $failureRow = $failure->row();
                $failureAttribute = $failure->attribute();
                if (!isset($result['validate'][$failureRow])) {
                    $result['validate'][$failureRow] = [];
                }
                if (!isset($result['validate'][$failureRow][$failureAttribute])) {
                    $result['validate'][$failureRow][$failureAttribute] = [];
                }
                $result['validate'][$failureRow][$failureAttribute] = array_merge($result['validate'][$failureRow][$failureAttribute], $failure->errors());
            }
            $result['failCount'] = count($result['validate']);
            $result['successCount'] = $result['total'] - $result['failCount'];
            DB::commit();
            Log::channel('import')->info('Import Ok ' . json_encode($result));
            Storage::delete($this->file);
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('import')->error('Import Fail ' . $e->getMessage());
            Storage::delete($this->file);
            throw $e;
        }
    }

}

