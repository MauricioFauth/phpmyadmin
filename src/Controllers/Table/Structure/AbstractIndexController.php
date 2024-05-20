<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Current;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Url;

use function __;
use function is_array;

abstract class AbstractIndexController
{
    public function __construct(
        protected readonly ResponseRenderer $response,
        protected readonly Indexes $indexes,
        protected readonly ResponseFactory $responseFactory,
        protected readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handleIndexCreation(ServerRequest $request, string $indexType): Response|null
    {
        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return null;
        }

        $GLOBALS['sql_query'] = Generator::getAddIndexSql($indexType, Current::$table, $selected);

        $message = $this->indexes->executeAddIndexSql(Current::$database, $GLOBALS['sql_query']);

        $this->flashMessenger->addMessage($message->getContext(), $message->getMessage(), $GLOBALS['sql_query']);

        return $this->responseFactory->createResponse(StatusCodeInterface::STATUS_FOUND)->withHeader(
            'Location',
            Url::getFromRoute('/table/structure', ['db' => Current::$database, 'table' => Current::$table]),
        );
    }
}
