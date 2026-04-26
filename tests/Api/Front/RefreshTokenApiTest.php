<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Tests\Api\Front;

use Thelia\Test\ApiTestCase;

/**
 * End-to-end coverage of the JWT refresh flow on both firewalls.
 *
 *  - login responses must carry a refresh_token alongside the access token,
 *  - calling the matching /token/refresh endpoint must return a fresh pair,
 *  - the refresh token must be single-use,
 *  - refresh tokens are scoped to their firewall (admin vs customer).
 */
final class RefreshTokenApiTest extends ApiTestCase
{
    public function testCustomerLoginReturnsRefreshToken(): void
    {
        $factory = $this->createFixtureFactory();
        $customer = $factory->customer($factory->customerTitle(), ['password' => 'password']);

        $response = $this->jsonRequest('POST', '/api/front/login', [
            'username' => $customer->getEmail(),
            'password' => 'password',
        ], format: 'json');

        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('refresh_token', $payload);
        self::assertNotEmpty($payload['refresh_token']);
        self::assertSame(2_592_000, $payload['refresh_token_ttl']);
    }

    public function testCustomerCanRefreshAccessToken(): void
    {
        $factory = $this->createFixtureFactory();
        $customer = $factory->customer($factory->customerTitle(), ['password' => 'password']);

        $loginResponse = $this->jsonRequest('POST', '/api/front/login', [
            'username' => $customer->getEmail(),
            'password' => 'password',
        ], format: 'json');
        $loginPayload = json_decode($loginResponse->getContent(), true);

        $response = $this->jsonRequest('POST', '/api/front/token/refresh', [
            'refresh_token' => $loginPayload['refresh_token'],
        ], format: 'json');

        self::assertSame(200, $response->getStatusCode());
        $refreshPayload = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $refreshPayload);
        self::assertArrayHasKey('refresh_token', $refreshPayload);
        self::assertNotSame($loginPayload['refresh_token'], $refreshPayload['refresh_token']);
    }

    public function testRefreshTokenIsSingleUse(): void
    {
        $factory = $this->createFixtureFactory();
        $customer = $factory->customer($factory->customerTitle(), ['password' => 'password']);

        $loginResponse = $this->jsonRequest('POST', '/api/front/login', [
            'username' => $customer->getEmail(),
            'password' => 'password',
        ], format: 'json');
        $refreshToken = json_decode($loginResponse->getContent(), true)['refresh_token'];

        $first = $this->jsonRequest('POST', '/api/front/token/refresh', [
            'refresh_token' => $refreshToken,
        ], format: 'json');
        self::assertSame(200, $first->getStatusCode());

        $replay = $this->jsonRequest('POST', '/api/front/token/refresh', [
            'refresh_token' => $refreshToken,
        ], format: 'json');
        self::assertSame(401, $replay->getStatusCode());
    }

    public function testRefreshFailsWithUnknownToken(): void
    {
        $response = $this->jsonRequest('POST', '/api/front/token/refresh', [
            'refresh_token' => 'this-token-does-not-exist',
        ], format: 'json');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testRefreshFailsWithoutToken(): void
    {
        $response = $this->jsonRequest('POST', '/api/front/token/refresh', [], format: 'json');

        self::assertSame(400, $response->getStatusCode());
    }

    public function testCustomerRefreshTokenRejectedOnAdminEndpoint(): void
    {
        $factory = $this->createFixtureFactory();
        $customer = $factory->customer($factory->customerTitle(), ['password' => 'password']);

        $loginResponse = $this->jsonRequest('POST', '/api/front/login', [
            'username' => $customer->getEmail(),
            'password' => 'password',
        ], format: 'json');
        $refreshToken = json_decode($loginResponse->getContent(), true)['refresh_token'];

        $response = $this->jsonRequest('POST', '/api/admin/token/refresh', [
            'refresh_token' => $refreshToken,
        ], format: 'json');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminLoginReturnsRefreshToken(): void
    {
        $response = $this->jsonRequest('POST', '/api/admin/login', [
            'username' => 'thelia',
            'password' => 'thelia',
        ], format: 'json');

        if ($response->getStatusCode() !== 200) {
            self::markTestSkipped('No demo admin account available for refresh-token coverage.');
        }

        $payload = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token', $payload);
        self::assertArrayHasKey('refresh_token', $payload);
        self::assertNotEmpty($payload['refresh_token']);
    }
}
