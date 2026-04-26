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

namespace Thelia\Tests\Api\Admin;

use Thelia\Test\ApiTestCase;
use TheliaLibrary\Model\LibraryTag;
use TheliaLibrary\Model\LibraryTagQuery;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaLibrary module:
 *   POST   /api/admin/library_tags
 *   GET    /api/admin/library_tags
 *   GET    /api/admin/library_tags/{id}
 *   PATCH  /api/admin/library_tags/{id}
 *   DELETE /api/admin/library_tags/{id}
 */
final class LibraryTagApiTest extends ApiTestCase
{
    public function testAdminCanCreateLibraryTag(): void
    {
        $token = $this->authenticateAsAdmin();

        $response = $this->jsonRequest('POST', '/api/admin/library_tags', [
            'colorCode' => '#ff0000',
            'i18ns' => [
                'en_US' => ['title' => 'Hero', 'locale' => 'en_US'],
            ],
        ], $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('#ff0000', $data['colorCode']);

        $persisted = LibraryTagQuery::create()->findPk($data['id']);
        self::assertInstanceOf(LibraryTag::class, $persisted);
        self::assertSame('Hero', $persisted->setLocale('en_US')->getTitle());
    }

    public function testAdminCanGetLibraryTag(): void
    {
        $token = $this->authenticateAsAdmin();
        $tag = $this->createTag('to-read', '#00ff00');

        $response = $this->jsonRequest('GET', '/api/admin/library_tags/'.$tag->getId(), token: $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertSame($tag->getId(), $data['id']);
        self::assertSame('#00ff00', $data['colorCode']);
    }

    public function testAdminCanPatchLibraryTag(): void
    {
        $token = $this->authenticateAsAdmin();
        $tag = $this->createTag('to-update', '#000000');

        $response = $this->jsonRequest('PATCH', '/api/admin/library_tags/'.$tag->getId(), [
            'colorCode' => '#123456',
        ], $token, 'merge-patch+json');

        self::assertJsonResponseSuccessful($response);
        self::assertSame(
            '#123456',
            LibraryTagQuery::create()->findPk($tag->getId())->getColorCode(),
        );
    }

    public function testAdminCanDeleteLibraryTag(): void
    {
        $token = $this->authenticateAsAdmin();
        $tag = $this->createTag('to-delete', '#cccccc');
        $tagId = $tag->getId();

        $response = $this->jsonRequest('DELETE', '/api/admin/library_tags/'.$tagId, token: $token);

        self::assertSame(204, $response->getStatusCode());
        self::assertNull(LibraryTagQuery::create()->findPk($tagId));
    }

    public function testAdminCanListLibraryTags(): void
    {
        $token = $this->authenticateAsAdmin();
        $this->createTag('list-one', '#111111');
        $this->createTag('list-two', '#222222');

        $response = $this->jsonRequest('GET', '/api/admin/library_tags', token: $token);

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(2, $response);
    }

    private function createTag(string $title, string $colorCode): LibraryTag
    {
        $tag = (new LibraryTag())
            ->setLocale('en_US')
            ->setTitle($title)
            ->setColorCode($colorCode);
        $tag->save();

        return $tag;
    }
}
