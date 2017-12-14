<?php
/**
 * @copyright 2017 interactivesolutions
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Contact InteractiveSolutions:
 * E-mail: info@interactivesolutions.lt
 * http://www.interactivesolutions.lt
 */

declare(strict_types = 1);

namespace Tests\Unit\Commands\Services;

use InteractiveSolutions\HoneycombScripts\app\commands\service\HCServiceController;
use Tests\TestCase;

class HCServiceControllerOptimizeTest extends TestCase
{
    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_v1(): void
    {
        $data = $this->getPackageDataObject('interactivesolutions/honeycomb-wallet/');

        $expectData = (object)[
            "directory" => "interactivesolutions/honeycomb-wallet/",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/interactivesolutions/honeycomb-wallet/src/",
            "packageService" => true,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "InteractiveSolutions\HoneycombWallet\Http\Controllers\Wallet",
            "controllerNameForRoutes" => "Wallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./packages/interactivesolutions/honeycomb-wallet/src/Http/Controllers/Wallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_and_many_symbols_in_not_honeycomb_package(): void
    {
        $data = $this->getPackageDataObject('my-custom-package/is-going-to-work/');

        $expectData = (object)[
            "directory" => "my-custom-package/is-going-to-work/",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/my-custom-package/is-going-to-work/src/",
            "packageService" => true,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "MyCustomPackage\IsGoingToWork\Http\Controllers\Wallet",
            "controllerNameForRoutes" => "Wallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./packages/my-custom-package/is-going-to-work/src/Http/Controllers/Wallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_and_many_symbols_in_not_honeycomb_package_name_and_with_spec_symbols_in_service_url(
    ): void
    {
        $data = $this->getPackageDataObject('my-custom-package/is-going-to-work/');

        $data->serviceURL = 'wallet-is-ok/history';

        $expectData = (object)[
            "directory" => "my-custom-package/is-going-to-work/",
            "serviceURL" => "wallet-is-ok/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/my-custom-package/is-going-to-work/src/",
            "packageService" => true,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "MyCustomPackage\IsGoingToWork\Http\Controllers\WalletIsOk",
            "controllerNameForRoutes" => "WalletIsOk\\\\HCWalletHistoryController",
            "controllerDestination" => "./packages/my-custom-package/is-going-to-work/src/Http/Controllers/WalletIsOk",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_v2(): void
    {
        $data = $this->getPackageDataObject('interactivesolutions/honeycomb-core-ui/');

        $expectData = (object)[
            "directory" => "interactivesolutions/honeycomb-core-ui/",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/interactivesolutions/honeycomb-core-ui/src/",
            "packageService" => true,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "InteractiveSolutions\HoneycombCoreUi\Http\Controllers\Wallet",
            "controllerNameForRoutes" => "Wallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./packages/interactivesolutions/honeycomb-core-ui/src/Http/Controllers/Wallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_v3(): void
    {
        $data = $this->getPackageDataObject('interactivesolutions/rivile/');

        $expectData = (object)[
            "directory" => "interactivesolutions/rivile/",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/interactivesolutions/rivile/src/",
            "packageService" => true,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "InteractiveSolutions\Rivile\Http\Controllers\Wallet",
            "controllerNameForRoutes" => "Wallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./packages/interactivesolutions/rivile/src/Http/Controllers/Wallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /**
     * @test
     */
    public function it_must_return_namespace_with_upper_case_letters_in_project_creation_v1(): void
    {
        $data = $this->getProjectDataObject();

        $expectData = (object)[
            "directory" => "",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./",
            "packageService" => false,

            "controllerName" => "HCWalletHistoryController",
            "controllerNamespace" => "App\Http\Controllers\Wallet",
            "controllerNameForRoutes" => "Wallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./app/Http/Controllers/Wallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData, $response);
    }

    /** @test */
    public function it_must_return_service_url_in_upper_case_and_without_spec_symbols_in_service_main_name(): void
    {
        $data = (object)[
            "directory" => "",
            "serviceURL" => "my-super-duper-wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./",
            "packageService" => false,
        ];

        $expectData = (object)[
            "controllerNamespace" => "App\Http\Controllers\MySuperDuperWallet",
            "controllerNameForRoutes" => "MySuperDuperWallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./app/Http/Controllers/MySuperDuperWallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData->controllerNamespace, $response->controllerNamespace);
        $this->assertEquals($expectData->controllerNameForRoutes, $response->controllerNameForRoutes);
        $this->assertEquals($expectData->controllerDestination, $response->controllerDestination);
    }

    /** @test */
    public function it_must_work_with_sub_service_name_with_spec_symbols(): void
    {
        $data = (object)[
            "directory" => "",
            "serviceURL" => "my-wallet/history-is-very-good",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./",
            "packageService" => false,
        ];

        $expectData = (object)[
            "controllerNamespace" => "App\Http\Controllers\MyWallet",
            "controllerNameForRoutes" => "MyWallet\\\\HCWalletHistoryController",
            "controllerDestination" => "./app/Http/Controllers/MyWallet",
        ];

        $response = $this->getControllerInstance()->optimize($data);

        $this->assertEquals($expectData->controllerNamespace, $response->controllerNamespace);
        $this->assertEquals($expectData->controllerNameForRoutes, $response->controllerNameForRoutes);
        $this->assertEquals($expectData->controllerDestination, $response->controllerDestination);
    }

    /**
     * @return HCServiceController
     */
    private function getControllerInstance(): HCServiceController
    {
        return $this->app->make(HCServiceController::class);
    }

    /**
     * @param string $path
     * @return \stdClass
     */
    private function getPackageDataObject(string $path): \stdClass
    {
        $data = (object)[
            "directory" => "{$path}",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./packages/{$path}src/",
            "packageService" => true,
        ];

        return $data;
    }

    /**
     * @return \stdClass
     */
    private function getProjectDataObject(): \stdClass
    {
        $data = (object)[
            "directory" => "",
            "serviceURL" => "wallet/history",
            "serviceName" => "HCWalletHistory",
            "rootDirectory" => "./",
            "packageService" => false,
        ];

        return $data;
    }
}