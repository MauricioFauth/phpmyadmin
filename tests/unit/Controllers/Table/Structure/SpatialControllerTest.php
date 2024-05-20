<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\Table\Structure\SpatialController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SpatialController::class)]
class SpatialControllerTest extends AbstractTestCase
{
    public function testAddSpatialKeyToSingleField(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $GLOBALS['sql_query'] = '';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $flashMessenger = new FlashMessenger();
        $controller = new SpatialController(
            new ResponseRenderer(),
            $indexes,
            ResponseFactory::create(),
            $flashMessenger,
        );
        $response = $controller($request);

        self::assertNotNull($response);
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            'index.php?route=/table/structure&db=test_db&table=test_table&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame(
            [
                [
                    'context' => 'success',
                    'message' => 'Your SQL query has been executed successfully.',
                    'statement' => 'ALTER TABLE `test_table` ADD SPATIAL(`test_field`);',
                ],
            ],
            $flashMessenger->getCurrentMessages(),
        );

        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testAddSpatialKeyToMultipleFields(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $GLOBALS['message'] = null;
        $GLOBALS['sql_query'] = null;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field1', 'test_field2']]]);

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $flashMessenger = new FlashMessenger();
        $controller = new SpatialController(
            new ResponseRenderer(),
            $indexes,
            ResponseFactory::create(),
            $flashMessenger,
        );
        $response = $controller($request);

        self::assertNotNull($response);
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            'index.php?route=/table/structure&db=test_db&table=test_table&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame(
            [
                [
                    'context' => 'success',
                    'message' => 'Your SQL query has been executed successfully.',
                    'statement' => 'ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);',
                ],
            ],
            $flashMessenger->getCurrentMessages(),
        );

        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testNoColumnsSelected(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], null]]);
        $responseRenderer = new ResponseRenderer();

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $controller = new SpatialController(
            $responseRenderer,
            $indexes,
            ResponseFactory::create(),
            new FlashMessenger(),
        );
        $response = $controller($request);

        self::assertNull($response);
        self::assertFalse($responseRenderer->hasSuccessState());
        self::assertSame(['message' => 'No column selected.'], $responseRenderer->getJSONResult());
    }

    public function testAddSpatialKeyWithError(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $GLOBALS['sql_query'] = '';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', false);
        $dbiDummy->addErrorCode('#1210 - Incorrect arguments to SPATIAL INDEX');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $flashMessenger = new FlashMessenger();
        $controller = new SpatialController(
            new ResponseRenderer(),
            $indexes,
            ResponseFactory::create(),
            $flashMessenger,
        );
        $response = $controller($request);

        self::assertNotNull($response);
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            'index.php?route=/table/structure&db=test_db&table=test_table&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame(
            [
                [
                    'context' => 'danger',
                    'message' => '#1210 - Incorrect arguments to SPATIAL INDEX',
                    'statement' => 'ALTER TABLE `test_table` ADD SPATIAL(`test_field`);',
                ],
            ],
            $flashMessenger->getCurrentMessages(),
        );

        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllErrorCodesConsumed();
    }
}
