<?php

namespace Functional;

class Api_Admin_AuditLogCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function viewAuditLog(\FunctionalTester $I): void
    {
        $I->wantTo('View audit log via API.');

        $I->sendGet('/api/admin/auditlog');
        $I->seeResponseCodeIs(200);
    }
}
