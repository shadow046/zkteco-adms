<?php

namespace App\Http\Controllers\ZktecoAdms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Shadow046\ZktecoAdms\Services\AdmsCoreService;
use Symfony\Component\HttpFoundation\Response;

class AdmsEndpointController extends Controller
{
    public function __construct(private readonly AdmsCoreService $service)
    {
    }

    public function cdata(Request $request): Response
    {
        return $this->service->handleCdata($request);
    }

    public function fdata(Request $request): Response
    {
        return $this->service->handleFdata($request);
    }

    public function getrequest(Request $request): Response
    {
        return $this->service->handleGetRequest($request);
    }

    public function devicecmd(Request $request): Response
    {
        return $this->service->handleDeviceCmd($request);
    }
}
