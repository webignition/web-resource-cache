<?php

namespace App\Services;

use App\Model\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use ResqueBundle\Resque\Job;

class RetrieveResourceJobManager extends ResqueQueueService
{
    public function contains(Job $job): bool
    {
        if (!$job instanceof RetrieveResourceJob) {
            return false;
        }

        $request = RetrieveRequest::createFromJson($job->args['request-json'] ?? '');
        if (!$request) {
            return false;
        }

        $jobs = $this->resque->getQueue(RetrieveResourceJob::QUEUE_NAME)->getJobs();
        foreach ($jobs as $foundJob) {
            $foundRetrieveRequest = RetrieveRequest::createFromJson($foundJob->args['request-json'] ?? null);

            if ($foundRetrieveRequest) {
                $requestHashMatches = $foundRetrieveRequest->getRequestHash() === $request->getRequestHash();
                $urlMatches = $foundRetrieveRequest->getUrl() === $request->getUrl();
                $headersMatches = $foundRetrieveRequest->getHeaders()->toArray() === $request->getHeaders()->toArray();

                if ($requestHashMatches && $urlMatches && $headersMatches) {
                    return true;
                }
            }
        }

        return false;
    }
}
