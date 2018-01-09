<?php

namespace APp;

trait RecordsActivity
{
    protected static function bootRecordsActivity()
    {

    }

    protected function recordActivity($event)
    {
        // create activity entry here
        Activity::create([
        'user_id' => auth()->id(),
        'object_table' => get_class($this),
        'object_id' => $this->id,
        'action_id' => 1,
        'object_name' => $this->name,
        'changes' => $this,
        'ip_address' => \Request::ip()
        ]);
    }

    protected function getActivityType($event)
    {
        return $event . '_' . strtolower((new \ReflectionClass($this))->getShortName());
    }
}