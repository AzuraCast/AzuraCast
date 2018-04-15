<?php
namespace Controller\Admin;

use App\Flash;
use Entity;
use App\Http\Request;
use App\Http\Response;

class BrandingController extends SettingsController
{
    public function indexAction(Request $request, Response $response): Response
    {
        return $this->renderSettingsForm($request, $response, 'admin/branding/index');
    }
}