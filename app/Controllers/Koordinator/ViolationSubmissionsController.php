<?php

namespace App\Controllers\Koordinator;

use App\Controllers\RoleFeatures\BaseViolationSubmissionReviewController;

class ViolationSubmissionsController extends BaseViolationSubmissionReviewController
{
    protected string $routePrefix = 'koordinator';
    protected string $viewPrefix = 'koordinator/violation_submissions';
    protected string $roleLabel = 'Koordinator BK';
}
