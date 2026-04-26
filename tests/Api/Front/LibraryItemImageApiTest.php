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
use TheliaLibrary\Model\LibraryImage;
use TheliaLibrary\Model\LibraryItemImage;

/**
 * Covers the AP 4.3 front endpoints exposed by the TheliaLibrary module:
 *   GET /api/front/library_item_images
 *   GET /api/front/library_images
 */
final class LibraryItemImageApiTest extends ApiTestCase
{
    public function testFrontGetItemImagesByProduct(): void
    {
        $image = $this->createImage();

        $itemImage = (new LibraryItemImage())
            ->setImageId($image->getId())
            ->setItemType('product')
            ->setItemId(123)
            ->setVisible(1)
            ->setPosition(0);
        $itemImage->save();

        $response = $this->jsonRequest(
            'GET',
            '/api/front/library_item_images?itemType=product&itemId=123',
        );

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(1, $response);
    }

    public function testFrontListLibraryImages(): void
    {
        $this->createImage();
        $this->createImage();

        $response = $this->jsonRequest('GET', '/api/front/library_images');

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('hydra:totalItems', $data);
        self::assertGreaterThanOrEqual(2, $data['hydra:totalItems']);
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
