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
use TheliaLibrary\Model\LibraryItemImage;
use TheliaLibrary\Model\LibraryItemImageQuery;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaLibrary module:
 *   POST   /api/admin/library_item_images
 *   GET    /api/admin/library_item_images
 *   GET    /api/admin/library_item_images/{id}
 *   PATCH  /api/admin/library_item_images/{id}
 *   DELETE /api/admin/library_item_images/{id}
 */
final class LibraryItemImageApiTest extends ApiTestCase
{
    public function testAdminCanAssociateImageToProduct(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();

        $response = $this->jsonRequest('POST', '/api/admin/library_item_images', [
            'libraryImage' => '/api/admin/library_images/'.$image->getId(),
            'itemType' => 'product',
            'itemId' => 42,
            'visible' => true,
            'position' => 1,
        ], $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('product', $data['itemType']);
        self::assertSame(42, $data['itemId']);

        $persisted = LibraryItemImageQuery::create()->findPk($data['id']);
        self::assertInstanceOf(LibraryItemImage::class, $persisted);
        self::assertSame($image->getId(), $persisted->getImageId());
    }

    public function testAdminCanReorderItemImage(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();

        $itemImage = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('product')
            ->setItemId(7)
            ->setVisible(1)
            ->setPosition(1);
        $itemImage->save();

        $response = $this->jsonRequest('PATCH', '/api/admin/library_item_images/'.$itemImage->getId(), [
            'position' => 5,
        ], $token, 'merge-patch+json');

        self::assertJsonResponseSuccessful($response);
        self::assertSame(5, LibraryItemImageQuery::create()->findPk($itemImage->getId())->getPosition());
    }

    public function testAdminCanToggleVisibility(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();

        $itemImage = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('content')
            ->setItemId(3)
            ->setVisible(1)
            ->setPosition(0);
        $itemImage->save();

        $response = $this->jsonRequest('PATCH', '/api/admin/library_item_images/'.$itemImage->getId(), [
            'visible' => false,
        ], $token, 'merge-patch+json');

        self::assertJsonResponseSuccessful($response);
        self::assertSame(0, LibraryItemImageQuery::create()->findPk($itemImage->getId())->getVisible());
    }

    public function testAdminCanFilterByItemTypeAndId(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();

        $matching = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('category')
            ->setItemId(11)
            ->setVisible(1)
            ->setPosition(0);
        $matching->save();

        $other = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('folder')
            ->setItemId(11)
            ->setVisible(1)
            ->setPosition(0);
        $other->save();

        $response = $this->jsonRequest(
            'GET',
            '/api/admin/library_item_images?itemType=category&itemId=11',
            token: $token,
        );

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(1, $response);
    }

    public function testAdminCanDeleteItemImage(): void
    {
        $token = $this->authenticateAsAdmin();
        $image = $this->createImage();

        $itemImage = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('product')
            ->setItemId(99)
            ->setVisible(1)
            ->setPosition(0);
        $itemImage->save();

        $response = $this->jsonRequest('DELETE', '/api/admin/library_item_images/'.$itemImage->getId(), token: $token);

        self::assertSame(204, $response->getStatusCode());
        self::assertNull(LibraryItemImageQuery::create()->findPk($itemImage->getId()));
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
}
