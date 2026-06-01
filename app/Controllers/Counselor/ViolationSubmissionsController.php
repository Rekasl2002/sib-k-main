<?php

namespace App\Controllers\Counselor;

use App\Controllers\RoleFeatures\BaseViolationSubmissionReviewController;

class ViolationSubmissionsController extends BaseViolationSubmissionReviewController
{
    protected string $routePrefix = 'counselor';
    protected string $viewPrefix = 'counselor/violation_submissions';
    protected string $roleLabel = 'Guru BK';
}
