<?php
namespace Controller\Frontend;

class UtilController extends BaseController
{
    public function testAction()
    {
        $this->doNotRender();

        /** @var \Supervisor\Supervisor $supervisor */
        $supervisor = $this->di['supervisor'];

        $procs = $supervisor->getAllProcesses();
        \App\Utilities::print_r($procs);
    }
}