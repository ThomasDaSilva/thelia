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
use TheliaLibrary\Model\LibraryImage;
use TheliaLibrary\Model\LibraryImageTag;
use TheliaLibrary\Model\LibraryImageTagQuery;
use TheliaLibrary\Model\LibraryTag;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaLibrary module:
 *   POST   /api/admin/library_image_tags
 *   DELETE /api/admin/library_image_tags/{id}
 */
final class LibraryImageTagApiTest extends ApiTestCase
{
    public function testAdminCanAttachTagToImage(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();
        $tag = $this->createTag('hero', '#ff0000');

        $response = $this->jsonRequest('POST', '/api/admin/library_image_tags', [
            'libraryImage' => '/api/admin/library_images/'.$image->getId(),
            'libraryTag' => '/api/admin/library_tags/'.$tag->getId(),
        ], $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);

        $persisted = LibraryImageTagQuery::create()->findPk($data['id']);
        self::assertInstanceOf(LibraryImageTag::class, $persisted);
        self::assertSame($image->getId(), $persisted->getImageId());
        self::assertSame($tag->getId(), $persisted->getTagId());
    }

    public function testAdminCanDetachTagFromImage(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();
        $tag = $this->createTag('to-detach', '#00ff00');

        $imageTag = (new LibraryImageTag())
            ->setImageId($image->getId())
            ->setTagId($tag->getId());
        $imageTag->save();

        $response = $this->jsonRequest('DELETE', '/api/admin/library_image_tags/'.$imageTag->getId(), token: $token);

        self::assertSame(204, $response->getStatusCode());
        self::assertNull(LibraryImageTagQuery::create()->findPk($imageTag->getId()));
    }

    private function createImage(): LibraryImage
    {
        $image = (new LibraryImage())
            ->setLocale('en_US')
            ->setTitle('Sample image')
            ->setFileName('sample.png');
        $image->save();

        return $image;
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
