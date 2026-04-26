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
use TheliaBlocks\Model\BlockGroup;
use TheliaBlocks\Model\ItemBlockGroupQuery;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaBlocks module for
 * the polymorphic item_block_group association:
 *   POST   /api/admin/item_block_groups (upsert by itemType+itemId)
 *   DELETE /api/admin/item_block_groups/{id}
 */
final class ItemBlockGroupApiTest extends ApiTestCase
{
    public function testAdminCanAssignBlockGroupToItem(): void
    {
        $token = $this->authenticateAsAdmin();
        $blockGroup = $this->createBlockGroup('hero', 'Hero block');

        $response = $this->jsonRequest('POST', '/api/admin/item_block_groups', [
            'itemType' => 'product',
            'itemId' => 42,
            'blockGroup' => '/api/admin/block_groups/'.$blockGroup->getId(),
        ], $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('product', $data['itemType']);
        self::assertSame(42, $data['itemId']);

        $persisted = ItemBlockGroupQuery::create()->findPk($data['id']);
        self::assertNotNull($persisted);
        self::assertSame($blockGroup->getId(), $persisted->getBlockGroupId());
    }

    public function testAdminReassignReplacesPreviousBinding(): void
    {
        $token = $this->authenticateAsAdmin();
        $first = $this->createBlockGroup('first', 'First');
        $second = $this->createBlockGroup('second', 'Second');

        $this->jsonRequest('POST', '/api/admin/item_block_groups', [
            'itemType' => 'category',
            'itemId' => 7,
            'blockGroup' => '/api/admin/block_groups/'.$first->getId(),
        ], $token);

        $this->jsonRequest('POST', '/api/admin/item_block_groups', [
            'itemType' => 'category',
            'itemId' => 7,
            'blockGroup' => '/api/admin/block_groups/'.$second->getId(),
        ], $token);

        $matchingItems = ItemBlockGroupQuery::create()
            ->filterByItemType('category')
            ->filterByItemId(7)
            ->find();

        self::assertCount(1, $matchingItems, 'Upsert must keep at most one row per (itemType, itemId).');
        self::assertSame($second->getId(), $matchingItems->getFirst()->getBlockGroupId());
    }

    public function testAdminCanDeleteItemBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();
        $blockGroup = $this->createBlockGroup('to-detach', 'Detach me');

        $createResponse = $this->jsonRequest('POST', '/api/admin/item_block_groups', [
            'itemType' => 'folder',
            'itemId' => 13,
            'blockGroup' => '/api/admin/block_groups/'.$blockGroup->getId(),
        ], $token);

        $createdId = json_decode($createResponse->getContent(), true)['id'];

        $deleteResponse = $this->jsonRequest('DELETE', '/api/admin/item_block_groups/'.$createdId, token: $token);

        self::assertSame(204, $deleteResponse->getStatusCode());
        self::assertNull(ItemBlockGroupQuery::create()->findPk($createdId));
    }

    private function createBlockGroup(string $slug, string $title): BlockGroup
    {
        $blockGroup = (new BlockGroup())
            ->setLocale('en_US')
            ->setSlug($slug)
            ->setTitle($title)
            ->setVisible(0);
        $blockGroup->save();

        return $blockGroup;
    }
}
