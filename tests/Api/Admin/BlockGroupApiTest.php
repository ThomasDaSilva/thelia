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
use TheliaBlocks\Model\BlockGroupQuery;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaBlocks module:
 *   POST   /api/admin/block_groups
 *   GET    /api/admin/block_groups
 *   GET    /api/admin/block_groups/{id}
 *   PATCH  /api/admin/block_groups/{id}
 *   DELETE /api/admin/block_groups/{id}
 *   POST   /api/admin/block_groups/{id}/duplicate
 */
final class BlockGroupApiTest extends ApiTestCase
{
    public function testAdminCanCreateBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();

        $response = $this->jsonRequest('POST', '/api/admin/block_groups', [
            'visible' => true,
            'slug' => 'first-block-group',
            'i18ns' => [
                'en_US' => ['title' => 'First block group', 'jsonContent' => '{"blocks":[]}', 'locale' => 'en_US'],
            ],
        ], $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('first-block-group', $data['slug']);
        self::assertTrue($data['visible']);

        $persisted = BlockGroupQuery::create()->findPk($data['id']);
        self::assertNotNull($persisted);
        self::assertSame('First block group', $persisted->setLocale('en_US')->getTitle());
    }

    public function testAdminCanGetBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();
        $blockGroup = $this->createBlockGroup('to-read', 'Read me');

        $response = $this->jsonRequest('GET', '/api/admin/block_groups/'.$blockGroup->getId(), token: $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertSame($blockGroup->getId(), $data['id']);
        self::assertSame('to-read', $data['slug']);
    }

    public function testAdminCanPatchBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();
        $blockGroup = $this->createBlockGroup('to-update', 'Original');

        $response = $this->jsonRequest('PATCH', '/api/admin/block_groups/'.$blockGroup->getId(), [
            'visible' => true,
        ], $token, 'merge-patch+json');

        self::assertJsonResponseSuccessful($response);
        self::assertTrue((bool) BlockGroupQuery::create()->findPk($blockGroup->getId())->getVisible());
    }

    public function testAdminCanDeleteBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();
        $blockGroup = $this->createBlockGroup('to-delete', 'Delete me');
        $id = $blockGroup->getId();

        $response = $this->jsonRequest('DELETE', '/api/admin/block_groups/'.$id, token: $token);

        self::assertSame(204, $response->getStatusCode());
        self::assertNull(BlockGroupQuery::create()->findPk($id));
    }

    public function testAdminCanDuplicateBlockGroup(): void
    {
        $token = $this->authenticateAsAdmin();
        $source = $this->createBlockGroup('to-duplicate', 'Source title', '{"blocks":[{"type":"text"}]}');
        $sourceId = $source->getId();

        // The duplicate operation reads its source from the URI; the body is
        // only present to satisfy AP 4.3 which still routes through PlaceholderAction
        // expecting a denormalized $data argument.
        $response = $this->jsonRequest('POST', '/api/admin/block_groups/'.$sourceId.'/duplicate', payload: ['source' => $sourceId], token: $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertNotSame($sourceId, $data['id']);

        $duplicate = BlockGroupQuery::create()->findPk($data['id']);
        self::assertNotNull($duplicate);
        self::assertSame('Source title', $duplicate->setLocale('en_US')->getTitle());
        self::assertSame('{"blocks":[{"type":"text"}]}', $duplicate->setLocale('en_US')->getJsonContent());
    }

    public function testAdminCanListBlockGroups(): void
    {
        $token = $this->authenticateAsAdmin();
        $this->createBlockGroup('list-one', 'One');
        $this->createBlockGroup('list-two', 'Two');

        $response = $this->jsonRequest('GET', '/api/admin/block_groups', token: $token);

        self::assertJsonResponseSuccessful($response);
        self::assertHydraTotalItems(2, $response);
    }

    private function createBlockGroup(string $slug, string $title, ?string $jsonContent = null): BlockGroup
    {
        $blockGroup = (new BlockGroup())
            ->setLocale('en_US')
            ->setSlug($slug)
            ->setTitle($title)
            ->setVisible(0)
            ->setJsonContent($jsonContent);
        $blockGroup->save();

        return $blockGroup;
    }
}
