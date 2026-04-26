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
use TheliaBlocks\Model\BlockGroup;

/**
 * Covers the AP 4.3 public front endpoints exposed by the TheliaBlocks module:
 *   GET /api/front/block_groups
 *   GET /api/front/block_groups/{id}
 */
final class BlockGroupApiTest extends ApiTestCase
{
    public function testFrontListsBlockGroupsAnonymously(): void
    {
        $this->createBlockGroup('homepage', 'Homepage banner');
        $this->createBlockGroup('footer', 'Footer block');

        $response = $this->jsonRequest('GET', '/api/front/block_groups');

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(2, $response);
    }

    public function testFrontFiltersBlockGroupBySlug(): void
    {
        $this->createBlockGroup('homepage', 'Homepage banner');
        $this->createBlockGroup('footer', 'Footer block');

        $response = $this->jsonRequest('GET', '/api/front/block_groups?slug=footer');

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(1, $response);
    }

    private function createBlockGroup(string $slug, string $title): BlockGroup
    {
        $blockGroup = (new BlockGroup())
            ->setLocale('en_US')
            ->setSlug($slug)
            ->setTitle($title)
            ->setVisible(1);
        $blockGroup->save();

        return $blockGroup;
    }
}
