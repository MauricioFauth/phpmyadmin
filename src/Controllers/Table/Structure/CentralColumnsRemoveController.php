<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Url;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

final class CentralColumnsRemoveController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly CentralColumns $centralColumns,
        private readonly ResponseFactory $responseFactory,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $GLOBALS['message'] ??= null;

        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return null;
        }

        Assert::allString($selected);

        $centralColsError = $this->centralColumns->deleteColumnsFromList(Current::$database, $selected, false);

        $message = $centralColsError instanceof Message ? $centralColsError : Message::success(__('Success!'));
        $this->flashMessenger->addMessage($message->getContext(), $message->getMessage());

        return $this->responseFactory->createResponse(StatusCodeInterface::STATUS_FOUND)->withHeader(
            'Location',
            Url::getFromRoute('/table/structure', ['db' => Current::$database, 'table' => Current::$table]),
        );
    }
}
