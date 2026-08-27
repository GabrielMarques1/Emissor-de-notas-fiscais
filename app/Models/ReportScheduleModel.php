<?php

namespace App\Models;

class ReportScheduleModel extends BaseAppModel
{
    protected $table = 'report_schedules';
    protected $primaryKey = 'id_schedule';
    protected $allowedFields = [
        'id_schedule',
        'id_empresa',
        'id_contador',
        'report_type',
        'frequency',
        'day_of_week',
        'day_of_month',
        'hour',
        'email_recipients',
        'filters',
        'status',
        'last_sent_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
