<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;

#[Route('/server/status/monitor/log-vars', ['POST'])]
final class LogVarsController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly Monitor $monitor,
        private readonly DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $this->response->addJSON([
            'message' => $this->monitor->getJsonForLoggingVars(
                $request->getParsedBodyParamAsStringOrNull('varName'),
                $request->getParsedBodyParamAsStringOrNull('varValue'),
            ),
        ]);

        return $this->response->response();
    }
}
