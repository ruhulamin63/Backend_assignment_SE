<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogPreference
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {

                $description = "{$this->logName} has been {$eventName}";
                if (auth()->user()) {
                    $description .= " by " . auth()->user()->name;
                }
                return $description;
            })
            ->useLogName($this->logName ?? 'default')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();

        // Chain fluent methods for configuration options
    }
}
