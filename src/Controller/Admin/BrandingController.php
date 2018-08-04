<?php
namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;

class BrandingController extends SettingsController
{
    public function indexAction(Request $request, Response $response): Response
    {
        return $this->renderSettingsForm($request, $response, 'admin/branding/index');
    }
}
