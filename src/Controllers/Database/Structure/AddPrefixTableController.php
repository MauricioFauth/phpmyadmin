<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

final class AddPrefixTableController implements InvocableController
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly ResponseFactory $responseFactory,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);

        $GLOBALS['sql_query'] = '';

        $this->dbi->selectDb(Current::$database);

        foreach ($selected as $selectedValue) {
            $newTableName = $request->getParsedBodyParam('add_prefix', '') . $selectedValue;
            $aQuery = 'ALTER TABLE ' . Util::backquote($selectedValue) . ' RENAME ' . Util::backquote($newTableName);

            $GLOBALS['sql_query'] .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
        }

        $message = Message::success();

        $this->flashMessenger->addMessage($message->getContext(), $message->getMessage(), $GLOBALS['sql_query']);

        return $this->responseFactory->createResponse(StatusCodeInterface::STATUS_FOUND)
            ->withHeader('Location', Url::getFromRoute('/database/structure', ['db' => Current::$database]));
    }
}
