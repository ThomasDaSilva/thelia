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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Test\ApiTestCase;
use TheliaLibrary\Model\LibraryImage;
use TheliaLibrary\Model\LibraryImageQuery;
use TheliaLibrary\TheliaLibrary;

/**
 * Covers the AP 4.3 admin endpoints exposed by the TheliaLibrary module:
 *   POST   /api/admin/library_images          (multipart upload)
 *   POST   /api/admin/library_images/{id}/replace (multipart replace)
 *   GET    /api/admin/library_images
 *   GET    /api/admin/library_images/{id}
 *   PATCH  /api/admin/library_images/{id}
 *   DELETE /api/admin/library_images/{id}
 */
final class LibraryImageApiTest extends ApiTestCase
{
    private const FIXTURE_PATH = __DIR__.'/../../fixtures/library/sample.png';

    public function testAdminCanUploadImage(): void
    {
        $token = $this->authenticateAsAdmin();

        $response = $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'Hero image', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );

        self::assertSame(201, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $data);

        $persisted = LibraryImageQuery::create()->findPk($data['id']);
        self::assertInstanceOf(LibraryImage::class, $persisted);
        self::assertSame('Hero image', $persisted->setLocale('en_US')->getTitle());

        $persistedFileName = $persisted->setLocale('en_US')->getFileName();
        self::assertNotNull($persistedFileName);
        self::assertFileExists(TheliaLibrary::getImageDirectory().$persistedFileName);
    }

    public function testAdminCanReplaceImage(): void
    {
        $token = $this->authenticateAsAdmin();

        $createResponse = $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'Original', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );
        $imageId = json_decode($createResponse->getContent(), true)['id'];
        $originalFileName = LibraryImageQuery::create()->findPk($imageId)->setLocale('en_US')->getFileName();

        $replaceResponse = $this->multipartRequest(
            '/api/admin/library_images/'.$imageId.'/replace',
            ['title' => 'Replaced', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );

        self::assertJsonResponseSuccessful($replaceResponse);

        $persisted = LibraryImageQuery::create()->findPk($imageId);
        $newFileName = $persisted->setLocale('en_US')->getFileName();
        self::assertNotNull($newFileName);
        self::assertNotSame($originalFileName, $newFileName, 'Replace must rotate the stored filename.');
        self::assertFileExists(TheliaLibrary::getImageDirectory().$newFileName);
    }

    public function testAdminCanDeleteImage(): void
    {
        $token = $this->authenticateAsAdmin();

        $createResponse = $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'To delete', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );
        $imageId = json_decode($createResponse->getContent(), true)['id'];
        $fileName = LibraryImageQuery::create()->findPk($imageId)->setLocale('en_US')->getFileName();

        $deleteResponse = $this->jsonRequest('DELETE', '/api/admin/library_images/'.$imageId, token: $token);

        self::assertSame(204, $deleteResponse->getStatusCode());
        self::assertNull(LibraryImageQuery::create()->findPk($imageId));
        self::assertFileDoesNotExist(TheliaLibrary::getImageDirectory().$fileName);
    }

    public function testAdminCanListLibraryImages(): void
    {
        $token = $this->authenticateAsAdmin();

        $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'List one', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );
        $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'List two', 'locale' => 'en_US'],
            ['image' => $this->fixtureFile()],
            $token,
        );

        $response = $this->jsonRequest('GET', '/api/admin/library_images', token: $token);

        self::assertJsonResponseSuccessful($response);
        $data = json_decode($response->getContent(), true);
        self::assertGreaterThanOrEqual(2, $data['hydra:totalItems']);
    }

    public function testRejectsMissingImagePart(): void
    {
        $token = $this->authenticateAsAdmin();

        $response = $this->multipartRequest(
            '/api/admin/library_images',
            ['title' => 'No file', 'locale' => 'en_US'],
            [],
            $token,
        );

        self::assertSame(400, $response->getStatusCode());
    }

    private function fixtureFile(): UploadedFile
    {
        // The service moves the uploaded file out of its source path; copy
        // the fixture into a tmp file first so the source PNG survives.
        $tmpPath = tempnam(sys_get_temp_dir(), 'library-image-fixture-').'.png';
        copy(self::FIXTURE_PATH, $tmpPath);

        return new UploadedFile(
            $tmpPath,
            'sample.png',
            'image/png',
            null,
            true,
        );
    }

    private function multipartRequest(
        string $uri,
        array $parameters,
        array $files,
        string $token,
    ): Response {
        $this->client->request(
            'POST',
            $uri,
            parameters: $parameters,
            files: $files,
            server: [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
        );

        return $this->client->getResponse();
    }
}
