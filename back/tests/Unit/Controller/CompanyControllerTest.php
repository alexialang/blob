<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CompanyController;
use App\Service\CompanyService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class CompanyControllerTest extends TestCase
{
    public function testCompanyControllerCanBeInstantiated(): void
    {
        $companyService = $this->createMock(CompanyService::class);
        $userService = $this->createMock(UserService::class);

        $controller = new CompanyController($companyService, $userService);
        $this->assertInstanceOf(CompanyController::class, $controller);
    }

    public function testCompanyControllerHasMethods(): void
    {
        $companyService = $this->createMock(CompanyService::class);
        $userService = $this->createMock(UserService::class);

        $controller = new CompanyController($companyService, $userService);
        $this->assertTrue(method_exists($controller, 'list'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'getCompanyUsers'));
        $this->assertTrue(method_exists($controller, 'getCompanyGroups'));
        $this->assertTrue(method_exists($controller, 'stats'));
        $this->assertTrue(method_exists($controller, 'exportCsv'));
        $this->assertTrue(method_exists($controller, 'exportJson'));
        $this->assertTrue(method_exists($controller, 'importCsv'));
        $this->assertTrue(method_exists($controller, 'assignUserToCompany'));
        $this->assertTrue(method_exists($controller, 'getAvailableUsers'));
    }
}
