<?php

namespace App\Exceptions\Reports;

class DeleteTopicHasRelatedEntityException extends BaseException
{
    public function __construct()
    {
    	parent::__construct('exceptions.delete_topic_has_related_entity');
    }
}
